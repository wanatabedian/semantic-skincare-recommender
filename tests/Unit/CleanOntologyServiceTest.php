<?php

namespace Tests\Unit;

use App\Services\CleanOntologyService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
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

    private function playgroundService(array $responses, array &$history = []): CleanOntologyService
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));

        // Klien default memakai timeout 15 detik, seperti klien service sungguhan.
        return new CleanOntologyService(new Client(['handler' => $stack, 'timeout' => 15, 'connect_timeout' => 5]));
    }

    public function test_playground_overrides_the_total_timeout_to_five_seconds_per_request(): void
    {
        $history = [];
        $service = $this->playgroundService([
            new Response(200, [], json_encode(['head' => ['vars' => ['p']], 'results' => ['bindings' => []]])),
            new Response(200, [], json_encode(['head' => ['vars' => ['p']], 'results' => ['bindings' => []]])),
        ], $history);

        $service->playground('SELECT ?p WHERE { ?p a ex:Product } LIMIT 1', 'select', 5.0);
        $service->query('SELECT ?p WHERE { ?p a ex:Product }');

        // Playground: total timeout 5 detik (bukan hanya koneksi).
        $this->assertSame(5.0, $history[0]['options']['timeout']);
        $this->assertLessThanOrEqual(5.0, $history[0]['options']['connect_timeout']);
        // query() untuk fitur lain tetap memakai timeout klien (15 detik).
        $this->assertSame(15, $history[1]['options']['timeout']);
    }

    public function test_playground_returns_select_vars_and_bindings(): void
    {
        $service = $this->playgroundService([
            new Response(200, [], json_encode([
                'head' => ['vars' => ['product', 'name']],
                'results' => ['bindings' => [
                    ['product' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#LAB-0054'], 'name' => ['type' => 'literal', 'value' => 'AcnePro Biome Acne Spot Gel']],
                ]],
            ])),
        ]);

        $result = $service->playground('SELECT ?product ?name WHERE { ?product ex:productName ?name } LIMIT 1', 'select', 5.0);

        $this->assertSame('select', $result['type']);
        $this->assertSame(['product', 'name'], $result['vars']);
        $this->assertSame('AcnePro Biome Acne Spot Gel', $result['bindings'][0]['name']['value']);
    }

    public function test_playground_parses_ask_boolean_results_including_false(): void
    {
        $history = [];
        $service = $this->playgroundService([
            new Response(200, [], '{"head":{},"boolean":true}'),
            new Response(200, [], '{"head":{},"boolean":false}'),
        ], $history);

        $this->assertSame(['type' => 'ask', 'boolean' => true], $service->playground('ASK { ?s ?p ?o }', 'ask', 5.0));
        $this->assertSame(['type' => 'ask', 'boolean' => false], $service->playground('ASK { ?p ex:suitableForAgeGroup ex:Teen ; ex:hasIngredient ex:Retinol }', 'ask', 5.0));

        parse_str((string) $history[1]['request']->getBody(), $form);
        $this->assertStringStartsWith('PREFIX ex: <', $form['query']);
        $this->assertStringEndsWith('ASK { ?p ex:suitableForAgeGroup ex:Teen ; ex:hasIngredient ex:Retinol }', $form['query']);
    }

    public function test_playground_rejects_an_ask_response_without_boolean(): void
    {
        $service = $this->playgroundService([
            new Response(200, [], '{"head":{"vars":[]},"results":{"bindings":[]}}'),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('nilai boolean tidak ditemukan');

        $service->playground('ASK { ?s ?p ?o }', 'ask', 5.0);
    }

    public function test_playground_reports_timeouts_with_a_clear_message(): void
    {
        $service = $this->playgroundService([
            new ConnectException(
                'cURL error 28: Operation timed out after 5001 milliseconds',
                new Request('POST', 'http://127.0.0.1:3030/aphrodite-clean/query'),
                null,
                ['errno' => 28]
            ),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Query melebihi batas waktu 5 detik');

        $service->playground('SELECT * WHERE { ?s ?p ?o } LIMIT 200', 'select', 5.0);
    }

    public function test_playground_shows_a_short_fuseki_syntax_error(): void
    {
        $service = $this->playgroundService([
            new Response(400, [], "Parse error: Encountered \" \"}\" at line 3\nstack trace line"),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Query ditolak oleh Fuseki: Parse error: Encountered');

        $service->playground('SELECT * WHERE { ?s ?p }', 'select', 5.0);
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
