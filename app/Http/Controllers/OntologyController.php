<?php

namespace App\Http\Controllers;

use App\Services\CleanOntologyService;
use Illuminate\Http\Request;
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
        $stats['objectProperties'] = $this->countProperties('object');
        $stats['datatypeProperties'] = $this->countProperties('datatype');
        // fetch object property hierarchy (domain -> range)
        $hierarchy = $this->objectPropertyHierarchy();
        return view('ontology.dashboard', compact('stats', 'hierarchy'));
    }

    public function download()
    {
        $path = base_path('ontology/skincare_ontology_clean.ttl');
        if (!file_exists($path)) {
            abort(404, 'File not found');
        }
        return response()->download($path, 'skincare_ontology_clean.ttl');
    }

    public function sparql(Request $request)
    {
        $query = $request->input('query');
        $upper = strtoupper($query);
        if (strpos($upper, 'INSERT') !== false || strpos($upper, 'DELETE') !== false || strpos($upper, 'UPDATE') !== false) {
            return redirect()->back()->with('sparqlError', 'Only SELECT queries are allowed.');
        }
        if (preg_match('/SERVICE\s+<[^>]+>/i', $query)) {
            return redirect()->back()->with('sparqlError', 'SERVICE clauses are not permitted.');
        }
        if (!preg_match('/\bLIMIT\b/i', $query)) {
            $query = $query . ' LIMIT 100';
        }
        try {
            $results = $this->ontologyService->query($query);
            return redirect()->back()->with('sparqlResult', $results);
        } catch (\Exception $e) {
            return redirect()->back()->with('sparqlError', $e->getMessage());
        }
    }

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
    public function graphData()
    {
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
            'nodes' => array_values($nodes),
            'edges' => $edges,
        ]);
    }
}
