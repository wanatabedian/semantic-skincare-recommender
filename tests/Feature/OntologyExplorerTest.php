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
}
