<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\CleanOntologyService;
use Mockery;

class OntologyExplorerTest extends TestCase
{
    public function test_ontology_dashboard_route_returns_ok()
    {
        $mock = Mockery::mock(CleanOntologyService::class);
        $mock->shouldReceive('query')->andReturn([['c' => ['value' => '1488']]]);
        $this->app->instance(CleanOntologyService::class, $mock);

        $response = $this->get(route('ontology.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Ontology Explorer');
        // Verify 8 classes in Class Explorer section
        $response->assertSee('Product');
        $response->assertSee('Brand');
        $response->assertSee('Category');
        $response->assertSee('ProductSeries');
        $response->assertSee('AgeGroup');
        $response->assertSee('SkinType');
        $response->assertSee('SkinConcern');
        $response->assertSee('Ingredient');
    }

    public function test_download_route_returns_file()
    {
        $response = $this->get(route('ontology.download'));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_sparql_playground_rejects_update_queries()
    {
        $response = $this->post(route('ontology.sparql'), ['query' => 'INSERT DATA { <ex:a> <ex:b> "c" }']);
        $response->assertRedirect();
        $response->assertSessionHas('sparqlError');
    }

    public function test_class_explorer_returns_individuals()
    {
        $mock = Mockery::mock(CleanOntologyService::class);
        $mock->shouldReceive('query')->andReturn([
            ['instance' => ['value' => 'https://example.org/skincare-ontology#Prod1'], 'label' => ['value' => 'Prod1']]
        ]);
        $this->app->instance(CleanOntologyService::class, $mock);

        $response = $this->get(route('ontology.class', ['class' => 'Product']));
        $response->assertStatus(200);
        $response->assertSee('Prod1');
    }

    public function test_class_explorer_validates_class_allowlist()
    {
        $response = $this->get(route('ontology.class', ['class' => 'MaliciousClass']));
        $response->assertStatus(404);
    }

    public function test_class_explorer_handles_search_and_pagination()
    {
        $mock = Mockery::mock(CleanOntologyService::class);
        $mock->shouldReceive('query')->andReturn([
            ['instance' => ['value' => 'https://example.org/skincare-ontology#Serum1'], 'label' => ['value' => 'Brightening Serum']]
        ]);
        $this->app->instance(CleanOntologyService::class, $mock);

        $response = $this->get(route('ontology.class', ['class' => 'Product', 'search' => 'Brightening', 'page' => 1]));
        $response->assertStatus(200);
        $response->assertSee('Brightening Serum');
    }

    public function test_individual_detail_displays_product_attributes()
    {
        $mock = Mockery::mock(CleanOntologyService::class);
        $mock->shouldReceive('query')->andReturn([
            ['p' => ['value' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'], 'o' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#Product']],
            ['p' => ['value' => 'https://example.org/skincare-ontology#productName'], 'o' => ['type' => 'literal', 'value' => 'Super Serum']],
            ['p' => ['value' => 'https://example.org/skincare-ontology#hasBrand'], 'o' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#WardahBrand'], 'oLabel' => ['value' => 'Wardah']],
            ['p' => ['value' => 'https://example.org/skincare-ontology#hasCategory'], 'o' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#SerumCategory'], 'oLabel' => ['value' => 'Serum']],
            ['p' => ['value' => 'https://example.org/skincare-ontology#estimatedPrice'], 'o' => ['type' => 'literal', 'value' => '85000']],
            ['p' => ['value' => 'https://example.org/skincare-ontology#verificationStatus'], 'o' => ['type' => 'literal', 'value' => 'Terverifikasi BPOM']],
        ]);
        $this->app->instance(CleanOntologyService::class, $mock);

        $iri = urlencode('https://example.org/skincare-ontology#WAR-0001');
        $response = $this->get(route('ontology.individual', ['iri' => $iri]));

        $response->assertStatus(200);
        $response->assertSee('Super Serum');
        $response->assertSee('Wardah');
        $response->assertSee('Serum');
        $response->assertSee('85.000');
        $response->assertSee('Terverifikasi BPOM');
    }

    public function test_individual_detail_handles_incomplete_product_relations()
    {
        $mock = Mockery::mock(CleanOntologyService::class);
        $mock->shouldReceive('query')->andReturn([
            ['p' => ['value' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'], 'o' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#Product']],
            ['p' => ['value' => 'https://example.org/skincare-ontology#productName'], 'o' => ['type' => 'literal', 'value' => 'Simple Cream']],
        ]);
        $this->app->instance(CleanOntologyService::class, $mock);

        $iri = urlencode('https://example.org/skincare-ontology#SMP-0001');
        $response = $this->get(route('ontology.individual', ['iri' => $iri]));

        $response->assertStatus(200);
        $response->assertSee('Simple Cream');
        // Missing fields must show "Tidak tersedia"
        $response->assertSee('Tidak tersedia');
    }

    public function test_individual_detail_returns_404_when_not_found()
    {
        $mock = Mockery::mock(CleanOntologyService::class);
        $mock->shouldReceive('query')->andReturn([]);
        $this->app->instance(CleanOntologyService::class, $mock);

        $iri = urlencode('https://example.org/skincare-ontology#UnknownIndividual');
        $response = $this->get(route('ontology.individual', ['iri' => $iri]));

        $response->assertStatus(404);
    }

    public function test_individual_detail_rejects_invalid_iri()
    {
        $response = $this->get(route('ontology.individual', ['iri' => 'not-a-valid-url']));
        $response->assertStatus(404);
    }

    // ------------------------------------------------------------------
    // SPARQL Playground
    // ------------------------------------------------------------------

    private const EX = 'https://example.org/skincare-ontology#';

    private function mockService(): \Mockery\MockInterface
    {
        $mock = Mockery::mock(CleanOntologyService::class);
        $mock->shouldReceive('local')->andReturnUsing(fn ($iri) => substr($iri, strrpos($iri, '#') + 1));
        $this->app->instance(CleanOntologyService::class, $mock);

        return $mock;
    }

    public function test_playground_accepts_valid_select_with_five_second_timeout()
    {
        $mock = $this->mockService();
        $mock->shouldReceive('playground')->once()
            ->with('SELECT ?product WHERE { ?product a ex:Product } LIMIT 10', 'select', 5.0)
            ->andReturn([
                'type' => 'select',
                'vars' => ['product'],
                'bindings' => [['product' => ['type' => 'uri', 'value' => self::EX.'LAB-0054']]],
            ]);

        $response = $this->post(route('ontology.sparql'), ['query' => 'SELECT ?product WHERE { ?product a ex:Product } LIMIT 10']);

        $response->assertRedirect(route('ontology.dashboard').'#sparql-playground');
        $response->assertSessionMissing('sparqlError');
        $result = session('sparqlResult');
        $this->assertSame('select', $result['type']);
        $this->assertSame(['product'], $result['vars']);
        $this->assertCount(1, $result['rows']);
        $this->assertFalse($result['limitApplied']);
        $this->assertFalse($result['truncated']);
    }

    public function test_playground_accepts_ask_and_keeps_false_as_boolean()
    {
        $mock = $this->mockService();
        $query = 'ASK { ?p ex:suitableForAgeGroup ex:Teen ; ex:hasIngredient ex:Retinol }';
        $mock->shouldReceive('playground')->once()->with($query, 'ask', 5.0)
            ->andReturn(['type' => 'ask', 'boolean' => false]);

        $response = $this->post(route('ontology.sparql'), ['query' => $query]);

        $response->assertSessionMissing('sparqlError');
        $this->assertSame(['type' => 'ask', 'boolean' => false], session('sparqlResult'));
    }

    public function test_ask_result_is_rendered_as_boolean_on_dashboard()
    {
        $mock = $this->mockService();
        $mock->shouldReceive('query')->andReturn([['c' => ['value' => '1']]]);

        $this->withSession(['sparqlResult' => ['type' => 'ask', 'boolean' => false]])
            ->get(route('ontology.dashboard'))
            ->assertOk()
            ->assertSee('Hasil ASK')
            ->assertSee('pola tidak ditemukan di ontologi.');
    }

    public function test_select_result_is_rendered_as_table_on_dashboard()
    {
        $mock = $this->mockService();
        $mock->shouldReceive('query')->andReturn([['c' => ['value' => '1']]]);

        $this->withSession(['sparqlResult' => [
            'type' => 'select', 'vars' => ['name'], 'limitApplied' => true, 'truncated' => false,
            'rows' => [['name' => ['type' => 'literal', 'value' => '<b>AcnePro</b> Spot Gel']]],
        ]])
            ->get(route('ontology.dashboard'))
            ->assertOk()
            ->assertSee('?name')
            ->assertSee('&lt;b&gt;AcnePro&lt;/b&gt; Spot Gel', false) // nilai di-escape
            ->assertSee('LIMIT 200 ditambahkan otomatis.');
    }

    public static function rejectedQueries(): array
    {
        return [
            'INSERT' => ['INSERT DATA { ex:a ex:b "c" }', 'INSERT'],
            'DELETE' => ['DELETE WHERE { ?s ?p ?o }', 'DELETE'],
            'UPDATE' => ['UPDATE ex:a', 'UPDATE'],
            'LOAD' => ['LOAD <http://evil.example/data.ttl>', 'LOAD'],
            'SERVICE' => ['SELECT * WHERE { SERVICE <http://dbpedia.org/sparql> { ?s ?p ?o } }', 'SERVICE'],
            'CLEAR' => ['CLEAR ALL', 'CLEAR'],
            'DROP' => ['DROP GRAPH <http://x>', 'DROP'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('rejectedQueries')]
    public function test_playground_rejects_modifying_or_external_operations(string $query, string $keyword)
    {
        $mock = $this->mockService();
        $mock->shouldNotReceive('playground');
        $mock->shouldNotReceive('query');

        $response = $this->post(route('ontology.sparql'), ['query' => $query]);

        $response->assertRedirect();
        $response->assertSessionHas('sparqlError', fn ($msg) => str_contains($msg, "Operasi {$keyword} tidak diizinkan"));
        $response->assertSessionMissing('sparqlResult');
        $response->assertSessionHasInput('query', $query);
    }

    public function test_playground_rejects_empty_query()
    {
        $mock = $this->mockService();
        $mock->shouldNotReceive('playground');

        foreach (['', '   ', null] as $query) {
            $this->post(route('ontology.sparql'), ['query' => $query])
                ->assertSessionHas('sparqlError', 'Query tidak boleh kosong.');
        }
    }

    public function test_playground_rejects_limit_above_200()
    {
        $mock = $this->mockService();
        $mock->shouldNotReceive('playground');

        $this->post(route('ontology.sparql'), ['query' => 'SELECT * WHERE { ?s ?p ?o } LIMIT 1000'])
            ->assertSessionHas('sparqlError', 'LIMIT maksimal 200 (diminta: 1000).');
    }

    public function test_playground_adds_limit_200_and_caps_rows_when_limit_is_missing()
    {
        $mock = $this->mockService();
        $rows = array_fill(0, 250, ['s' => ['type' => 'uri', 'value' => self::EX.'X']]);
        $mock->shouldReceive('playground')->once()
            ->with("SELECT ?s WHERE { ?s ?p ?o }\nLIMIT 200", 'select', 5.0)
            ->andReturn(['type' => 'select', 'vars' => ['s'], 'bindings' => $rows]);

        $this->post(route('ontology.sparql'), ['query' => 'SELECT ?s WHERE { ?s ?p ?o }']);

        $result = session('sparqlResult');
        $this->assertCount(200, $result['rows']);
        $this->assertTrue($result['limitApplied']);
        $this->assertTrue($result['truncated']);
    }

    public function test_playground_shows_service_errors_such_as_timeout()
    {
        $mock = $this->mockService();
        $mock->shouldReceive('playground')->once()
            ->andThrow(new \RuntimeException('Query melebihi batas waktu 5 detik dan dihentikan. Sederhanakan query atau perkecil LIMIT.'));

        $this->post(route('ontology.sparql'), ['query' => 'SELECT * WHERE { ?s ?p ?o }'])
            ->assertSessionHas('sparqlError', fn ($msg) => str_contains($msg, 'batas waktu 5 detik'));
    }

    public function test_dashboard_lists_example_queries_from_the_clean_ontology()
    {
        $mock = $this->mockService();
        $mock->shouldReceive('query')->andReturn([['c' => ['value' => '1']]]);

        $response = $this->get(route('ontology.dashboard'))->assertOk();

        $response->assertSee('id="sparql-example"', false);
        foreach (\App\Http\Controllers\OntologyController::PLAYGROUND_EXAMPLES as $example) {
            $response->assertSee($example['label']);
        }
        $response->assertSee('ex:hasIngredient ex:Retinol');
        $response->assertSee('Hasil maksimal 200 baris', false);
        $response->assertSee('batas waktu 5 detik');
    }

    public function test_example_queries_only_use_terms_defined_in_the_clean_ontology()
    {
        $ttl = file_get_contents(base_path('ontology/skincare_ontology_clean.ttl'));
        preg_match_all('/^ex:([A-Za-z0-9_-]+)\s/m', $ttl, $defined);
        $defined = array_flip($defined[1]);

        foreach (\App\Http\Controllers\OntologyController::PLAYGROUND_EXAMPLES as $key => $example) {
            preg_match_all('/\bex:([A-Za-z0-9_-]+)/', $example['query'], $used);
            foreach ($used[1] as $term) {
                $this->assertArrayHasKey($term, $defined, "Contoh '$key' memakai ex:$term yang tidak ada di ontologi CLEAN.");
            }
            (new \App\Services\Ontology\SparqlPlaygroundGuard)->prepare($example['query']); // semua contoh lolos validasi
        }
    }

    public function test_download_serves_the_clean_ontology_file()
    {
        $this->get(route('ontology.download'))
            ->assertOk()
            ->assertDownload('skincare_ontology_clean.ttl');
    }

    // ------------------------------------------------------------------
    // Graf Cytoscape
    // ------------------------------------------------------------------

    public function test_overview_graph_returns_only_edges_between_returned_nodes()
    {
        $mock = $this->mockService();
        $mock->shouldReceive('query')->andReturnUsing(function (string $q) {
            if (str_contains($q, 'owl:ObjectProperty')) {
                return [
                    ['p' => ['value' => self::EX.'hasBrand'], 's' => ['value' => self::EX.'LAB-0054'], 'o' => ['value' => self::EX.'LABOREBrand']],
                    ['p' => ['value' => self::EX.'hasIngredient'], 's' => ['value' => self::EX.'LAB-0054'], 'o' => ['value' => self::EX.'MissingNode']],
                ];
            }

            return [
                ['s' => ['value' => self::EX.'LAB-0054'], 'type' => ['value' => self::EX.'Product']],
                ['s' => ['value' => self::EX.'LABOREBrand'], 'label' => ['value' => 'LABORE'], 'type' => ['value' => self::EX.'Brand']],
            ];
        });

        $json = $this->getJson(route('ontology.graph'))->assertOk()->assertJsonPath('mode', 'overview')->json();

        $ids = array_column(array_column($json['nodes'], 'data'), 'id');
        $this->assertCount(2, $json['nodes']);
        $this->assertCount(1, $json['edges'], 'Edge ke node yang tidak ada harus dibuang.');
        foreach ($json['edges'] as $edge) {
            $this->assertContains($edge['data']['source'], $ids);
            $this->assertContains($edge['data']['target'], $ids);
        }
        $this->assertSame('hasBrand', $json['edges'][0]['data']['label']);
        $this->assertSame('LABORE', $json['nodes'][1]['data']['label']);
    }

    public function test_product_graph_returns_the_product_and_its_neighbours()
    {
        $mock = $this->mockService();
        $queries = [];
        $mock->shouldReceive('query')->andReturnUsing(function (string $q) use (&$queries) {
            $queries[] = $q;
            if (str_contains($q, 'BIND(ex:LAB-0054 AS ?product)')) {
                return [['product' => ['value' => self::EX.'LAB-0054'], 'name' => ['value' => 'AcnePro Biome Acne Spot Gel']]];
            }

            return [
                ['p' => ['value' => self::EX.'hasBrand'], 'o' => ['type' => 'uri', 'value' => self::EX.'LABOREBrand'], 'type' => ['value' => self::EX.'Brand'], 'label' => ['value' => 'LABORE']],
                ['p' => ['value' => self::EX.'hasIngredient'], 'o' => ['type' => 'uri', 'value' => self::EX.'Retinol'], 'type' => ['value' => self::EX.'Ingredient'], 'label' => ['value' => 'Retinol']],
                ['p' => ['value' => self::EX.'suitableForAgeGroup'], 'o' => ['type' => 'uri', 'value' => self::EX.'Adult'], 'type' => ['value' => self::EX.'AgeGroup'], 'label' => ['value' => 'Dewasa']],
                // Seri tanpa label: jatuh ke nama lokal; baris dobel (mis. label ganda) tidak menggandakan node/edge.
                ['p' => ['value' => self::EX.'belongsToSeries'], 'o' => ['type' => 'uri', 'value' => self::EX.'AcneProSeries'], 'type' => ['value' => self::EX.'ProductSeries']],
                ['p' => ['value' => self::EX.'belongsToSeries'], 'o' => ['type' => 'uri', 'value' => self::EX.'AcneProSeries'], 'type' => ['value' => self::EX.'ProductSeries']],
            ];
        });

        $json = $this->getJson(route('ontology.graph', ['product' => 'LAB-0054']))
            ->assertOk()
            ->assertJsonPath('mode', 'product')
            ->assertJsonPath('focus', self::EX.'LAB-0054')
            ->json();

        $nodes = collect($json['nodes'])->pluck('data')->keyBy('id');
        $this->assertCount(5, $nodes);
        $this->assertTrue($nodes[self::EX.'LAB-0054']['focus']);
        $this->assertSame('AcnePro Biome Acne Spot Gel', $nodes[self::EX.'LAB-0054']['label']);
        $this->assertSame('Ingredient', $nodes[self::EX.'Retinol']['class']);
        $this->assertSame('Dewasa', $nodes[self::EX.'Adult']['label']);
        $this->assertSame('AcneProSeries', $nodes[self::EX.'AcneProSeries']['label']);
        $this->assertStringContainsString('/ontologi/individu/', $nodes[self::EX.'Retinol']['url']);

        $this->assertCount(4, $json['edges']);
        foreach ($json['edges'] as $edge) {
            $this->assertSame(self::EX.'LAB-0054', $edge['data']['source']);
            $this->assertTrue($nodes->has($edge['data']['target']));
        }
        $this->assertEqualsCanonicalizing(
            ['hasBrand', 'hasIngredient', 'suitableForAgeGroup', 'belongsToSeries'],
            array_column(array_column($json['edges'], 'data'), 'label')
        );
        $this->assertStringContainsString('ex:LAB-0054 ?p ?o', $queries[1]);
        $this->assertStringContainsString('?p a owl:ObjectProperty', $queries[1]);
    }

    public function test_product_graph_returns_404_for_unknown_product()
    {
        $mock = $this->mockService();
        $mock->shouldReceive('query')->once()->andReturn([]);

        $this->getJson(route('ontology.graph', ['product' => 'XXX-9999']))
            ->assertNotFound()
            ->assertJsonPath('message', "Produk 'XXX-9999' tidak ditemukan di ontologi CLEAN.");
    }

    public function test_product_graph_rejects_unsafe_product_ids()
    {
        $mock = $this->mockService();
        $mock->shouldNotReceive('query');

        foreach (['LAB-0054 } ; DROP ALL #', '<http://x>', '../etc', str_repeat('A', 80)] as $id) {
            $this->getJson(route('ontology.graph', ['product' => $id]))->assertStatus(422);
        }
    }

    public function test_dashboard_offers_product_selector_and_total_individuals()
    {
        $mock = $this->mockService();
        $mock->shouldReceive('query')->andReturnUsing(function (string $q) {
            if (str_contains($q, '?product ?name')) {
                return [['product' => ['value' => self::EX.'LAB-0054'], 'name' => ['value' => 'AcnePro Biome Acne Spot Gel']]];
            }

            return [['c' => ['value' => '2']]];
        });

        $this->get(route('ontology.dashboard'))
            ->assertOk()
            ->assertSee('id="graph-product"', false)
            ->assertSee('LAB-0054 — AcnePro Biome Acne Spot Gel')
            ->assertSee('Total individu (8 kelas utama)')
            ->assertSee('<td class="border px-2 py-1 font-semibold">16</td>', false); // 8 kelas x 2
    }
}
