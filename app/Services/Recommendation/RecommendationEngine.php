<?php

namespace App\Services\Recommendation;

use App\Services\SparqlService;

/**
 * Mesin rekomendasi Aphrodite (Orang 3).
 *
 * Alur:
 *  1. Satu query SPARQL menghitung skor setiap produk (Plan A dan Plan B memakai
 *     query yang sama). Setiap kriteria yang diisi user menyumbang poin:
 *     keluhan 3 (per keluhan), jenis kulit 2, kategori 2, bahan 2, harga 1.
 *  2. Filter keras yang TIDAK PERNAH dilonggarkan: kelompok umur dan Teen Lock.
 *  3. Plan A = produk dengan skor maksimal (semua kriteria terpenuhi).
 *     Plan B = kalau Plan A kosong, 12 produk skor tertinggi + daftar kriteria
 *     yang dilonggarkan.
 *  4. Alasan (explainability) dibangun dari komponen skor yang > 0 dan label
 *     rdfs:label di ontologi.
 */
class RecommendationEngine
{
    public function __construct(
        private SparqlService $sparql,
        private Vocabulary $vocabulary,
    ) {}

    public function vocabulary(): Vocabulary
    {
        return $this->vocabulary;
    }

    /**
     * Jalankan rekomendasi untuk kriteria yang sudah divalidasi.
     *
     * Bentuk hasil (juga dipakai sebagai respons JSON, lihat docs/KONTRAK-orang3.md):
     *  plan, max_score, relaxed, relaxed_keys, teen_lock, notices, total, products[]
     */
    public function recommend(Criteria $criteria): array
    {
        $teenLock = $this->teenLockActive($criteria);
        $notices = [];

        // Teen + bahan retinoid: kriteria bahan mustahil dipenuhi, jadi diabaikan.
        $useIngredient = $criteria->ingredient !== null;
        if ($teenLock && $useIngredient && $this->ingredientIsLockedForTeen($criteria->ingredient)) {
            $useIngredient = false;
            $notices[] = $this->vocabulary->label('ingredient', $criteria->ingredient)
                .' tidak disarankan untuk usia remaja, jadi kriteria bahan diabaikan.';
        }

        $rows = $this->sparql->select($this->buildScoreQuery($criteria, $teenLock, $useIngredient));
        $maxScore = $this->maxScore($criteria, $useIngredient);

        $scored = array_map(fn (array $row) => $this->scoreRow($row, $criteria, $useIngredient, $maxScore), $rows);

        $planA = array_values(array_filter($scored, fn ($p) => $p['score'] === $maxScore));
        if ($scored === []) {
            $plan = 'none';
            $products = [];
        } elseif ($planA !== []) {
            $plan = 'A';
            $products = $planA;
        } else {
            $plan = 'B';
            $products = array_slice($scored, 0, (int) config('recommendation.plan_b_limit', 12));
        }

        [$relaxedKeys, $relaxed] = $this->relaxed($products, $plan);

        $hidden = $teenLock ? $this->countHiddenByTeenLock($criteria) : 0;

        $products = $this->attachDetails($products, $criteria);

        return [
            'plan' => $plan,
            'criteria' => $criteria->toArray(),
            'max_score' => $maxScore,
            'relaxed' => $relaxed,
            'relaxed_keys' => $relaxedKeys,
            'teen_lock' => [
                'active' => $teenLock,
                'hidden_count' => $hidden,
                'message' => $teenLock && $hidden > 0
                    ? "{$hidden} produk ber-retinol disembunyikan untuk usia remaja."
                    : null,
            ],
            'notices' => $notices,
            'total' => count($products),
            'products' => $products,
        ];
    }

    public function teenLockActive(Criteria $criteria): bool
    {
        return $criteria->age !== null
            && $criteria->age === config('recommendation.teen_lock.age_group', 'Teen');
    }

    public function maxScore(Criteria $criteria, bool $useIngredient = true): int
    {
        $w = config('recommendation.weights');
        $max = count($criteria->concerns) * $w['concern'];
        $max += $criteria->skin !== null ? $w['skin'] : 0;
        $max += $criteria->category !== null ? $w['category'] : 0;
        $max += ($useIngredient && $criteria->ingredient !== null) ? $w['ingredient'] : 0;
        $max += $criteria->hasPrice() ? $w['price'] : 0;

        return $max;
    }

    /**
     * Query skor tunggal untuk Plan A dan Plan B.
     * Semua nilai dari user sudah lewat whitelist lalu diubah menjadi IRI.
     */
    public function buildScoreQuery(Criteria $criteria, bool $teenLock, bool $useIngredient = true): string
    {
        $w = config('recommendation.weights');
        $iri = fn (string $local) => $this->sparql->iri($local);

        $select = ['?p', '?name', '?price'];
        $scoreVars = [];
        $where = [
            '?p a ex:Product ;',
            '   ex:productName ?name .',
            'OPTIONAL { ?p ex:estimatedPrice ?price }',
        ];

        // ---- Filter keras: kelompok umur (tidak pernah dilonggarkan) ----
        if ($criteria->age !== null) {
            $where[] = '?p ex:suitableForAgeGroup '.$iri($criteria->age).' .';
        }

        // ---- Filter keras: Teen Lock berbasis kelas ex:Retinoid ----
        if ($teenLock) {
            $where[] = 'FILTER NOT EXISTS {';
            $where[] = '  ?p ex:hasIngredient ?ri .';
            $where[] = '  '.$this->retinoidPattern('?ri', $criteria->age);
            $where[] = '}';
        }

        // ---- Skor jenis kulit: cocok langsung, atau produk untuk semua jenis kulit ----
        if ($criteria->skin !== null) {
            $allSkin = implode(' ', array_map($iri, $this->allSkinTypes()));
            $where[] = 'BIND(IF(EXISTS { ?p ex:suitableForSkinType '.$iri($criteria->skin).' }, "exact",';
            $where[] = '     IF(EXISTS { ?p ex:suitableForSkinType ?allSkin . VALUES ?allSkin { '.$allSkin.' } }, "all", "")) AS ?skinVia)';
            $where[] = 'BIND(IF(?skinVia != "", '.$w['skin'].', 0) AS ?sSkin)';
            array_push($select, '?skinVia', '?sSkin');
            $scoreVars[] = '?sSkin';
        }

        // ---- Skor keluhan: langsung (treatsConcern) atau lewat bahan (targetsConcern) ----
        foreach ($criteria->concerns as $i => $concern) {
            $n = $i + 1;
            $c = $iri($concern);
            $where[] = "BIND(IF(EXISTS { ?p ex:treatsConcern {$c} }, \"direct\",";
            $where[] = "     IF(EXISTS { ?p ex:hasIngredient/ex:targetsConcern {$c} }, \"ingredient\", \"\")) AS ?c{$n}Via)";
            $where[] = "BIND(IF(?c{$n}Via != \"\", {$w['concern']}, 0) AS ?sC{$n})";
            array_push($select, "?c{$n}Via", "?sC{$n}");
            $scoreVars[] = "?sC{$n}";
        }

        // ---- Skor kategori: termasuk sub-kategori lewat skos:broader* ----
        if ($criteria->category !== null) {
            $where[] = 'BIND(IF(EXISTS { ?p ex:hasCategory/skos:broader* '.$iri($criteria->category).' }, '.$w['category'].', 0) AS ?sCat)';
            $select[] = '?sCat';
            $scoreVars[] = '?sCat';
        }

        // ---- Skor bahan: individu yang sama, atau bahan bertipe golongan yang dipilih ----
        if ($useIngredient && $criteria->ingredient !== null) {
            $ing = $iri($criteria->ingredient);
            $where[] = "BIND(IF(EXISTS { { ?p ex:hasIngredient {$ing} } UNION { ?p ex:hasIngredient/rdf:type/rdfs:subClassOf* {$ing} } }, {$w['ingredient']}, 0) AS ?sIng)";
            $select[] = '?sIng';
            $scoreVars[] = '?sIng';
        }

        // ---- Skor harga (produk tanpa harga = 0 poin) ----
        if ($criteria->hasPrice()) {
            $conds = [];
            if ($criteria->priceMin !== null) {
                $conds[] = '?price >= '.$criteria->priceMin;
            }
            if ($criteria->priceMax !== null) {
                $conds[] = '?price <= '.$criteria->priceMax;
            }
            $where[] = 'BIND(COALESCE(IF('.implode(' && ', $conds).', '.$w['price'].', 0), 0) AS ?sPrice)';
            $select[] = '?sPrice';
            $scoreVars[] = '?sPrice';
        }

        $select[] = $scoreVars === [] ? '(0 AS ?score)' : '(('.implode(' + ', $scoreVars).') AS ?score)';

        return 'SELECT DISTINCT '.implode(' ', $select)."\nWHERE {\n  ".implode("\n  ", $where)."\n}\nORDER BY DESC(?score) ?price ?name";
    }

    /**
     * Pola SPARQL "bahan ?var adalah retinoid / dikontraindikasikan untuk umur ini".
     *  - ?var bertipe ex:Retinoid (atau subclass-nya)              -> ontologi v2 (B2)
     *  - ?var / golongannya punya ex:contraindicatedFor <umur>     -> ontologi v2 (B3)
     *  - ?var termasuk daftar cadangan di config                   -> selama v2 belum lengkap
     */
    public function retinoidPattern(string $var, ?string $age = null): string
    {
        $age ??= (string) config('recommendation.teen_lock.age_group', 'Teen');
        $ageIri = $this->sparql->iri($age);
        $class = $this->sparql->iri((string) config('recommendation.teen_lock.class', 'Retinoid'));

        $parts = [
            "{ {$var} rdf:type/rdfs:subClassOf* {$class} }",
            "{ {$var} ex:contraindicatedFor {$ageIri} }",
            "{ {$var} rdf:type/rdfs:subClassOf* ?lockClass . ?lockClass ex:contraindicatedFor {$ageIri} }",
        ];

        $fallback = array_filter(
            (array) config('recommendation.teen_lock.fallback_ingredients', []),
            fn ($l) => SparqlService::isSafeLocalName((string) $l)
        );
        if ($fallback !== []) {
            $parts[] = "{ VALUES {$var} { ".implode(' ', array_map(fn ($l) => $this->sparql->iri($l), $fallback)).' } }';
        }

        return implode(' UNION ', $parts);
    }

    public function ingredientIsLockedForTeen(string $ingredient): bool
    {
        $ing = $this->sparql->iri($ingredient);

        return $this->sparql->ask("ASK { VALUES ?ri { {$ing} } ".$this->retinoidPattern('?ri').' }');
    }

    /** Berapa produk yang lolos filter umur tetapi dibuang Teen Lock. */
    public function countHiddenByTeenLock(Criteria $criteria): int
    {
        $age = $this->sparql->iri((string) $criteria->age);
        $rows = $this->sparql->select(
            "SELECT (COUNT(DISTINCT ?p) AS ?n) WHERE {\n"
            ."  ?p a ex:Product ; ex:suitableForAgeGroup {$age} ; ex:hasIngredient ?ri .\n"
            .'  '.$this->retinoidPattern('?ri', $criteria->age)."\n}"
        );

        return (int) (SparqlService::value($rows[0] ?? [], 'n') ?? 0);
    }

    /** Ubah satu baris hasil query menjadi produk ber-skor + alasan. */
    private function scoreRow(array $row, Criteria $criteria, bool $useIngredient, int $maxScore): array
    {
        $v = fn (string $var) => SparqlService::value($row, $var);
        $num = fn (string $var) => (int) ($v($var) ?? 0);
        $voc = $this->vocabulary;

        $reasons = [];
        $missed = [];
        $missedKeys = [];
        $miss = function (string $key, string $label) use (&$missed, &$missedKeys) {
            $missedKeys[] = $key;
            $missed[] = $label;
        };

        if ($criteria->skin !== null) {
            $skin = $voc->label('skin', $criteria->skin);
            if ($v('skinVia') === 'exact') {
                $reasons[] = "Cocok untuk kulit {$skin}";
            } elseif ($v('skinVia') === 'all') {
                $reasons[] = "Cocok untuk semua jenis kulit (termasuk {$skin})";
            } else {
                $miss('skin', 'jenis kulit');
            }
        }

        foreach ($criteria->concerns as $i => $concern) {
            $n = $i + 1;
            $label = $voc->label('concern', $concern);
            if ($v("c{$n}Via") === 'direct') {
                $reasons[] = "Mengatasi {$label}";
            } elseif ($v("c{$n}Via") === 'ingredient') {
                $reasons[] = "Mengandung bahan yang menargetkan {$label}";
            } else {
                $miss("concern:{$concern}", "keluhan {$label}");
            }
        }

        if ($criteria->category !== null) {
            if ($num('sCat') > 0) {
                $reasons[] = 'Termasuk kategori '.$voc->label('category', $criteria->category);
            } else {
                $miss('category', 'kategori');
            }
        }

        if ($useIngredient && $criteria->ingredient !== null) {
            if ($num('sIng') > 0) {
                $reasons[] = 'Mengandung '.$voc->label('ingredient', $criteria->ingredient);
            } else {
                $miss('ingredient', 'bahan');
            }
        }

        $price = $v('price') !== null ? (int) $v('price') : null;
        if ($criteria->hasPrice()) {
            if ($num('sPrice') > 0) {
                $reasons[] = 'Harga '.self::rupiah($price).' sesuai rentang '.self::priceRange($criteria);
            } else {
                $miss('price', 'harga');
            }
        }

        $score = $num('score');
        $id = SparqlService::local((string) $v('p'));

        return [
            'id' => $id,
            'uri' => $v('p'),
            'name' => $v('name'),
            'price' => $price,
            'score' => $score,
            'max_score' => $maxScore,
            'match_percent' => $maxScore > 0 ? (int) round($score / $maxScore * 100) : 100,
            'reasons' => $reasons,
            'missed' => $missed,
            'missed_keys' => $missedKeys,
        ];
    }

    /** Kriteria yang dilonggarkan = gabungan kriteria yang tidak dipenuhi produk yang ditampilkan. */
    private function relaxed(array $products, string $plan): array
    {
        if ($plan !== 'B') {
            return [[], []];
        }

        $keys = [];
        $labels = [];
        foreach ($products as $product) {
            foreach ($product['missed_keys'] as $i => $key) {
                if (! in_array($key, $keys, true)) {
                    $keys[] = $key;
                    $labels[] = $product['missed'][$i];
                }
            }
        }

        return [$keys, $labels];
    }

    /** Ambil detail tampilan (brand, seri, kategori, jenis kulit, keluhan, bahan) untuk produk yang ditampilkan. */
    private function attachDetails(array $products, Criteria $criteria): array
    {
        if ($products === []) {
            return [];
        }

        $values = implode(' ', array_map(fn ($p) => '<'.$p['uri'].'>', $products));
        $label = fn (string $res, string $var) => "OPTIONAL { {$res} rdfs:label {$var} FILTER(lang({$var}) = \"\" || langMatches(lang({$var}), \"id\")) }";

        $viaIngredient = '';
        if ($criteria->concerns !== []) {
            $concernValues = implode(' ', array_map(fn ($c) => $this->sparql->iri($c), $criteria->concerns));
            $viaIngredient = "OPTIONAL { ?p ex:hasIngredient ?ti . ?ti ex:targetsConcern ?tc . VALUES ?tc { {$concernValues} } {$label('?ti', '?til')} }";
        }

        $rows = $this->sparql->select(<<<SPARQL
SELECT ?p
  (SAMPLE(?brand) AS ?brandIri) (SAMPLE(?brandL) AS ?brandLabel)
  (SAMPLE(?series) AS ?seriesIri) (SAMPLE(?seriesL) AS ?seriesLabel)
  (SAMPLE(?catL) AS ?categoryLabel) (SAMPLE(?img) AS ?image) (SAMPLE(?vs) AS ?verification)
  (GROUP_CONCAT(DISTINCT ?skinL; separator="|") AS ?skins)
  (GROUP_CONCAT(DISTINCT ?concernL; separator="|") AS ?concerns)
  (GROUP_CONCAT(DISTINCT ?ingL; separator="|") AS ?ingredients)
  (GROUP_CONCAT(DISTINCT ?til; separator="|") AS ?targetingIngredients)
WHERE {
  VALUES ?p { {$values} }
  OPTIONAL { ?p ex:hasBrand ?brand . {$label('?brand', '?brandL')} }
  OPTIONAL { ?p ex:belongsToSeries ?series . {$label('?series', '?seriesL')} }
  OPTIONAL { ?p ex:hasCategory ?cat . {$label('?cat', '?catL')} }
  OPTIONAL { ?p ex:suitableForSkinType ?skin . {$label('?skin', '?skinL')} }
  OPTIONAL { ?p ex:treatsConcern ?concern . {$label('?concern', '?concernL')} }
  OPTIONAL { ?p ex:hasIngredient ?ing . {$label('?ing', '?ingL')} }
  OPTIONAL { ?p schema:image ?img }
  OPTIONAL { ?p ex:verificationStatus ?vs }
  {$viaIngredient}
}
GROUP BY ?p
SPARQL);

        $split = fn (?string $s) => $s === null || $s === '' ? [] : array_values(array_unique(explode('|', $s)));
        $details = [];
        foreach ($rows as $row) {
            $v = fn (string $var) => SparqlService::value($row, $var);
            $details[(string) $v('p')] = [
                'brand' => $v('brandLabel') ?? ($v('brandIri') ? Vocabulary::humanize(SparqlService::local($v('brandIri'))) : null),
                'brand_id' => $v('brandIri') ? SparqlService::local($v('brandIri')) : null,
                'series' => $v('seriesLabel') ?? ($v('seriesIri') ? Vocabulary::humanize(SparqlService::local($v('seriesIri'))) : null),
                'series_id' => $v('seriesIri') ? SparqlService::local($v('seriesIri')) : null,
                'category' => $v('categoryLabel'),
                'image' => $v('image'),
                'verification' => $v('verification'),
                'skin_types' => $split($v('skins')),
                'concerns' => $split($v('concerns')),
                'ingredients' => $split($v('ingredients')),
                'targeting_ingredients' => $split($v('targetingIngredients')),
            ];
        }

        $empty = ['brand' => null, 'brand_id' => null, 'series' => null, 'series_id' => null, 'category' => null,
            'image' => null, 'verification' => null, 'skin_types' => [], 'concerns' => [], 'ingredients' => [], 'targeting_ingredients' => []];

        return array_map(function (array $product) use ($details, $empty) {
            $detail = $details[$product['uri']] ?? $empty;

            // Perjelas alasan "lewat bahan" dengan nama bahannya (dari triple ex:targetsConcern).
            if ($detail['targeting_ingredients'] !== []) {
                $names = implode(', ', $detail['targeting_ingredients']);
                $product['reasons'] = array_map(
                    fn ($r) => str_replace('Mengandung bahan yang menargetkan', "Mengandung {$names} yang menargetkan", $r),
                    $product['reasons']
                );
            }
            unset($detail['targeting_ingredients']);

            return $product + $detail;
        }, $products);
    }

    private function allSkinTypes(): array
    {
        $configured = (array) config('recommendation.all_skin_types', ['AllSkinType']);

        return array_values(array_filter($configured, fn ($l) => SparqlService::isSafeLocalName((string) $l)));
    }

    public static function rupiah(?int $price): string
    {
        return $price === null ? 'tidak tersedia' : 'Rp'.number_format($price, 0, ',', '.');
    }

    public static function priceRange(Criteria $criteria): string
    {
        return match (true) {
            $criteria->priceMin !== null && $criteria->priceMax !== null => self::rupiah($criteria->priceMin).'–'.self::rupiah($criteria->priceMax),
            $criteria->priceMin !== null => '≥ '.self::rupiah($criteria->priceMin),
            default => '≤ '.self::rupiah($criteria->priceMax),
        };
    }
}
