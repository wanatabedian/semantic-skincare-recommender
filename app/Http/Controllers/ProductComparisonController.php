<?php

namespace App\Http\Controllers;

use App\Services\FusekiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ProductComparisonController extends Controller
{
    public function __construct(private FusekiService $fuseki) {}

    public function index() { return view('user.bandingkan'); }

    public function catalog(): JsonResponse
    {
        try {
            $rows = $this->fuseki->query('SELECT DISTINCT ?product ?name WHERE { ?product a ex:Product ; ex:productName ?name } ORDER BY ?name');
            return response()->json(['products' => array_map(fn ($row) => ['id' => $this->fuseki->local($row['product']['value']), 'name' => $row['name']['value']], $rows)]);
        } catch (RuntimeException $e) { return response()->json(['message' => $e->getMessage()], 503); }
    }

    public function products(Request $request): JsonResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:3'], 'ids.*' => ['required', 'regex:/^[A-Za-z0-9_-]+$/', 'distinct']])['ids'];
        $values = implode(' ', array_map(fn ($id) => 'ex:'.$id, $ids));
        try {
            $rows = $this->fuseki->query("SELECT DISTINCT ?product ?name ?brand ?series ?category ?categoryLabel ?price ?skin ?skinLabel ?age ?ageLabel ?concern ?concernLabel ?ingredient ?ingredientLabel ?claim WHERE {
                VALUES ?product { {$values} }
                ?product a ex:Product ; ex:productName ?name .
                OPTIONAL { ?product ex:hasBrand ?brand }
                OPTIONAL { ?product ex:belongsToSeries ?series }
                OPTIONAL { ?product ex:hasCategory ?category . OPTIONAL { ?category rdfs:label ?categoryLabel } }
                OPTIONAL { ?product ex:estimatedPrice ?price }
                OPTIONAL { ?product ex:suitableForSkinType ?skin . OPTIONAL { ?skin rdfs:label ?skinLabel } }
                OPTIONAL { ?product ex:suitableForAgeGroup ?age . OPTIONAL { ?age rdfs:label ?ageLabel } }
                OPTIONAL { ?product ex:treatsConcern ?concern . OPTIONAL { ?concern rdfs:label ?concernLabel } }
                OPTIONAL { ?product ex:hasIngredient ?ingredient . OPTIONAL { ?ingredient rdfs:label ?ingredientLabel } }
                OPTIONAL { ?product ex:claim ?claim }
            }");
            $products = [];
            foreach ($rows as $row) {
                $id = $this->fuseki->local($row['product']['value']);
                $products[$id] ??= ['id' => $id, 'name' => $row['name']['value'], 'brand' => null, 'series' => null, 'category' => null, 'price' => null, 'skin_types' => [], 'age_groups' => [], 'concerns' => [], 'ingredients' => [], 'claims' => []];
                foreach (['brand' => 'brand', 'series' => 'series'] as $key => $out) if (isset($row[$key]['value'])) $products[$id][$out] = $this->fuseki->label($row[$key]['value']);
                if (isset($row['category']['value'])) $products[$id]['category'] = $row['categoryLabel']['value'] ?? $this->fuseki->label($row['category']['value']);
                if (isset($row['price']['value'])) $products[$id]['price'] = (int) $row['price']['value'];
                if (isset($row['skin']['value'])) $products[$id]['skin_types'][] = $row['skinLabel']['value'] ?? $this->fuseki->label($row['skin']['value']);
                if (isset($row['age']['value'])) $products[$id]['age_groups'][] = $row['ageLabel']['value'] ?? $this->fuseki->label($row['age']['value']);
                if (isset($row['ingredient']['value'])) $products[$id]['ingredients'][] = $row['ingredientLabel']['value'] ?? $this->fuseki->label($row['ingredient']['value']);
                if (isset($row['claim']['value'])) $products[$id]['claims'][] = $row['claim']['value'];
                if (isset($row['concern']['value'])) $products[$id]['concerns'][] = $row['concernLabel']['value'] ?? $this->fuseki->local($row['concern']['value']);
            }
            return response()->json(['products' => array_values(array_map(function ($p) { foreach (['skin_types','age_groups','concerns','ingredients','claims'] as $key) $p[$key] = array_values(array_unique($p[$key])); return $p; }, $products))]);
        } catch (RuntimeException $e) { return response()->json(['message' => $e->getMessage()], 503); }
    }
}
