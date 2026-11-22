<?php

namespace App\Http\Controllers;

use App\Services\Recommendation\Vocabulary;
use App\Services\SparqlException;
use App\Services\SparqlService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Orang 5 — Eksplorasi Katalog.
 *
 * Tahap 3: search nama produk, filter (kategori, jenis kulit, keluhan, brand, harga),
 * dan dynamic facet counts — semuanya dihitung lewat SPARQL ke ontologi.
 *
 * Logika filter: OR di dalam satu kelompok, AND antar kelompok, search selalu ikut.
 * Facet satu kelompok dihitung dengan MENGABAIKAN pilihan kelompok itu sendiri.
 * Dokumentasi query: docs/queries/katalog-*.rq dan katalog-search-filter-facet.md.
 *
 * Keamanan (anti SPARQL injection):
 * - Nilai filter hanya diterima bila ada di daftar opsi dari ontologi (whitelist),
 *   lalu diubah menjadi IRI lewat SparqlService::iri().
 * - Rentang harga hanya berupa kunci PRICE_RANGES; angka tidak pernah berasal dari input.
 * - Kata kunci search dibersihkan lalu di-escape dengan SparqlService::literal().
 *
 * Sorting & pagination dikerjakan pada tahap berikutnya.
 */
class CatalogController extends Controller
{
    /** Foto produk: public/images/{slug-brand}/{sourceProductId}.{ext} */
    public const IMAGE_DIR = 'images';

    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public const SEARCH_MAX_LENGTH = 100;

    /**
     * Kelompok filter berbasis relasi ontologi: kunci URL => property + judul.
     * Urutan array = urutan di sidebar = urutan parameter di URL.
     */
    public const GROUPS = [
        'kategori' => ['property' => 'hasCategory', 'title' => 'Kategori'],
        'kulit' => ['property' => 'suitableForSkinType', 'title' => 'Jenis kulit'],
        'concern' => ['property' => 'treatsConcern', 'title' => 'Keluhan kulit'],
        'brand' => ['property' => 'hasBrand', 'title' => 'Brand'],
    ];

    public const PRICE_GROUP = 'harga';

    public const PRICE_TITLE = 'Rentang harga';

    /**
     * Rentang harga setengah terbuka [min, max) atas ex:estimatedPrice (xsd:integer),
     * sehingga harga tepat di batas (50.000, 100.000, 150.000) hanya masuk satu rentang.
     */
    public const PRICE_RANGES = [
        'lt50' => ['min' => null, 'max' => 50000, 'label' => 'Di bawah Rp50.000'],
        '50-100' => ['min' => 50000, 'max' => 100000, 'label' => 'Rp50.000 – Rp99.999'],
        '100-150' => ['min' => 100000, 'max' => 150000, 'label' => 'Rp100.000 – Rp149.999'],
        'gte150' => ['min' => 150000, 'max' => null, 'label' => 'Rp150.000 ke atas'],
    ];

    /** Total produk di ontologi (angka "Produk terdata" di hero). */
    public const TOTAL_QUERY = <<<'SPARQL'
# katalog:total
SELECT (COUNT(DISTINCT ?p) AS ?total)
WHERE {
  ?p a ex:Product ;
     ex:productName ?nm .
}
SPARQL;

    /**
     * Opsi filter = nilai relasi yang benar-benar dipakai oleh produk (bukan daftar statis).
     * Label: rdfs:label@id -> rdfs:label tanpa bahasa -> label apa pun (dipilih di PHP).
     */
    public const OPTIONS_QUERY = <<<'SPARQL'
# katalog:opsi
SELECT ?group ?opt (SAMPLE(?lid) AS ?labelId) (SAMPLE(?lplain) AS ?labelPlain) (SAMPLE(?lany) AS ?labelAny)
WHERE {
  VALUES (?group ?prop) {
    ("kategori" ex:hasCategory)
    ("kulit"    ex:suitableForSkinType)
    ("concern"  ex:treatsConcern)
    ("brand"    ex:hasBrand)
  }
  ?p a ex:Product ;
     ex:productName ?nm ;
     ?prop ?opt .
  FILTER(isIRI(?opt))
  OPTIONAL { ?opt rdfs:label ?lid FILTER(langMatches(lang(?lid), "id")) }
  OPTIONAL { ?opt rdfs:label ?lplain FILTER(lang(?lplain) = "") }
  OPTIONAL { ?opt rdfs:label ?lany }
}
GROUP BY ?group ?opt
SPARQL;

    /**
     * Daftar produk. {{FILTERS}} diganti blok filter (search + semua kelompok aktif).
     * Filter memakai FILTER EXISTS sehingga relasi bernilai banyak tidak menggandakan baris.
     */
    public const PRODUCTS_QUERY = <<<'SPARQL'
# katalog:produk
SELECT ?p
  (SAMPLE(?sid) AS ?sourceId) (SAMPLE(?nm) AS ?name) (SAMPLE(?pr) AS ?price)
  (SAMPLE(?brand) AS ?brandIri) (SAMPLE(?brandL) AS ?brandLabel)
  (SAMPLE(?cat) AS ?categoryIri) (SAMPLE(?catL) AS ?categoryLabel)
  (SAMPLE(?img) AS ?image)
  (GROUP_CONCAT(DISTINCT ?skinL; separator="|") AS ?skins)
  (GROUP_CONCAT(DISTINCT ?concernL; separator="|") AS ?concerns)
WHERE {
  ?p a ex:Product ;
     ex:productName ?nm .
{{FILTERS}}
  OPTIONAL { ?p ex:sourceProductId ?sid }
  OPTIONAL { ?p ex:estimatedPrice ?pr }
  OPTIONAL { ?p ex:hasBrand ?brand .
             OPTIONAL { ?brand rdfs:label ?brandL FILTER(lang(?brandL) = "" || langMatches(lang(?brandL), "id")) } }
  OPTIONAL { ?p ex:hasCategory ?cat .
             OPTIONAL { ?cat rdfs:label ?catL FILTER(lang(?catL) = "" || langMatches(lang(?catL), "id")) } }
  OPTIONAL { ?p ex:suitableForSkinType ?skin .
             ?skin rdfs:label ?skinL FILTER(lang(?skinL) = "" || langMatches(lang(?skinL), "id")) }
  OPTIONAL { ?p ex:treatsConcern ?concern .
             ?concern rdfs:label ?concernL FILTER(lang(?concernL) = "" || langMatches(lang(?concernL), "id")) }
  OPTIONAL { ?p schema:image ?img }
}
GROUP BY ?p
SPARQL;

    /** Facet kelompok relasi. {{FILTERS}} = semua filter KECUALI kelompok ini sendiri. */
    public const FACET_QUERY = <<<'SPARQL'
# katalog:facet:{{GROUP}}
SELECT ?opt (COUNT(DISTINCT ?p) AS ?n)
WHERE {
  ?p a ex:Product ;
     ex:productName ?nm .
{{FILTERS}}
  ?p {{PROPERTY}} ?opt .
}
GROUP BY ?opt
SPARQL;

    /** Facet harga: setiap harga numerik dipetakan ke tepat satu rentang (BIND), lalu dihitung. */
    public const PRICE_FACET_QUERY = <<<'SPARQL'
# katalog:facet:harga
SELECT ?opt (COUNT(DISTINCT ?p) AS ?n)
WHERE {
  ?p a ex:Product ;
     ex:productName ?nm .
{{FILTERS}}
  ?p ex:estimatedPrice ?price .
  FILTER(isNumeric(?price))
  BIND({{BUCKET}} AS ?opt)
  FILTER(?opt != "")
}
GROUP BY ?opt
SPARQL;

    public function __construct(private SparqlService $sparql) {}

    public function index(Request $request)
    {
        $products = [];
        $facets = [];
        $options = [];
        $total = null;
        $error = null;

        try {
            $options = $this->options();
            $state = $this->state($request, $options);
            $total = $this->total();
            $products = $this->products($state);
            $facets = $this->facets($state, $options);
        } catch (SparqlException $e) {
            $error = $e->getMessage();
            $state = $this->state($request, []);
        }

        $hasFilters = $state['q'] !== '' || $this->activeCount($state) > 0;

        return view('user.katalog', [
            'products' => $products,
            'productCount' => $error === null ? $total : null,
            'brandCount' => collect($products)->pluck('brand')->filter()->unique()->count(),
            'error' => $error,
            'search' => $state['q'],
            'state' => $state,
            'facets' => $facets,
            'chips' => $this->chips($state, $options),
            'hasFilters' => $hasFilters,
            'activeCount' => $this->activeCount($state) + ($state['q'] !== '' ? 1 : 0),
            'resetUrl' => route('katalog'),
        ]);
    }

    /* ------------------------------------------------------------------
     | Input & URL
     * ------------------------------------------------------------------ */

    /** Bersihkan kata kunci: hanya string, tanpa karakter kontrol, spasi dirapikan, maks. 100 karakter. */
    public static function searchTerm(mixed $raw): string
    {
        if (! is_string($raw)) {
            return '';
        }

        $clean = preg_replace('/\p{C}+/u', ' ', $raw); // null bila UTF-8 tidak valid
        if ($clean === null) {
            return '';
        }

        return mb_substr(trim((string) preg_replace('/\s+/u', ' ', $clean)), 0, self::SEARCH_MAX_LENGTH);
    }

    /**
     * State filter yang SUDAH tervalidasi.
     *
     * @param  array<string, array<string, string>>  $options  kelompok => [namaLokal => label]
     * @return array{q: string, kategori: string[], kulit: string[], concern: string[], brand: string[], harga: string[]}
     */
    private function state(Request $request, array $options): array
    {
        $state = ['q' => self::searchTerm($request->query('q'))];

        foreach (array_keys(self::GROUPS) as $key) {
            $state[$key] = self::pick($request->query($key), array_keys($options[$key] ?? []));
        }
        $state[self::PRICE_GROUP] = self::pick($request->query(self::PRICE_GROUP), array_keys(self::PRICE_RANGES));

        return $state;
    }

    /**
     * Ambil nilai yang ada di whitelist saja. Urutan mengikuti whitelist (bukan input)
     * supaya URL selalu konsisten dan duplikat hilang.
     *
     * @return string[]
     */
    private static function pick(mixed $raw, array $allowed): array
    {
        $given = array_filter((array) $raw, 'is_string');

        return array_values(array_intersect($allowed, $given));
    }

    private function activeCount(array $state): int
    {
        $n = count($state[self::PRICE_GROUP]);
        foreach (array_keys(self::GROUPS) as $key) {
            $n += count($state[$key]);
        }

        return $n;
    }

    /** URL katalog untuk sebuah state; format sama dengan form GET (brand[]=…&harga[]=…). */
    public static function url(array $state): string
    {
        $params = [];
        if (($state['q'] ?? '') !== '') {
            $params['q'] = $state['q'];
        }
        foreach ([...array_keys(self::GROUPS), self::PRICE_GROUP] as $key) {
            if (! empty($state[$key])) {
                $params[$key] = array_values($state[$key]);
            }
        }

        $query = preg_replace('/%5B\d+%5D=/', '%5B%5D=', http_build_query($params, '', '&', PHP_QUERY_RFC1738));

        return route('katalog').($query === '' ? '' : '?'.$query);
    }

    /** Chip filter aktif, masing-masing dengan URL untuk menghapus chip itu saja. */
    private function chips(array $state, array $options): array
    {
        $chips = [];

        if ($state['q'] !== '') {
            $chips[] = ['group' => 'Pencarian', 'label' => '“'.$state['q'].'”', 'url' => self::url([...$state, 'q' => ''])];
        }

        foreach (self::GROUPS as $key => $group) {
            foreach ($state[$key] as $value) {
                $chips[] = [
                    'group' => $group['title'],
                    'label' => $options[$key][$value] ?? Vocabulary::humanize($value),
                    'url' => self::url([...$state, $key => array_values(array_diff($state[$key], [$value]))]),
                ];
            }
        }

        foreach ($state[self::PRICE_GROUP] as $value) {
            $chips[] = [
                'group' => 'Harga',
                'label' => self::PRICE_RANGES[$value]['label'],
                'url' => self::url([...$state, self::PRICE_GROUP => array_values(array_diff($state[self::PRICE_GROUP], [$value]))]),
            ];
        }

        return $chips;
    }

    /* ------------------------------------------------------------------
     | Penyusunan query
     * ------------------------------------------------------------------ */

    /**
     * Blok filter SPARQL: search + semua kelompok aktif, kecuali $except.
     * OR dalam kelompok = IN (…); AND antar kelompok = beberapa FILTER EXISTS.
     */
    public function filterBlock(array $state, ?string $except = null): string
    {
        $lines = [];

        if ($state['q'] !== '') {
            $lines[] = '  FILTER(CONTAINS(LCASE(STR(?nm)), LCASE('.SparqlService::literal($state['q']).')))';
        }

        foreach (self::GROUPS as $key => $group) {
            if ($key === $except || empty($state[$key])) {
                continue;
            }
            $iris = implode(', ', array_map(fn (string $local) => $this->sparql->iri($local), $state[$key]));
            $var = '?f_'.$key;
            $lines[] = "  FILTER EXISTS { ?p ex:{$group['property']} {$var} . FILTER({$var} IN ({$iris})) }";
        }

        if ($except !== self::PRICE_GROUP && ! empty($state[self::PRICE_GROUP])) {
            $conditions = implode(' || ', array_map(
                fn (string $key) => self::priceCondition('?f_harga', self::PRICE_RANGES[$key]),
                $state[self::PRICE_GROUP]
            ));
            $lines[] = "  FILTER EXISTS { ?p ex:estimatedPrice ?f_harga . FILTER(isNumeric(?f_harga) && ({$conditions})) }";
        }

        return implode("\n", $lines);
    }

    /** (?v >= min && ?v < max) — angka berasal dari konstanta, bukan input. */
    private static function priceCondition(string $var, array $range): string
    {
        $parts = [];
        if ($range['min'] !== null) {
            $parts[] = $var.' >= '.(int) $range['min'];
        }
        if ($range['max'] !== null) {
            $parts[] = $var.' < '.(int) $range['max'];
        }

        return '('.implode(' && ', $parts ?: ['true']).')';
    }

    /** IF(cond1, "lt50", IF(cond2, "50-100", … "")) untuk memetakan harga ke kunci rentang. */
    private static function priceBucketExpression(string $var): string
    {
        $expr = '""';
        foreach (array_reverse(self::PRICE_RANGES, true) as $key => $range) {
            $expr = 'IF('.self::priceCondition($var, $range).', '.SparqlService::literal($key).', '.$expr.')';
        }

        return $expr;
    }

    /* ------------------------------------------------------------------
     | Data dari ontologi
     * ------------------------------------------------------------------ */

    private function total(): int
    {
        $rows = $this->sparql->select(self::TOTAL_QUERY);

        return isset($rows[0]) ? (int) SparqlService::value($rows[0], 'total') : 0;
    }

    /** @return array<string, array<string, string>> kelompok => [namaLokal => label], urut label */
    private function options(): array
    {
        $options = array_fill_keys(array_keys(self::GROUPS), []);
        $ns = $this->sparql->namespace();

        foreach ($this->sparql->select(self::OPTIONS_QUERY) as $row) {
            $group = (string) SparqlService::value($row, 'group');
            $iri = (string) SparqlService::value($row, 'opt');
            $local = SparqlService::local($iri);

            // Hanya IRI di namespace ex: dengan nama lokal aman yang bisa dipakai kembali sebagai filter.
            if (! isset($options[$group]) || $iri !== $ns.$local || ! SparqlService::isSafeLocalName($local)) {
                continue;
            }

            $options[$group][$local] = SparqlService::value($row, 'labelId')
                ?? SparqlService::value($row, 'labelPlain')
                ?? SparqlService::value($row, 'labelAny')
                ?? Vocabulary::humanize($local);
        }

        foreach ($options as &$list) {
            uasort($list, 'strnatcasecmp');
        }
        unset($list);

        return $options;
    }

    /** Facet semua kelompok: [kelompok => ['title', 'options' => [[value, label, count, selected]]]]. */
    private function facets(array $state, array $options): array
    {
        $facets = [];

        foreach (self::GROUPS as $key => $group) {
            $query = strtr(self::FACET_QUERY, [
                '{{GROUP}}' => $key,
                '{{PROPERTY}}' => 'ex:'.$group['property'],
                '{{FILTERS}}' => $this->filterBlock($state, $key),
            ]);
            $counts = $this->counts($query);

            $facets[$key] = ['title' => $group['title'], 'options' => []];
            foreach ($options[$key] as $value => $label) {
                $facets[$key]['options'][] = [
                    'value' => $value,
                    'label' => $label,
                    'count' => $counts[$value] ?? 0,
                    'selected' => in_array($value, $state[$key], true),
                ];
            }
        }

        $query = strtr(self::PRICE_FACET_QUERY, [
            '{{FILTERS}}' => $this->filterBlock($state, self::PRICE_GROUP),
            '{{BUCKET}}' => self::priceBucketExpression('?price'),
        ]);
        $counts = $this->counts($query);

        $facets[self::PRICE_GROUP] = ['title' => self::PRICE_TITLE, 'options' => []];
        foreach (self::PRICE_RANGES as $value => $range) {
            $facets[self::PRICE_GROUP]['options'][] = [
                'value' => $value,
                'label' => $range['label'],
                'count' => $counts[$value] ?? 0,
                'selected' => in_array($value, $state[self::PRICE_GROUP], true),
            ];
        }

        return $facets;
    }

    /** @return array<string, int> nilai (nama lokal / kunci harga) => jumlah produk */
    private function counts(string $query): array
    {
        $counts = [];
        foreach ($this->sparql->select($query) as $row) {
            $opt = (string) SparqlService::value($row, 'opt');
            $key = str_contains($opt, '#') || str_contains($opt, '/') ? SparqlService::local($opt) : $opt;
            $counts[$key] = (int) SparqlService::value($row, 'n');
        }

        return $counts;
    }

    /** @return array<int, array<string, mixed>> */
    private function products(array $state): array
    {
        $split = fn (?string $s) => $s === null || $s === '' ? [] : array_values(array_unique(explode('|', $s)));
        $query = str_replace('{{FILTERS}}', $this->filterBlock($state), self::PRODUCTS_QUERY);
        $products = [];

        foreach ($this->sparql->select($query) as $row) {
            $v = fn (string $var) => SparqlService::value($row, $var);
            $id = SparqlService::local((string) $v('p'));
            $sourceId = $v('sourceId') ?? $id;
            $brand = $v('brandLabel') ?? ($v('brandIri') ? Vocabulary::humanize(SparqlService::local($v('brandIri'))) : null);

            $products[] = [
                'id' => $id,
                'source_id' => $sourceId,
                'name' => (string) $v('name'),
                'brand' => $brand,
                'category' => $v('categoryLabel') ?? ($v('categoryIri') ? Vocabulary::humanize(SparqlService::local($v('categoryIri'))) : null),
                'price' => is_numeric($v('price')) ? (int) $v('price') : null,
                'skin_types' => $split($v('skins')),
                'concerns' => $split($v('concerns')),
                'image' => $this->imageFor($brand, $sourceId) ?? $this->ontologyImage($v('image')),
                'detail_url' => preg_match('/^[A-Z]{3}-\d{4}$/', $id) === 1 ? route('produk.detail', ['id' => $id]) : null,
            ];
        }

        // Urutan tetap (bukan fitur sorting): mengikuti sourceProductId agar produk per brand berdekatan.
        usort($products, fn ($a, $b) => strnatcmp($a['source_id'], $b['source_id']));

        return $products;
    }

    /* ------------------------------------------------------------------
     | Foto produk
     * ------------------------------------------------------------------ */

    /** Folder brand: nama brand huruf kecil, mis. "Kahf" -> kahf, "TAVI" -> tavi. */
    public static function brandFolder(?string $brand): ?string
    {
        $slug = Str::slug((string) $brand);

        return $slug === '' ? null : $slug;
    }

    /** URL foto lokal bila file {sourceProductId}.{ext} sudah ditaruh di folder brand. */
    private function imageFor(?string $brand, string $sourceId): ?string
    {
        $folder = self::brandFolder($brand);
        if ($folder === null || ! SparqlService::isSafeLocalName($sourceId)) {
            return null;
        }

        foreach (self::IMAGE_EXTENSIONS as $ext) {
            $relative = self::IMAGE_DIR."/{$folder}/{$sourceId}.{$ext}";
            if (is_file(public_path($relative))) {
                return asset($relative);
            }
        }

        return null;
    }

    /** Cadangan: schema:image dari ontologi, hanya bila berupa URL http(s). */
    private function ontologyImage(?string $url): ?string
    {
        return $url !== null && preg_match('#^https?://#i', $url) === 1 ? $url : null;
    }
}
