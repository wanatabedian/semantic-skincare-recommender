<?php

namespace Tests\Unit;

use App\Services\CleanOntologyService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use RuntimeException;
use Tests\TestCase;

class CleanOntologyServiceTest extends TestCase
{
    public function test_query_uses_the_clean_dataset_and_returns_select_bindings(): void
    {
        config([
            'services.fuseki.clean_query_url' => 'http://127.0.0.1:3030/aphrodite-clean/query',
            'services.fuseki.namespace' => 'https://example.org/skincare-ontology#',
        ]);

        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'application/sparql-results+json'], json_encode([
                'head' => ['vars' => ['product']],
                'results' => ['bindings' => [
                    ['product' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#CRY-0001']],
                ]],
            ], JSON_THROW_ON_ERROR)),
        ]));
        $stack->push(Middleware::history($history));

        $service = new CleanOntologyService(new Client(['handler' => $stack]));
        $bindings = $service->query('SELECT ?product WHERE { ?product a ex:Product }');

        $this->assertCount(1, $bindings);
        $this->assertSame(
            'https://example.org/skincare-ontology#CRY-0001',
            $bindings[0]['product']['value']
        );
        $this->assertCount(1, $history);
        $this->assertSame(
            'http://127.0.0.1:3030/aphrodite-clean/query',
            (string) $history[0]['request']->getUri()
        );
        $this->assertSame(
            'application/sparql-results+json',
            $history[0]['request']->getHeaderLine('Accept')
        );

        parse_str((string) $history[0]['request']->getBody(), $form);
        $this->assertStringStartsWith(
            "PREFIX ex: <https://example.org/skincare-ontology#>\nPREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\n",
            $form['query']
        );
    }

    public function test_query_rejects_a_response_without_sparql_bindings(): void
    {
        $service = new CleanOntologyService(new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(200, ['Content-Type' => 'application/json'], '{"unexpected":[]}'),
            ])),
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Format respons Fuseki Clean tidak sesuai');

        $service->query('SELECT ?product WHERE { ?product a ex:Product }');
    }

    public function test_query_wraps_endpoint_errors_in_a_runtime_exception(): void
    {
        $service = new CleanOntologyService(new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(503, [], 'Service unavailable'),
            ])),
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tidak dapat terhubung ke endpoint Fuseki Clean');

        $service->query('SELECT ?product WHERE { ?product a ex:Product }');
    }

    public function test_local_and_label_match_the_existing_fuseki_helpers(): void
    {
        $service = new CleanOntologyService;

        $this->assertSame(
            'OilySkinType',
            $service->local('https://example.org/skincare-ontology#OilySkinType')
        );
        $this->assertSame(
            'Moisturizer',
            $service->label('https://example.org/skincare-ontology#MoisturizerCategory')
        );
    }
}
