<?php

namespace App\Http\Controllers;

use App\Services\Recommendation\Vocabulary;
use App\Services\SparqlException;
use App\Services\SparqlService;
use Illuminate\Http\Request;

/**
 * Orang 5 — Direktori bahan aktif (/bahan) dan detail bahan (/bahan/{id}).
 *
 * Semua data berasal dari ontologi lewat SparqlService. Ontologi yang dimuat saat ini hanya
 * mencatat untuk setiap ex:Ingredient: rdfs:label dan relasi ex:hasIngredient (Product -> Ingredient).
 * Deskripsi, fungsi, target concern, peringatan, kontraindikasi, dan konflik TIDAK ada di ontologi,
 * jadi halaman ini tidak menampilkan (dan tidak mengarang) bagian tersebut.
 *
 * ID bahan = nama lokal IRI di namespace ex:, mis. ex:Niacinamide -> /bahan/Niacinamide.
 * Dokumentasi query: docs/queries/bahan-daftar.rq dan docs/queries/bahan-detail.rq.
 */
class IngredientController extends Controller
{
    /** Semua bahan + label + jumlah produk yang mengandungnya. Label dipilih di PHP (@id -> tanpa bahasa -> apa pun). */
    public const LIST_QUERY = <<<'SPARQL'
# bahan:daftar
SELECT ?i
  (SAMPLE(?lid) AS ?labelId) (SAMPLE(?lplain) AS ?labelPlain) (SAMPLE(?lany) AS ?labelAny)
  (GROUP_CONCAT(DISTINCT STR(?lall); separator=" | ") AS ?allLabels)
  (COUNT(DISTINCT ?p) AS ?products)
WHERE {
  ?i a ex:Ingredient .
  FILTER(isIRI(?i))
  OPTIONAL { ?i rdfs:label ?lid FILTER(langMatches(lang(?lid), "id")) }
  OPTIONAL { ?i rdfs:label ?lplain FILTER(lang(?lplain) = "") }
  OPTIONAL { ?i rdfs:label ?lany }
  OPTIONAL { ?i rdfs:label ?lall }
  OPTIONAL { ?p a ex:Product ; ex:hasIngredient ?i }
}
GROUP BY ?i
SPARQL;

    /** Bahan yang namanya (label mana pun) memuat kata kunci; {{TERM}} = literal ter-escape. */
    public const SEARCH_QUERY = <<<'SPARQL'
# bahan:cari
SELECT DISTINCT ?i
WHERE {
  ?i a ex:Ingredient ;
     rdfs:label ?l .
  FILTER(CONTAINS(LCASE(STR(?l)), LCASE({{TERM}})))
}
SPARQL;

    /** Jumlah produk berbeda yang mengandung minimal satu bahan tercatat. */
    public const LINKED_PRODUCTS_QUERY = <<<'SPARQL'
# bahan:produk-terhubung
SELECT (COUNT(DISTINCT ?p) AS ?n)
WHERE {
  ?p a ex:Product ;
     ex:hasIngredient ?i .
  ?i a ex:Ingredient .
}
SPARQL;

    /**
     * Detail satu bahan: hanya mengembalikan baris bila IRI benar-benar ex:Ingredient
     * (ID produk / ID tak dikenal -> 0 baris -> 404). {{IRI}} dari SparqlService::iri().
     */
    public const DETAIL_QUERY = <<<'SPARQL'
# bahan:detail
SELECT ?label (LANG(?label) AS ?lang)
WHERE {
  {{IRI}} a ex:Ingredient .
  OPTIONAL { {{IRI}} rdfs:label ?label }
}
SPARQL;

    /** Produk yang mengandung bahan ini — proyeksi sama dengan CatalogController::PRODUCTS_QUERY. */
    public const PRODUCTS_QUERY = <<<'SPARQL'
# bahan:produk
SELECT ?p
  (SAMPLE(?sid) AS ?sourceId) (SAMPLE(?nm) AS ?name) (SAMPLE(?pr) AS ?price)
  (SAMPLE(?brand) AS ?brandIri) (SAMPLE(?brandL) AS ?brandLabel)
  (SAMPLE(?cat) AS ?categoryIri) (SAMPLE(?catL) AS ?categoryLabel)
  (SAMPLE(?img) AS ?image)
  (GROUP_CONCAT(DISTINCT ?skinL; separator="|") AS ?skins)
  (GROUP_CONCAT(DISTINCT ?concernL; separator="|") AS ?concerns)
WHERE {
  ?p a ex:Product ;
     ex:productName ?nm ;
     ex:hasIngredient {{IRI}} .
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
ORDER BY ASC(COALESCE(?sourceId, STR(?p))) ASC(STR(?p))
SPARQL;

    public function __construct(private SparqlService $sparql) {}

    /* ------------------------------------------------------------------
     | /bahan
     * ------------------------------------------------------------------ */

    public function index(Request $request)
    {
        $search = CatalogController::searchTerm($request->query('q'));
        $ingredients = [];
        $linkedProducts = null;
        $error = null;

        try {
            $ingredients = $this->ingredients();
            $linkedProducts = $this->linkedProducts();

            // Semua bahan tetap dirender (agar pencarian di browser bisa dilonggarkan tanpa muat ulang);
            // yang tidak cocok dengan pencarian server diberi tanda tersembunyi.
            if ($search !== '') {
                $matches = $this->searchIds($search);
                foreach ($ingredients as &$ingredient) {
                    $ingredient['match'] = isset($matches[$ingredient['id']]);
                }
                unset($ingredient);
            }
        } catch (SparqlException $e) {
            $error = $e->getMessage();
        }

        $letters = [];
        foreach ($ingredients as $ingredient) {
            $letters[$ingredient['letter']][] = $ingredient;
        }

        return view('user.bahan.index', [
            'ingredients' => $ingredients,
            'letters' => $letters,
            'alphabet' => [...range('A', 'Z'), ...(isset($letters['#']) ? ['#'] : [])],
            'visibleCount' => count(array_filter($ingredients, fn ($i) => $i['match'])),
            'maxProducts' => max([1, ...array_column($ingredients, 'products')]),
            'linkedProducts' => $linkedProducts,
            'search' => $search,
            'error' => $error,
        ]);
    }

    /** @return array<int, array{id: string, name: string, search: string, letter: string, products: int, url: string, match: bool}> urut nama */
    private function ingredients(): array
    {
        $ns = $this->sparql->namespace();
        $ingredients = [];

        foreach ($this->sparql->select(self::LIST_QUERY) as $row) {
            $iri = (string) SparqlService::value($row, 'i');
            $id = SparqlService::local($iri);
            // Hanya IRI ex: dengan nama lokal aman yang bisa dibuka lewat /bahan/{id}.
            if ($iri !== $ns.$id || ! SparqlService::isSafeLocalName($id)) {
                continue;
            }

            $name = SparqlService::value($row, 'labelId')
                ?? SparqlService::value($row, 'labelPlain')
                ?? SparqlService::value($row, 'labelAny')
                ?? Vocabulary::humanize($id);

            $ingredients[] = [
                'id' => $id,
                'name' => $name,
                // Teks yang dicocokkan pencarian di browser: semua label bahan (sama dengan SEARCH_QUERY).
                'search' => mb_strtolower(trim(SparqlService::value($row, 'allLabels') ?? $name)),
                'letter' => self::letterOf($name),
                'products' => (int) SparqlService::value($row, 'products'),
                'url' => route('bahan.detail', ['id' => $id]),
                'match' => true,
            ];
        }

        usort($ingredients, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']) ?: strcmp($a['id'], $b['id']));

        return $ingredients;
    }

    /** Huruf indeks A–Z dari nama bahan; selain huruf Latin masuk ke "#". */
    public static function letterOf(string $name): string
    {
        $first = strtoupper(substr(ltrim($name), 0, 1));

        return preg_match('/^[A-Z]$/', $first) === 1 ? $first : '#';
    }

    /** @return array<string, true> nama lokal bahan yang cocok dengan pencarian */
    private function searchIds(string $search): array
    {
        $ids = [];
        $query = str_replace('{{TERM}}', SparqlService::literal($search), self::SEARCH_QUERY);
        foreach ($this->sparql->select($query) as $row) {
            $ids[SparqlService::local((string) SparqlService::value($row, 'i'))] = true;
        }

        return $ids;
    }

    private function linkedProducts(): int
    {
        $rows = $this->sparql->select(self::LINKED_PRODUCTS_QUERY);

        return isset($rows[0]) ? (int) SparqlService::value($rows[0], 'n') : 0;
    }

    /* ------------------------------------------------------------------
     | /bahan/{id}
     * ------------------------------------------------------------------ */

    public function show(string $id)
    {
        abort_unless(SparqlService::isSafeLocalName($id), 404);

        try {
            $iri = $this->sparql->iri($id);
            $rows = $this->sparql->select(str_replace('{{IRI}}', $iri, self::DETAIL_QUERY));
            abort_if($rows === [], 404); // bukan ex:Ingredient (atau tidak ada di ontologi)

            $products = array_map(
                [CatalogController::class, 'productFromRow'],
                $this->sparql->select(str_replace('{{IRI}}', $iri, self::PRODUCTS_QUERY))
            );
        } catch (SparqlException $e) {
            return response()->view('user.bahan.show', [
                'ingredient' => null,
                'products' => [],
                'error' => $e->getMessage(),
            ], 503);
        }

        $brands = array_values(array_unique(array_filter(array_column($products, 'brand'))));
        $categories = array_values(array_unique(array_filter(array_column($products, 'category'))));
        sort($brands, SORT_NATURAL | SORT_FLAG_CASE);

        return view('user.bahan.show', [
            'ingredient' => [
                'id' => $id,
                'name' => self::pickLabel($rows) ?? Vocabulary::humanize($id),
            ],
            'products' => $products,
            'brands' => $brands,
            'categoryCount' => count($categories),
            'error' => null,
        ]);
    }

    /** Label: @id -> tanpa bahasa -> label apa pun (urutan tetap). */
    private static function pickLabel(array $rows): ?string
    {
        $byLang = ['id' => [], '' => [], 'other' => []];
        foreach ($rows as $row) {
            $label = SparqlService::value($row, 'label');
            if ($label === null) {
                continue;
            }
            $lang = strtolower((string) SparqlService::value($row, 'lang'));
            $key = str_starts_with($lang, 'id') ? 'id' : ($lang === '' ? '' : 'other');
            $byLang[$key][] = $label;
        }

        foreach ($byLang as $labels) {
            if ($labels !== []) {
                sort($labels);

                return $labels[0];
            }
        }

        return null;
    }
}
