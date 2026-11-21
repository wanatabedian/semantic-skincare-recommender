<?php

namespace App\Http\Controllers;

use App\Services\FusekiService;
use Illuminate\Http\Response;
use RuntimeException;

class ProductController extends Controller
{
    public function __construct(private FusekiService $fuseki) {}

    public function show(string $id)
    {
        abort_unless(preg_match('/^[A-Z]{3}-\d{4}$/', $id) === 1, 404);

        try {
            $rows = $this->fuseki->query(<<<SPARQL
                SELECT DISTINCT ?product ?name ?sourceId ?price ?brand ?brandLabel ?series ?seriesLabel
                    ?category ?categoryLabel ?skin ?skinLabel ?age ?ageLabel ?concern ?concernLabel ?ingredient ?ingredientLabel
                    ?claim ?claimLabel ?status ?note
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
                    OPTIONAL { ?product ex:hasClaim ?claim . OPTIONAL { ?claim rdfs:label ?claimLabel } }
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
            'ingredients' => [], 'claims' => [], 'verification_status' => null,
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
            foreach (['skin' => 'skin_types', 'age' => 'age_groups', 'concern' => 'concerns', 'ingredient' => 'ingredients', 'claim' => 'claims'] as $key => $field) {
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

        foreach (['skin_types', 'age_groups', 'concerns', 'ingredients', 'claims', 'verification_notes'] as $field) {
            $product[$field] = array_values(array_unique(array_filter($product[$field], static fn ($value) => $value !== '')));
        }

        return view('user.detail-produk', ['product' => $product, 'error' => null]);
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
