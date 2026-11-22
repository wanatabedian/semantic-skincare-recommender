<?php

namespace App\Http\Controllers;

use App\Services\CleanOntologyService;
use App\Services\IngredientConflictDetector;
use App\Services\Recommendation\RecommendationEngine;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

class ProductController extends Controller
{
    /**
     * Langkah rutinitas Complete Your Segment, diturunkan dari kategori produk
     * (ontologi clean belum punya ex:routineStep / ex:usageTime).
     * Urutan array = urutan langkah. times = waktu pemakaian kategori.
     */
    private const ROUTINE_STEPS = [
        'CleanserCategory' => ['times' => ['pagi', 'malam'], 'optional' => false],
        'TonerCategory' => ['times' => ['pagi', 'malam'], 'optional' => false],
        'SerumCategory' => ['times' => ['pagi', 'malam'], 'optional' => false],
        'SpotTreatmentCategory' => ['times' => ['pagi', 'malam'], 'optional' => false],
        'EyeCareCategory' => ['times' => ['pagi', 'malam'], 'optional' => false],
        'MoisturizerCategory' => ['times' => ['pagi', 'malam'], 'optional' => false],
        'SunscreenCategory' => ['times' => ['pagi'], 'optional' => false],
        'FaceMistCategory' => ['times' => ['pagi', 'malam'], 'optional' => true],
    ];

    public function __construct(
        private CleanOntologyService $fuseki,
        private RecommendationEngine $engine,
        private IngredientConflictDetector $conflictDetector,
    ) {}

    public function show(Request $request, string $id)
    {
        abort_unless(preg_match('/^[A-Z]{3}-\d{4}$/', $id) === 1, 404);

        try {
            $rows = $this->fuseki->query(<<<SPARQL
                SELECT DISTINCT ?product ?name ?sourceId ?price ?brand ?brandLabel ?series ?seriesLabel
                    ?category ?categoryLabel ?skin ?skinLabel ?age ?ageLabel ?concern ?concernLabel ?ingredient ?ingredientLabel
                    ?status ?note
                WHERE {
                    BIND(ex:{$id} AS ?product)
                    ?product a ex:Product ; ex:productName ?name .
                    OPTIONAL { ?product ex:sourceProductId ?sourceId }
                    OPTIONAL { ?product ex:estimatedPrice ?price }
                    OPTIONAL { ?product ex:hasBrand ?brand . OPTIONAL { ?brand rdfs:label ?brandLabel } }
                    OPTIONAL { ?product ex:belongsToSeries ?series . OPTIONAL { ?series rdfs:label ?seriesLabel } }
                    OPTIONAL { ?product ex:hasCategory ?category . OPTIONAL { ?category rdfs:label ?categoryLabel } }
                    OPTIONAL { ?product ex:suitableForSkinType ?skin . OPTIONAL { ?skin rdfs:label ?skinLabel } }
                    OPTIONAL { ?product ex:suitableForAgeGroup ?age . OPTIONAL { ?age rdfs:label ?ageLabel } }
                    OPTIONAL { ?product ex:treatsConcern ?concern . OPTIONAL { ?concern rdfs:label ?concernLabel } }
                    OPTIONAL { ?product ex:hasIngredient ?ingredient . OPTIONAL { ?ingredient rdfs:label ?ingredientLabel } }
                    OPTIONAL { ?product ex:verificationStatus ?status }
                    OPTIONAL { ?product ex:verificationNote ?note }
                }
            SPARQL);
        } catch (RuntimeException $e) {
            return response()->view('user.detail-produk', ['product' => null, 'error' => 'Data produk sementara tidak dapat dimuat. Pastikan Fuseki aktif lalu coba lagi.'], 503);
        }

        if ($rows === []) {
            abort(404);
        }

        $product = [
            'id' => $id, 'name' => '', 'sourceId' => null, 'price' => null,
            'brand' => null, 'series' => null, 'category' => null,
            'skin_types' => [], 'age_groups' => [], 'concerns' => [],
            'ingredients' => [], 'verification_status' => null,
            'verification_notes' => [],
        ];

        foreach ($rows as $row) {
            $product['name'] = $row['name']['value'] ?? $product['name'];
            $product['sourceId'] = $row['sourceId']['value'] ?? $product['sourceId'];
            $product['price'] = isset($row['price']['value']) ? (float) $row['price']['value'] : $product['price'];
            foreach (['brand' => 'brand', 'series' => 'series', 'category' => 'category'] as $key => $field) {
                if (isset($row[$key]['value'])) {
                    $product[$field] = $this->displayTerm($row[$key], $row[$key.'Label'] ?? null);
                }
            }
            foreach (['skin' => 'skin_types', 'age' => 'age_groups', 'concern' => 'concerns', 'ingredient' => 'ingredients'] as $key => $field) {
                if (isset($row[$key]['value'])) {
                    $product[$field][] = $this->displayTerm($row[$key], $row[$key.'Label'] ?? null);
                }
            }
            if (isset($row['note']['value'])) {
                $product['verification_notes'][] = trim($row['note']['value']);
            }
            $product['verification_status'] = isset($row['status'])
                ? $this->displayTerm($row['status'])
                : $product['verification_status'];
        }

        foreach (['skin_types', 'age_groups', 'concerns', 'ingredients', 'verification_notes'] as $field) {
            $product[$field] = array_values(array_unique(array_filter($product[$field], static fn ($value) => $value !== '')));
        }

        try {
            $routine = $this->completeYourSegment($id, $request->query('age'));
            $routineError = null;
        } catch (RuntimeException $e) {
            // Produk utama tetap tampil walaupun rutinitas gagal dimuat.
            $routine = null;
            $routineError = 'Rutinitas sementara tidak dapat dimuat.';
        }

        try {
            // Reuse usia yang sudah divalidasi oleh Complete Your Segment.
            $similarProducts = $this->similarProducts($id, $routine['age']['value'] ?? null);
            $similarError = null;
        } catch (RuntimeException $e) {
            // Produk utama tetap tampil walaupun produk serupa gagal dimuat.
            $similarProducts = [];
            $similarError = 'Produk serupa sementara tidak dapat dimuat.';
        }

        return view('user.detail-produk', [
            'product' => $product,
            'error' => null,
            'similarProducts' => $similarProducts,
            'similarError' => $similarError,
            'routine' => $routine,
            'routineError' => $routineError,
        ]);
    }

    /**
     * Complete Your Segment v2.
     *
     * 1. Satu query SPARQL mengambil semua kandidat (produk dengan kategori rutinitas),
     *    lengkap dengan tanda: se-seri dengan produk utama, mengandung Retinol,
     *    terkena Teen Lock, dan jumlah keluhan / jenis kulit yang sama.
     * 2. Untuk setiap langkah (kategori) dipilih SATU produk utama:
     *    produk yang sedang dibuka -> produk se-seri -> produk pelengkap dari seri lain.
     *    Produk se-seri lain di kategori yang sama menjadi alternatif.
     * 3. Langkah dibagi ke Pagi / Malam sesuai aturan kategori; produk ber-Retinol hanya Malam.
     */
    private function completeYourSegment(string $id, mixed $ageParam): array
    {
        $categories = implode(' ', array_map(fn ($c) => "ex:{$c}", array_keys(self::ROUTINE_STEPS)));
        [$age, $categoryLabels, $ingredientLabels] = $this->routineVocabulary($ageParam, $categories);
        $teenLock = $age !== null && $age['value'] === config('recommendation.teen_lock.age_group', 'Teen');
        // Filter keras kelompok umur (sama seperti mesin rekomendasi).
        // Tandai kelayakan umur tanpa membuang baris sebelum Teen Lock dihitung.
        $ageEligibilityFlag = $age !== null
            ? "BIND(EXISTS { ?product ex:suitableForAgeGroup ex:{$age['value']} } AS ?ageEligibleFlag)"
            : 'BIND(true AS ?ageEligibleFlag)';
        // Teen Lock memakai pola milik Orang 3 supaya aturannya sama persis.
        $lockedFlag = $teenLock
            ? 'BIND(EXISTS { ?product ex:hasIngredient ?ri . '.$this->engine->retinoidPattern('?ri', $age['value']).' } AS ?lockedFlag)'
            : 'BIND(false AS ?lockedFlag)';

        $rows = $this->fuseki->query(<<<SPARQL
            PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
            SELECT ?product ?name ?category
                (SAMPLE(?priceValue) AS ?price)
                (SAMPLE(?brandUri) AS ?brand)
                (SAMPLE(?brandName) AS ?brandLabel)
                (SAMPLE(?sameSeriesFlag) AS ?sameSeries)
                (SAMPLE(?retinolFlag) AS ?hasRetinol)
                (SAMPLE(?lockedFlag) AS ?teenLocked)
                (SAMPLE(?ageEligibleFlag) AS ?ageEligible)
                (COUNT(DISTINCT ?sharedConcern) AS ?concernMatches)
                (COUNT(DISTINCT ?sharedSkin) AS ?skinMatches)
                (GROUP_CONCAT(DISTINCT STR(?ingredientUri); separator=" ") AS ?ingredientList)
            WHERE {
                BIND(ex:{$id} AS ?source)
                VALUES ?category { {$categories} }
                ?product a ex:Product ; ex:productName ?name ; ex:hasCategory ?category .
                {$ageEligibilityFlag}

                BIND(EXISTS { ?source ex:belongsToSeries ?s . ?product ex:belongsToSeries ?s } AS ?sameSeriesFlag)
                BIND(EXISTS { ?product ex:hasIngredient ex:Retinol } AS ?retinolFlag)
                {$lockedFlag}

                OPTIONAL { ?product ex:estimatedPrice ?priceValue }
                OPTIONAL { ?product ex:hasBrand ?brandUri . OPTIONAL { ?brandUri rdfs:label ?brandName } }
                OPTIONAL {
                    ?source ex:treatsConcern ?sharedConcern . ?product ex:treatsConcern ?sharedConcern .
                    ?sharedConcern a ex:SkinConcern .
                }
                OPTIONAL {
                    ?source ex:suitableForSkinType ?sharedSkin . ?product ex:suitableForSkinType ?sharedSkin .
                    ?sharedSkin a ex:SkinType .
                }
                # Bahan produk, untuk pemeriksaan kombinasi bahan antarproduk (tidak memengaruhi pemilihan langkah).
                OPTIONAL { ?product ex:hasIngredient ?ingredientUri . ?ingredientUri a ex:Ingredient . }
            }
            GROUP BY ?product ?name ?category
        SPARQL);

        // Ubah baris SPARQL menjadi kandidat per kategori.
        $candidates = [];
        $hiddenSeries = 0;
        $mainHidden = false;
        $sourceHasSkin = false;

        foreach ($rows as $row) {
            $productId = $this->fuseki->local($row['product']['value'] ?? '');
            $category = $this->fuseki->local($row['category']['value'] ?? '');

            if (preg_match('/^[A-Z]{3}-\d{4}$/', $productId) !== 1 || ! isset(self::ROUTINE_STEPS[$category])) {
                continue;
            }

            $sameSeries = $this->isTrue($row['sameSeries'] ?? null);

            if ($this->isTrue($row['teenLocked'] ?? null)) {
                if ($productId === $id) {
                    $mainHidden = true;
                } elseif ($sameSeries) {
                    $hiddenSeries++;
                }
                // Pertahankan sinyal kecocokan kulit dari baris yang lolos umur.
                if ($age === null || ! isset($row['ageEligible']) || $this->isTrue($row['ageEligible'])) {
                    $sourceHasSkin = $sourceHasSkin || (int) ($row['skinMatches']['value'] ?? 0) > 0;
                }
                continue;
            }

            // Teen Lock sudah dihitung; baru sekarang gugurkan produk di luar kelompok umur.
            if ($age !== null && isset($row['ageEligible']) && ! $this->isTrue($row['ageEligible'])) {
                continue;
            }
            $sourceHasSkin = $sourceHasSkin || (int) ($row['skinMatches']['value'] ?? 0) > 0;

            $candidates[$category][] = [
                'id' => $productId,
                'name' => trim($row['name']['value'] ?? ''),
                'price' => isset($row['price']['value']) ? (float) $row['price']['value'] : null,
                'brand' => trim($row['brandLabel']['value'] ?? '')
                    ?: (isset($row['brand']['value']) ? $this->fuseki->label($row['brand']['value']) : null),
                'is_main' => $productId === $id,
                'same_series' => $sameSeries,
                'has_retinol' => $this->isTrue($row['hasRetinol'] ?? null),
                'concern_matches' => (int) ($row['concernMatches']['value'] ?? 0),
                'skin_matches' => (int) ($row['skinMatches']['value'] ?? 0),
                'ingredients' => array_values(array_map(
                    fn ($uri) => $this->fuseki->local($uri),
                    array_filter(explode(' ', trim($row['ingredientList']['value'] ?? '')))
                )),
            ];
        }

        // Urutan pilihan: produk yang dibuka, lalu keluhan sama terbanyak, jenis kulit sama terbanyak, nama.
        $rank = static fn (array $a, array $b) => [$b['is_main'], $b['concern_matches'], $b['skin_matches'], $a['name']]
            <=> [$a['is_main'], $a['concern_matches'], $a['skin_matches'], $b['name']];

        $steps = [];
        $number = 0;

        foreach (self::ROUTINE_STEPS as $category => $rule) {
            $items = $candidates[$category] ?? [];
            $series = array_values(array_filter($items, fn ($p) => $p['same_series']));
            usort($series, $rank);

            if ($series !== []) {
                $primary = array_shift($series);
                $primary['source'] = $primary['is_main'] ? 'main' : 'series';
                $alternatives = $series;
            } else {
                // Pelengkap dari seri lain: wajib berbagi minimal 1 jenis kulit (jika produk utama punya data jenis kulit).
                $others = array_values(array_filter($items, fn ($p) => ! $sourceHasSkin || $p['skin_matches'] > 0));
                usort($others, $rank);
                $primary = $others[0] ?? null;
                if ($primary !== null) {
                    $primary['source'] = 'other';
                }
                $alternatives = [];
            }

            $times = $rule['times'];
            // Produk ber-Retinol hanya dipakai Malam (jika kategori memang dipakai Malam).
            if ($primary !== null && $primary['has_retinol'] && in_array('malam', $times, true)) {
                $times = ['malam'];
            }

            $steps[] = [
                'number' => $rule['optional'] ? null : ++$number,
                'category' => $category,
                'label' => $categoryLabels[$category] ?? $this->fuseki->label($category),
                'optional' => $rule['optional'],
                'times' => $times,
                'product' => $primary,
                'alternatives' => $alternatives,
            ];
        }

        $routine = [
            'age' => $age,
            'teen_lock' => $teenLock,
            'main_hidden' => $mainHidden,
            'hidden_series_count' => $hiddenSeries,
            'steps' => $steps,
            'pagi' => array_values(array_filter($steps, fn ($s) => in_array('pagi', $s['times'], true))),
            'malam' => array_values(array_filter($steps, fn ($s) => in_array('malam', $s['times'], true))),
        ];

        // Pemeriksaan kombinasi bahan per waktu pakai. Gagal di sini tidak boleh menggagalkan rutinitas.
        try {
            $routine['conflicts'] = [
                'pagi' => $this->routineConflicts($routine['pagi'], $ingredientLabels),
                'malam' => $this->routineConflicts($routine['malam'], $ingredientLabels),
            ];
            $routine['conflict_error'] = null;
        } catch (\Throwable $e) {
            report($e);
            $routine['conflicts'] = null;
            $routine['conflict_error'] = 'Pemeriksaan kombinasi bahan sementara tidak tersedia.';
        }

        return $routine;
    }

    /**
     * Jalankan IngredientConflictDetector untuk satu waktu pakai (Pagi atau Malam).
     * Hanya produk utama tiap langkah yang diperiksa (termasuk langkah opsional jika terisi);
     * alternatif tidak pernah ikut. Hasil asli detektor disimpan utuh, ditambah
     * `groups` (peringatan dikelompokkan per aturan) dan `involved_ids` untuk tampilan.
     */
    private function routineConflicts(array $steps, array $ingredientLabels): array
    {
        $products = [];
        foreach ($steps as $step) {
            if ($step['product'] === null) {
                continue;
            }
            $item = $step['product'];
            $products[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'ingredients' => $item['ingredients'] ?? [],
                'ingredient_labels' => array_intersect_key($ingredientLabels, array_flip($item['ingredients'] ?? [])),
            ];
        }

        $result = $this->conflictDetector->detect($products);
        $result['groups'] = $this->groupConflicts($result['conflicts']);

        $involved = [];
        foreach ($result['groups'] as $group) {
            foreach ($group['products'] as $product) {
                $involved[$product['id']] = true;
            }
        }
        $result['involved_ids'] = array_keys($involved);

        return $result;
    }

    /** Gabungkan konflik berpasangan dengan aturan yang sama menjadi satu peringatan. */
    private function groupConflicts(array $conflicts): array
    {
        $groups = [];

        foreach ($conflicts as $conflict) {
            $rule = $conflict['rule'];
            $groups[$rule] ??= [
                'rule' => $rule,
                'level' => $conflict['level'],
                'level_label' => $conflict['level_label'],
                'reason' => $conflict['reason'],
                'sources' => $conflict['sources'],
                'products' => [],
                'ingredients' => [],
                'pair_count' => 0,
            ];
            $groups[$rule]['pair_count']++;

            foreach ($conflict['products'] as $product) {
                $entry = &$groups[$rule]['products'][$product['id']];
                $entry ??= ['id' => $product['id'], 'name' => $product['name'], 'ingredients' => []];
                foreach ($product['ingredients'] as $ingredient) {
                    $entry['ingredients'][$ingredient['id']] = $ingredient['label'];
                    $groups[$rule]['ingredients'][$ingredient['id']] = $ingredient['label'];
                }
                unset($entry);
            }
        }

        return array_values(array_map(function (array $group) {
            $group['products'] = array_values(array_map(function (array $product) {
                $product['ingredients'] = array_values($product['ingredients']);

                return $product;
            }, $group['products']));
            $group['ingredients'] = array_values($group['ingredients']);

            return $group;
        }, $groups));
    }

    /**
     * Label kategori rutinitas, label bahan, dan daftar kelompok umur dari ontologi.
     * Parameter ?age= hanya dipakai jika benar-benar ada sebagai ex:AgeGroup (whitelist).
     *
     * @return array{0: ?array, 1: array<string, string>, 2: array<string, string>}
     */
    private function routineVocabulary(mixed $ageParam, string $categories): array
    {
        $rows = $this->fuseki->query(<<<SPARQL
            SELECT ?ageGroup ?ageGroupLabel ?routineCategory ?routineCategoryLabel ?ingredientItem ?ingredientItemLabel WHERE {
                { ?ageGroup a ex:AgeGroup . OPTIONAL { ?ageGroup rdfs:label ?ageGroupLabel } }
                UNION
                { VALUES ?routineCategory { {$categories} } ?routineCategory rdfs:label ?routineCategoryLabel }
                UNION
                { ?ingredientItem a ex:Ingredient ; rdfs:label ?ingredientItemLabel }
            }
        SPARQL);

        $age = null;
        $labels = [];
        $ingredientLabels = [];

        foreach ($rows as $row) {
            if (isset($row['routineCategory']['value'])) {
                $labels[$this->fuseki->local($row['routineCategory']['value'])] = trim($row['routineCategoryLabel']['value'] ?? '');
            }
            if (isset($row['ingredientItem']['value'])) {
                $ingredientLabels[$this->fuseki->local($row['ingredientItem']['value'])] = trim($row['ingredientItemLabel']['value'] ?? '');
            }
            if (is_string($ageParam) && isset($row['ageGroup']['value']) && $this->fuseki->local($row['ageGroup']['value']) === $ageParam
                && preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $ageParam) === 1) {
                $age = ['value' => $ageParam, 'label' => trim($row['ageGroupLabel']['value'] ?? '') ?: $ageParam];
            }
        }

        return [$age, array_filter($labels), array_filter($ingredientLabels)];
    }

    private function isTrue(?array $term): bool
    {
        return in_array(strtolower((string) ($term['value'] ?? '')), ['true', '1'], true);
    }

    /**
     * Produk serupa: brand berbeda, minimal 1 jenis kulit yang sama, dan minimal
     * 1 keluhan yang sama dengan produk utama. Produk dengan lebih banyak keluhan
     * dan jenis kulit yang sama ditaruh paling atas. Maksimal 4 produk.
     */
    private function similarProducts(string $id, ?string $age = null, int $limit = 4): array
    {
        $ageFilter = $age !== null ? "?product ex:suitableForAgeGroup ex:{$age} ." : '';
        $teenLockFilter = $age === config('recommendation.teen_lock.age_group', 'Teen')
            ? 'FILTER NOT EXISTS { ?product ex:hasIngredient ?teenLockedIngredient . '
                .$this->engine->retinoidPattern('?teenLockedIngredient', $age).' }'
            : '';

        $rows = $this->fuseki->query(<<<SPARQL
            PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
            SELECT ?product ?name
                (SAMPLE(?priceValue) AS ?price)
                (SAMPLE(?brandName) AS ?brandLabel)
                (SAMPLE(?brand) AS ?brandUri)
                (SAMPLE(?categoryName) AS ?categoryLabel)
                (COUNT(DISTINCT ?sharedConcern) AS ?concernMatches)
                (COUNT(DISTINCT ?sharedSkin) AS ?skinMatches)
                (GROUP_CONCAT(DISTINCT ?sharedConcernName; separator="|") AS ?sharedConcernLabels)
            WHERE {
                BIND(ex:{$id} AS ?source)
                ?source ex:hasBrand ?sourceBrand .

                ?product a ex:Product ; ex:productName ?name ; ex:hasBrand ?brand .
                {$ageFilter}
                {$teenLockFilter}
                FILTER(?product != ?source)
                FILTER NOT EXISTS { ?product ex:hasBrand ?sourceBrand }

                # Hanya individu ontologi (bukan teks lama) yang dihitung sebagai kecocokan.
                ?source ex:suitableForSkinType ?sharedSkin .
                ?product ex:suitableForSkinType ?sharedSkin .
                ?sharedSkin a ex:SkinType .
                ?source ex:treatsConcern ?sharedConcern .
                ?product ex:treatsConcern ?sharedConcern .
                ?sharedConcern a ex:SkinConcern .

                OPTIONAL { ?sharedConcern rdfs:label ?sharedConcernName }
                OPTIONAL { ?product ex:estimatedPrice ?priceValue }
                OPTIONAL { ?brand rdfs:label ?brandName }
                OPTIONAL { ?product ex:hasCategory ?category . OPTIONAL { ?category rdfs:label ?categoryName } }
            }
            GROUP BY ?product ?name
            ORDER BY DESC(?concernMatches) DESC(?skinMatches) ?name
            LIMIT 20
        SPARQL);

        $products = [];

        foreach ($rows as $row) {
            $productId = $this->fuseki->local($row['product']['value'] ?? '');

            // Hanya ID yang bisa dibuka lewat /produk/{id} (menyaring sisa data lama di Fuseki).
            if (preg_match('/^[A-Z]{3}-\d{4}$/', $productId) !== 1 || $productId === $id || isset($products[$productId])) {
                continue;
            }

            $products[$productId] = [
                'id' => $productId,
                'name' => trim($row['name']['value'] ?? ''),
                'price' => isset($row['price']['value']) ? (float) $row['price']['value'] : null,
                // Brand di ontologi clean belum punya rdfs:label -> pakai nama dari URI (KahfBrand -> Kahf).
                'brand' => trim($row['brandLabel']['value'] ?? '')
                    ?: (isset($row['brandUri']['value']) ? $this->fuseki->label($row['brandUri']['value']) : null),
                'category' => trim($row['categoryLabel']['value'] ?? '') ?: null,
                'shared_concerns' => array_values(array_filter(explode('|', $row['sharedConcernLabels']['value'] ?? ''))),
            ];

            if (count($products) === $limit) {
                break;
            }
        }

        return array_values($products);
    }

    /** Convert RDF terms to readable display values without altering literals. */
    private function displayTerm(array $term, ?array $label = null): string
    {
        $value = trim($term['value'] ?? '');

        if (($term['type'] ?? '') !== 'uri') {
            return $value;
        }

        $labelValue = trim($label['value'] ?? '');

        return $labelValue !== '' ? $labelValue : $this->fuseki->label($value);
    }
}
