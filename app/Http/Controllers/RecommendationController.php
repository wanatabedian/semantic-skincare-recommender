<?php

namespace App\Http\Controllers;

use App\Services\FusekiService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class RecommendationController extends Controller
{
    public function __construct(private FusekiService $fuseki) {}

    public function index(Request $request)
    {
        try {
            $options = $this->fuseki->options();
            $valid = fn ($group) => Rule::in(array_column($options[$group], 'value'));
            $criteria = $request->validate([
                'age' => ['nullable', 'string', $valid('age_groups')],
                'skin' => ['nullable', 'string', $valid('skin_types')],
                'category' => ['nullable', 'string', $valid('categories')],
                'ingredient' => ['nullable', 'string', $valid('ingredients')],
                'concern' => ['sometimes', 'array', 'max:2'],
                'concern.*' => ['string', 'distinct', $valid('concerns')],
                'price_min' => ['nullable', 'integer', 'min:0'],
                'price_max' => ['nullable', 'integer', 'gte:price_min'],
            ]);
            $where = '?product a ex:Product ; ex:productName ?name .';
            $where .= ' OPTIONAL { ?product ex:hasBrand ?brand } OPTIONAL { ?product ex:belongsToSeries ?series }';
            $where .= ' OPTIONAL { ?product ex:hasCategory ?category . OPTIONAL { ?category rdfs:label ?categoryLabel } } OPTIONAL { ?product ex:estimatedPrice ?price }';
            $where .= ' OPTIONAL { ?product ex:suitableForSkinType ?skin . OPTIONAL { ?skin rdfs:label ?skinLabel } } OPTIONAL { ?product ex:suitableForAgeGroup ?age . OPTIONAL { ?age rdfs:label ?ageLabel } }';
            $where .= ' OPTIONAL { ?product ex:hasIngredient ?ingredient . OPTIONAL { ?ingredient rdfs:label ?ingredientLabel } }';
            if (!empty($criteria['concern'])) {
                $ids = implode(' ', array_map(fn ($id) => 'ex:'.$id, $criteria['concern']));
                $where .= " ?product ex:treatsConcern ?concern . VALUES ?concern { {$ids} } OPTIONAL { ?concern rdfs:label ?concernLabel }";
            } else {
                $where .= ' OPTIONAL { ?product ex:treatsConcern ?concern . OPTIONAL { ?concern rdfs:label ?concernLabel } }';
            }
            $filters = [];
            if (!empty($criteria['age'])) $filters[] = '?age = ex:'.$criteria['age'];
            if (!empty($criteria['category'])) $filters[] = '?category = ex:'.$criteria['category'];
            if (!empty($criteria['skin'])) $filters[] = '?skin = ex:'.$criteria['skin'];
            if (!empty($criteria['ingredient'])) $filters[] = '?ingredient = ex:'.$criteria['ingredient'];
            if (isset($criteria['price_min'])) $filters[] = '?price >= '.(int) $criteria['price_min'];
            if (isset($criteria['price_max'])) $filters[] = '?price <= '.(int) $criteria['price_max'];
            foreach ($filters as $filter) $where .= ' FILTER('.$filter.')';
            $rows = $this->fuseki->query("SELECT DISTINCT ?product ?name ?brand ?series ?category ?categoryLabel ?price ?skin ?skinLabel ?age ?ageLabel ?concern ?concernLabel ?ingredient ?ingredientLabel WHERE { {$where} } ORDER BY ?name");
            $products = [];
            foreach ($rows as $row) {
                $id = $this->fuseki->local($row['product']['value']);
                $products[$id] ??= ['id' => $id, 'name' => $row['name']['value'], 'brand' => null, 'series' => null, 'category' => null, 'price' => null, 'skin_types' => [], 'age_groups' => [], 'concerns' => [], 'ingredients' => []];
                foreach (['brand' => 'brand', 'series' => 'series', 'category' => 'category', 'price' => 'price'] as $key => $out) if (isset($row[$key]['value'])) $products[$id][$out] = $key === 'price' ? (int) $row[$key]['value'] : $this->fuseki->label($row[$key]['value']);
                if (isset($row['skin']['value'])) $products[$id]['skin_types'][] = $row['skinLabel']['value'] ?? $this->fuseki->label($row['skin']['value']);
                if (isset($row['age']['value'])) $products[$id]['age_groups'][] = $row['ageLabel']['value'] ?? $this->fuseki->label($row['age']['value']);
                if (isset($row['ingredient']['value'])) $products[$id]['ingredients'][] = $row['ingredientLabel']['value'] ?? $this->fuseki->label($row['ingredient']['value']);
                if (isset($row['categoryLabel']['value'])) $products[$id]['category'] = $row['categoryLabel']['value'];
                if (isset($row['concern']['value'])) $products[$id]['concerns'][] = $row['concernLabel']['value'] ?? $this->fuseki->label($row['concern']['value']);
            }
            foreach ($products as &$product) foreach (['skin_types','age_groups','concerns','ingredients'] as $key) $product[$key] = array_values(array_unique($product[$key]));
            return view('user.rekomendasi', ['products' => array_values($products), 'criteria' => $criteria, 'error' => null]);
        } catch (RuntimeException $e) {
            return response()->view('user.rekomendasi', ['products' => [], 'criteria' => $request->query(), 'error' => $e->getMessage()], 503);
        }
    }
}
