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
 * Tahap 2: daftar produk aktual dari ontologi (SPARQL) + product card + pemetaan foto.
 * Search, filter, facet, sorting, dan pagination dikerjakan pada tahap berikutnya.
 */
class CatalogController extends Controller
{
    /** Foto produk: public/images/products/{slug-brand}/{sourceProductId}.{ext} */
    public const IMAGE_DIR = 'images/products';

    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /** Query ini juga didokumentasikan di docs/queries/katalog-daftar-produk.rq */
    public const PRODUCTS_QUERY = <<<'SPARQL'
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

    public function __construct(private SparqlService $sparql) {}

    public function index(Request $request)
    {
        $products = [];
        $error = null;

        try {
            $products = $this->products();
        } catch (SparqlException $e) {
            $error = $e->getMessage();
        }

        return view('user.katalog', [
            'products' => $products,
            'productCount' => $error === null ? count($products) : null,
            'error' => $error,
            'search' => trim((string) $request->query('q', '')),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function products(): array
    {
        $split = fn (?string $s) => $s === null || $s === '' ? [] : array_values(array_unique(explode('|', $s)));
        $products = [];

        foreach ($this->sparql->select(self::PRODUCTS_QUERY) as $row) {
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
