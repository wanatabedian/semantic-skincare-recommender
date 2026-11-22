<?php

namespace App\Http\Controllers;

use App\Services\CleanOntologyService;
use App\Services\Ontology\SparqlPlaygroundGuard;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;

class OntologyController extends Controller
{
    protected CleanOntologyService $ontologyService;

    public function __construct(CleanOntologyService $ontologyService)
    {
        $this->ontologyService = $ontologyService;
    }

    // Dashboard (already implemented, kept unchanged)
    public function index()
    {
        $stats = [];
        $stats['triples'] = $this->countTriples();
        $stats['products'] = $this->countClass('ex:Product');
        $stats['classes'] = 8;
        $stats['individuals'] = [
            'Product' => $stats['products'],
            'Brand' => $this->countClass('ex:Brand'),
            'Category' => $this->countClass('ex:Category'),
            'ProductSeries' => $this->countClass('ex:ProductSeries'),
            'AgeGroup' => $this->countClass('ex:AgeGroup'),
            'SkinType' => $this->countClass('ex:SkinType'),
            'SkinConcern' => $this->countClass('ex:SkinConcern'),
            'Ingredient' => $this->countClass('ex:Ingredient'),
        ];
        $stats['totalIndividuals'] = array_sum($stats['individuals']);
        $stats['objectProperties'] = $this->countProperties('object');
        $stats['datatypeProperties'] = $this->countProperties('datatype');
        // fetch object property hierarchy (domain -> range)
        $hierarchy = $this->objectPropertyHierarchy();
        $graphProducts = $this->graphProductOptions();
        $examples = self::PLAYGROUND_EXAMPLES;
        $playground = [
            'maxResults' => SparqlPlaygroundGuard::MAX_RESULTS,
            'timeout' => (int) SparqlPlaygroundGuard::TIMEOUT_SECONDS,
        ];

        return view('ontology.dashboard', compact('stats', 'hierarchy', 'graphProducts', 'examples', 'playground'));
    }

    /** Daftar produk untuk pemilih "graf satu produk". */
    protected function graphProductOptions(): array
    {
        $rows = $this->ontologyService->query(
            'SELECT ?product ?name WHERE { ?product a ex:Product ; ex:productName ?name . } ORDER BY ?name'
        );

        $options = [];
        foreach ($rows as $row) {
            $iri = $row['product']['value'] ?? null;
            if (! is_string($iri) || $iri === '') {
                continue;
            }
            $id = $this->ontologyService->local($iri);
            $options[$id] = ['id' => $id, 'name' => $row['name']['value'] ?? $id];
        }

        return array_values($options);
    }

    public function download()
    {
        $path = base_path('ontology/skincare_ontology_clean.ttl');
        if (!file_exists($path)) {
            abort(404, 'File not found');
        }
        return response()->download($path, 'skincare_ontology_clean.ttl');
    }

    /** SPARQL Playground: hanya SELECT/ASK, maks. 200 baris, timeout 5 detik. */
    public function sparql(Request $request, SparqlPlaygroundGuard $guard)
    {
        $back = redirect()->to(route('ontology.dashboard').'#sparql-playground')->withInput($request->only('query'));

        try {
            $prepared = $guard->prepare(is_string($request->input('query')) ? $request->input('query') : null);
        } catch (InvalidArgumentException $e) {
            return $back->with('sparqlError', $e->getMessage());
        }

        try {
            $result = $this->ontologyService->playground(
                $prepared['query'],
                $prepared['type'],
                SparqlPlaygroundGuard::TIMEOUT_SECONDS
            );
        } catch (RuntimeException $e) {
            return $back->with('sparqlError', $e->getMessage());
        }

        if ($result['type'] === 'ask') {
            return $back->with('sparqlResult', ['type' => 'ask', 'boolean' => $result['boolean']]);
        }

        $bindings = array_values($result['bindings']);

        return $back->with('sparqlResult', [
            'type' => 'select',
            'vars' => $result['vars'],
            'rows' => array_slice($bindings, 0, SparqlPlaygroundGuard::MAX_RESULTS),
            'truncated' => count($bindings) > SparqlPlaygroundGuard::MAX_RESULTS,
            'limitApplied' => $prepared['limitApplied'],
        ]);
    }

    /** Contoh query Playground; semua kelas/properti/individu ada di ontologi CLEAN. */
    public const PLAYGROUND_EXAMPLES = [
        'products' => [
            'label' => 'Daftar produk (nama & harga)',
            'query' => "SELECT ?product ?name ?price WHERE {\n  ?product a ex:Product ;\n           ex:productName ?name .\n  OPTIONAL { ?product ex:estimatedPrice ?price }\n}\nORDER BY ?name\nLIMIT 20",
        ],
        'ingredients' => [
            'label' => 'Daftar bahan aktif (Ingredient)',
            'query' => "SELECT ?ingredient ?label WHERE {\n  ?ingredient a ex:Ingredient .\n  OPTIONAL { ?ingredient rdfs:label ?label }\n}\nORDER BY ?label",
        ],
        'brands' => [
            'label' => 'Daftar merek & jumlah produk',
            'query' => "SELECT ?brand ?label (COUNT(?product) AS ?productCount) WHERE {\n  ?brand a ex:Brand .\n  OPTIONAL { ?brand rdfs:label ?label }\n  OPTIONAL { ?product ex:hasBrand ?brand }\n}\nGROUP BY ?brand ?label\nORDER BY ?label",
        ],
        'product_ingredients' => [
            'label' => 'Relasi produk – bahan (hasIngredient)',
            'query' => "SELECT ?name ?ingredientLabel WHERE {\n  ?product a ex:Product ;\n           ex:productName ?name ;\n           ex:hasIngredient ?ingredient .\n  OPTIONAL { ?ingredient rdfs:label ?ingredientLabel }\n}\nORDER BY ?name ?ingredientLabel\nLIMIT 50",
        ],
        'ask_retinol' => [
            'label' => 'ASK: adakah produk yang mengandung Retinol?',
            'query' => "ASK {\n  ?product a ex:Product ;\n           ex:hasIngredient ex:Retinol .\n}",
        ],
        'ask_teen_retinol' => [
            'label' => 'ASK: adakah produk Remaja yang mengandung Retinol? (cek Teen Lock)',
            'query' => "ASK {\n  ?product ex:suitableForAgeGroup ex:Teen ;\n           ex:hasIngredient ex:Retinol .\n}",
        ],
    ];

    public const ALLOWED_CLASSES = [
        'Product' => [
            'name' => 'Product',
            'label' => 'Product (Produk)',
            'description' => 'Produk perawatan kulit yang tersedia dalam katalog ontology.',
            'color' => '#2ca02c',
        ],
        'Brand' => [
            'name' => 'Brand',
            'label' => 'Brand (Merek)',
            'description' => 'Merek atau produsen produk skincare.',
            'color' => '#1f77b4',
        ],
        'Category' => [
            'name' => 'Category',
            'label' => 'Category (Kategori)',
            'description' => 'Kategori produk seperti Pembersih, Toner, Serum, dsb.',
            'color' => '#ff7f0e',
        ],
        'ProductSeries' => [
            'name' => 'ProductSeries',
            'label' => 'ProductSeries (Seri Produk)',
            'description' => 'Lini atau rangkaian produk spesifik dari suatu merek.',
            'color' => '#d62728',
        ],
        'AgeGroup' => [
            'name' => 'AgeGroup',
            'label' => 'AgeGroup (Kelompok Usia)',
            'description' => 'Target usia kesesuaian produk (Remaja, Dewasa).',
            'color' => '#9467bd',
        ],
        'SkinType' => [
            'name' => 'SkinType',
            'label' => 'SkinType (Jenis Kulit)',
            'description' => 'Tipe kulit pengguna seperti Kering, Normal, dan Berminyak.',
            'color' => '#8c564b',
        ],
        'SkinConcern' => [
            'name' => 'SkinConcern',
            'label' => 'SkinConcern (Masalah Kulit)',
            'description' => 'Kondisi atau masalah kulit yang ditargetkan produk.',
            'color' => '#e377c2',
        ],
        'Ingredient' => [
            'name' => 'Ingredient',
            'label' => 'Ingredient (Bahan Aktif)',
            'description' => 'Kandungan bahan aktif terstandarisasi dalam formulasi.',
            'color' => '#7f7f7f',
        ],
    ];

    /** Show list of individuals for a given class */
    public function classExplorer(Request $request, string $class)
    {
        // 1. Validate against allowlist
        if (!array_key_exists($class, self::ALLOWED_CLASSES)) {
            abort(404, "Kelas ontology '$class' tidak ditemukan.");
        }

        $classMeta = self::ALLOWED_CLASSES[$class];
        $search = trim((string) $request->query('search', ''));
        if (strlen($search) > 100) {
            $search = substr($search, 0, 100);
        }
        $escapedSearch = addcslashes($search, "\\'\"\n\r\t");

        $page = max((int) $request->query('page', 1), 1);
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $filterClause = '';
        if ($escapedSearch !== '') {
            $filterClause = "FILTER(CONTAINS(LCASE(STR(?label)), LCASE('$escapedSearch')) || CONTAINS(LCASE(STR(?instance)), LCASE('$escapedSearch')))";
        }

        $sparql = "SELECT ?instance ?label ?comment WHERE { "
            . "?instance a ex:$class . "
            . "OPTIONAL { ?instance rdfs:label ?rdfsLabel . } "
            . "OPTIONAL { ?instance ex:productName ?prodName . } "
            . "OPTIONAL { ?instance rdfs:comment ?comment . } "
            . "BIND(COALESCE(?rdfsLabel, ?prodName, '') AS ?explicitLabel) "
            . "BIND(IF(?explicitLabel != '', ?explicitLabel, REPLACE(STR(?instance), '^.*#', '')) AS ?label) "
            . $filterClause . " "
            . "} ORDER BY ?label ?instance LIMIT $perPage OFFSET $offset";

        $countSparql = "SELECT (COUNT(DISTINCT ?instance) AS ?c) WHERE { "
            . "?instance a ex:$class . "
            . "OPTIONAL { ?instance rdfs:label ?rdfsLabel . } "
            . "OPTIONAL { ?instance ex:productName ?prodName . } "
            . "BIND(COALESCE(?rdfsLabel, ?prodName, '') AS ?explicitLabel) "
            . "BIND(IF(?explicitLabel != '', ?explicitLabel, REPLACE(STR(?instance), '^.*#', '')) AS ?label) "
            . $filterClause . " "
            . "}";

        $results = $this->ontologyService->query($sparql);
        $countRes = $this->ontologyService->query($countSparql);
        $total = isset($countRes[0]['c']['value']) ? (int) $countRes[0]['c']['value'] : count($results);

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            ['path' => url()->current(), 'query' => $request->query()]
        );

        return view('ontology.class', [
            'class' => $class,
            'classMeta' => $classMeta,
            'individuals' => $paginator,
            'search' => $search,
            'total' => $total,
        ]);
    }

    /** Show details of a single individual */
    public function individualDetail(string $iri)
    {
        // Decode URL-encoded IRI
        $decoded = urldecode($iri);

        // Security validation for IRI
        if (!filter_var($decoded, FILTER_VALIDATE_URL) || preg_match('/[<>"\'\s]/', $decoded)) {
            abort(404, 'Format IRI tidak valid.');
        }

        $sparql = "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> "
            . "SELECT ?p ?o ?oLabel WHERE { "
            . "<$decoded> ?p ?o . "
            . "OPTIONAL { ?o rdfs:label ?oLabel . } "
            . "}";

        try {
            $triples = $this->ontologyService->query($sparql);
        } catch (\Exception $e) {
            abort(404, 'Gagal mengambil detail individu dari ontology.');
        }

        if (empty($triples)) {
            abort(404, 'Individu tidak ditemukan dalam ontology.');
        }

        // Process triples into structured fields
        $types = [];
        $mainClass = null;
        $label = null;
        $description = null;
        $datatypeProperties = [];
        $objectProperties = [];

        // For Product structured view
        $isProduct = false;
        $productData = [
            'productName' => null,
            'brand' => null,
            'category' => null,
            'series' => null,
            'ageGroups' => [],
            'skinTypes' => [],
            'concerns' => [],
            'ingredients' => [],
            'price' => null,
            'verificationStatus' => null,
            'sourceProductId' => null,
        ];

        foreach ($triples as $triple) {
            $p = $triple['p']['value'] ?? '';
            $o = $triple['o'] ?? [];
            $oValue = $o['value'] ?? '';
            $oType = $o['type'] ?? 'literal';
            $oLabel = $triple['oLabel']['value'] ?? null;

            $pLocal = $this->extractLocalName($p);

            // RDF type
            if ($p === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type') {
                $typeLocal = $this->extractLocalName($oValue);
                $types[] = $typeLocal;
                if (array_key_exists($typeLocal, self::ALLOWED_CLASSES)) {
                    $mainClass = $typeLocal;
                }
                if ($typeLocal === 'Product') {
                    $isProduct = true;
                }
                continue;
            }

            // Description / comment
            if ($p === 'http://www.w3.org/2000/01/rdf-schema#comment') {
                $description = $oValue;
            }

            // Label
            if ($p === 'http://www.w3.org/2000/01/rdf-schema#label' && !$label) {
                $label = $oValue;
            }
            if ($pLocal === 'productName') {
                $productData['productName'] = $oValue;
                $label = $oValue;
                $isProduct = true;
            }

            // Categorize as Datatype or Object Property
            if ($oType === 'uri') {
                $targetLabel = $oLabel ?? $this->extractLocalName($oValue);
                $objectProperties[] = [
                    'property' => $p,
                    'propertyLabel' => $pLocal,
                    'targetIri' => $oValue,
                    'targetLabel' => $targetLabel,
                ];

                // Product relations
                if ($pLocal === 'hasBrand') {
                    $productData['brand'] = ['iri' => $oValue, 'label' => $targetLabel];
                } elseif ($pLocal === 'hasCategory') {
                    $productData['category'] = ['iri' => $oValue, 'label' => $targetLabel];
                } elseif ($pLocal === 'belongsToSeries') {
                    $productData['series'] = ['iri' => $oValue, 'label' => $targetLabel];
                } elseif ($pLocal === 'suitableForAgeGroup') {
                    $productData['ageGroups'][] = ['iri' => $oValue, 'label' => $targetLabel];
                } elseif ($pLocal === 'suitableForSkinType') {
                    $productData['skinTypes'][] = ['iri' => $oValue, 'label' => $targetLabel];
                } elseif ($pLocal === 'treatsConcern') {
                    $productData['concerns'][] = ['iri' => $oValue, 'label' => $targetLabel];
                } elseif ($pLocal === 'hasIngredient') {
                    $productData['ingredients'][] = ['iri' => $oValue, 'label' => $targetLabel];
                }
            } else {
                $datatypeProperties[] = [
                    'property' => $p,
                    'propertyLabel' => $pLocal,
                    'value' => $oValue,
                    'datatype' => $o['datatype'] ?? null,
                ];

                if ($pLocal === 'estimatedPrice') {
                    $productData['price'] = (int) $oValue;
                } elseif ($pLocal === 'verificationStatus') {
                    $productData['verificationStatus'] = $oValue;
                } elseif ($pLocal === 'sourceProductId') {
                    $productData['sourceProductId'] = $oValue;
                }
            }
        }

        if (!$label) {
            $label = $this->extractLocalName($decoded);
        }

        return view('ontology.individual', [
            'iri' => $decoded,
            'triples' => $triples,
            'label' => $label,
            'description' => $description,
            'types' => $types,
            'mainClass' => $mainClass,
            'isProduct' => $isProduct,
            'productData' => $productData,
            'datatypeProperties' => $datatypeProperties,
            'objectProperties' => $objectProperties,
        ]);
    }

    /** Extract local fragment or basename from IRI */
    public function extractLocalName(string $iri): string
    {
        if (str_contains($iri, '#')) {
            return explode('#', $iri)[1];
        }
        $parts = explode('/', rtrim($iri, '/'));
        return end($parts);
    }

    // Helper methods (same as before)
    protected function countTriples(): int
    {
        $sparql = "SELECT (COUNT(*) AS ?c) WHERE { ?s ?p ?o }";
        $res = $this->ontologyService->query($sparql);
        return (int) ($res[0]['c']['value'] ?? 0);
    }

    protected function countClass(string $classIri): int
    {
        $sparql = "SELECT (COUNT(DISTINCT ?instance) AS ?c) WHERE { ?instance a $classIri . }";
        $res = $this->ontologyService->query($sparql);
        return (int) ($res[0]['c']['value'] ?? 0);
    }

    /**
     * Count object or datatype properties.
     * @param string $type 'object' or 'datatype'
     * @return int
     */
    protected function countProperties(string $type): int
    {
        $typeMap = [
            'object' => 'owl:ObjectProperty',
            'datatype' => 'owl:DatatypeProperty',
        ];
        if (!isset($typeMap[$type])) {
            return 0;
        }
        $propertyType = $typeMap[$type];
        $prefix = "PREFIX owl: <http://www.w3.org/2002/07/owl#> ";
        $sparql = $prefix . "SELECT (COUNT(DISTINCT ?p) AS ?c) WHERE { ?p a $propertyType . }";
        $res = $this->ontologyService->query($sparql);
        return (int) ($res[0]['c']['value'] ?? 0);
    }


    /** Retrieve object property hierarchy (domain -> range) */
    protected function objectPropertyHierarchy(): array
    {
        // Ensure owl prefix is available
        $prefix = "PREFIX owl: <http://www.w3.org/2002/07/owl#> ";
        $sparql = $prefix . "SELECT ?property ?domain ?range WHERE { "
            . "?property a owl:ObjectProperty . "
            . "OPTIONAL { ?property rdfs:domain ?domain . } "
            . "OPTIONAL { ?property rdfs:range ?range . } "
            . "} ORDER BY ?property";
        $results = $this->ontologyService->query($sparql);
        $hierarchy = [];
        foreach ($results as $row) {
            $hierarchy[] = [
                'property' => $row['property']['value'] ?? '',
                'domain'   => $row['domain']['value'] ?? '',
                'range'    => $row['range']['value'] ?? '',
            ];
        }
        return $hierarchy;
    }

    /**
     * Provide JSON data for the interactive knowledge graph.
     * Returns nodes (id, label, class) and edges (id, source, target, label).
     * Limits to 200 triples to keep payload small.
     */
    public function graphData(Request $request)
    {
        $product = $request->query('product');
        if (is_string($product) && trim($product) !== '') {
            return $this->productGraph(trim($product));
        }

        // ----- Nodes: instances of the eight main classes -----
        $nodeQuery = <<<SPARQL
SELECT ?s ?label ?type WHERE {
  ?s a ?type .
  FILTER(?type IN (ex:Brand, ex:Category, ex:Product, ex:ProductSeries,
                  ex:AgeGroup, ex:SkinType, ex:SkinConcern, ex:Ingredient))
  OPTIONAL { ?s rdfs:label ?label }
} LIMIT 200
SPARQL;
        $nodeResults = $this->ontologyService->query($nodeQuery);
        $nodes = [];
        foreach ($nodeResults as $row) {
            $iri   = $row['s']['value'] ?? '';
            $label = $row['label']['value'] ?? $this->ontologyService->local($iri);
            $typeIri = $row['type']['value'] ?? '';
            $class = substr($typeIri, strrpos($typeIri, '#') + 1);
            $nodes[$iri] = [
                'data' => [
                    'id'    => $iri,
                    'label' => $label,
                    'class' => $class,
                    'url'   => $this->individualUrl($iri),
                ],
            ];
        }

        // ----- Edges: object property triples (domain → range) -----
        $prefix = "PREFIX owl: <http://www.w3.org/2002/07/owl#> ";
        $edgeQuery = $prefix . <<<SPARQL
SELECT ?p ?s ?o WHERE {
  ?p a owl:ObjectProperty .
  ?s ?p ?o .
  FILTER(isIRI(?s) && isIRI(?o))
} LIMIT 200
SPARQL;
        $edgeResults = $this->ontologyService->query($edgeQuery);
        $edges = [];
        $edgeId = 0;
        foreach ($edgeResults as $row) {
            $source = $row['s']['value'] ?? '';
            $target = $row['o']['value'] ?? '';
            $prop   = $row['p']['value'] ?? '';
            if (!isset($nodes[$source]) || !isset($nodes[$target])) {
                continue; // keep consistency with displayed nodes
            }
            $edges[] = [
                'data' => [
                    'id'     => 'e' . $edgeId++,
                    'source' => $source,
                    'target' => $target,
                    'label'  => $this->ontologyService->local($prop),
                ],
            ];
        }

        return response()->json([
            'mode' => 'overview',
            'nodes' => array_values($nodes),
            'edges' => $edges,
        ]);
    }

    /**
     * Graf satu produk: node produk + semua individu yang dihubungkan langsung
     * lewat object property (brand, kategori, seri, umur, jenis kulit, keluhan, bahan).
     */
    protected function productGraph(string $id)
    {
        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/', $id)) {
            return response()->json(['message' => 'ID produk tidak valid.'], 422);
        }

        $productRows = $this->ontologyService->query(
            "SELECT ?product ?name WHERE { BIND(ex:$id AS ?product) ?product a ex:Product . OPTIONAL { ?product ex:productName ?name } } LIMIT 1"
        );
        $productIri = $productRows[0]['product']['value'] ?? null;
        if (! is_string($productIri) || $productIri === '') {
            return response()->json(['message' => "Produk '$id' tidak ditemukan di ontologi CLEAN."], 404);
        }

        $classes = implode(', ', array_map(fn ($c) => "ex:$c", array_keys(self::ALLOWED_CLASSES)));
        $neighbourRows = $this->ontologyService->query(
            "PREFIX owl: <http://www.w3.org/2002/07/owl#>\n"
            ."SELECT ?p ?o ?type ?label WHERE {\n"
            ."  ex:$id ?p ?o .\n"
            ."  ?p a owl:ObjectProperty .\n"
            ."  ?o a ?type .\n"
            ."  FILTER(?type IN ($classes))\n"
            ."  OPTIONAL { ?o rdfs:label ?label }\n"
            .'} ORDER BY ?p ?o'
        );

        $nodes = [$productIri => ['data' => [
            'id' => $productIri,
            'label' => $productRows[0]['name']['value'] ?? $this->ontologyService->local($productIri),
            'class' => 'Product',
            'url' => $this->individualUrl($productIri),
            'focus' => true,
        ]]];
        $edges = [];

        foreach ($neighbourRows as $row) {
            $target = $row['o']['value'] ?? '';
            $prop = $row['p']['value'] ?? '';
            $type = $row['type']['value'] ?? '';
            if ($target === '' || $prop === '' || ($row['o']['type'] ?? 'uri') !== 'uri') {
                continue;
            }

            $nodes[$target] ??= ['data' => [
                'id' => $target,
                'label' => $row['label']['value'] ?? $this->ontologyService->local($target),
                'class' => $this->extractLocalName($type),
                'url' => $this->individualUrl($target),
            ]];

            $edgeKey = $prop.'|'.$target;
            $edges[$edgeKey] ??= ['data' => [
                'id' => 'e'.count($edges),
                'source' => $productIri,
                'target' => $target,
                'label' => $this->ontologyService->local($prop),
            ]];
        }

        return response()->json([
            'mode' => 'product',
            'focus' => $productIri,
            'nodes' => array_values($nodes),
            'edges' => array_values($edges),
        ]);
    }

    private function individualUrl(string $iri): string
    {
        return route('ontology.individual', ['iri' => urlencode($iri)]);
    }
}
