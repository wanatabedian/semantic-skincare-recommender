<?php

namespace Tests\Unit;

use App\Services\Ontology\SparqlPlaygroundGuard;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SparqlPlaygroundGuardTest extends TestCase
{
    private function prepare(string $query): array
    {
        return (new SparqlPlaygroundGuard)->prepare($query);
    }

    private function assertRejected(string $query, string $messagePart): void
    {
        try {
            $this->prepare($query);
            $this->fail("Query seharusnya ditolak: {$query}");
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString($messagePart, $e->getMessage());
        }
    }

    public function test_select_without_limit_gets_limit_200(): void
    {
        $result = $this->prepare('SELECT ?p WHERE { ?p a ex:Product }');

        $this->assertSame('select', $result['type']);
        $this->assertTrue($result['limitApplied']);
        $this->assertStringEndsWith("\nLIMIT 200", $result['query']);
    }

    public function test_select_with_small_limit_is_kept_as_is(): void
    {
        $query = 'SELECT ?p WHERE { ?p a ex:Product } LIMIT 50';
        $result = $this->prepare($query);

        $this->assertFalse($result['limitApplied']);
        $this->assertSame($query, $result['query']);
    }

    public function test_limit_exactly_200_is_allowed(): void
    {
        $this->assertFalse($this->prepare('SELECT * WHERE { ?s ?p ?o } LIMIT 200')['limitApplied']);
    }

    public function test_limit_above_200_is_rejected(): void
    {
        $this->assertRejected('SELECT * WHERE { ?s ?p ?o } LIMIT 500', 'LIMIT maksimal 200 (diminta: 500)');
        $this->assertRejected("select * where { ?s ?p ?o } offset 5 limit 201", 'LIMIT maksimal 200');
    }

    public function test_limit_inside_subquery_does_not_count_as_outer_limit(): void
    {
        $result = $this->prepare('SELECT ?p WHERE { { SELECT ?p WHERE { ?p a ex:Product } LIMIT 5000 } }');

        $this->assertTrue($result['limitApplied'], 'LIMIT terluar tetap wajib ditambahkan.');
        $this->assertStringEndsWith("\nLIMIT 200", $result['query']);
    }

    public function test_limit_in_comment_or_string_is_ignored(): void
    {
        $result = $this->prepare("SELECT ?p WHERE { ?p ex:productName \"LIMIT 5\" } # LIMIT 10");

        $this->assertTrue($result['limitApplied']);
        $this->assertStringEndsWith("# LIMIT 10\nLIMIT 200", $result['query']);
    }

    public function test_ask_is_accepted_without_limit(): void
    {
        $query = 'ASK { ?p ex:hasIngredient ex:Retinol }';
        $result = $this->prepare($query);

        $this->assertSame('ask', $result['type']);
        $this->assertFalse($result['limitApplied']);
        $this->assertSame($query, $result['query']);
    }

    public function test_prefix_and_base_prologue_are_allowed(): void
    {
        $result = $this->prepare("PREFIX owl: <http://www.w3.org/2002/07/owl#>\nBASE <http://x/>\nPREFIX : <http://y#>\nselect ?c where { ?c a owl:Class }");

        $this->assertSame('select', $result['type']);
    }

    public function test_keywords_inside_literals_iris_variables_and_prefixed_names_are_not_rejected(): void
    {
        $this->assertSame('select', $this->prepare(
            'SELECT ?insert WHERE { ?insert ex:productName "Insert Delete Drop Serum" ; ex:x <http://example.org/load/service> . ?insert ex:Copy ?add }'
        )['type']);
    }

    public static function forbiddenQueries(): array
    {
        return [
            'INSERT DATA' => ['INSERT DATA { ex:a ex:b "c" }', 'INSERT'],
            'DELETE WHERE' => ['DELETE WHERE { ?s ?p ?o }', 'DELETE'],
            'DELETE/INSERT' => ['DELETE { ?s ?p ?o } INSERT { ?s ?p 1 } WHERE { ?s ?p ?o }', 'DELETE'],
            'UPDATE' => ['UPDATE ex:x', 'UPDATE'],
            'LOAD' => ['LOAD <http://evil.example/data.ttl>', 'LOAD'],
            'CLEAR' => ['CLEAR ALL', 'CLEAR'],
            'CREATE' => ['CREATE GRAPH <http://x>', 'CREATE'],
            'DROP' => ['DROP DEFAULT', 'DROP'],
            'MOVE' => ['MOVE DEFAULT TO <http://x>', 'MOVE'],
            'COPY' => ['COPY DEFAULT TO <http://x>', 'COPY'],
            'ADD' => ['ADD DEFAULT TO <http://x>', 'ADD'],
            'SERVICE in SELECT' => ['SELECT * WHERE { SERVICE <http://dbpedia.org/sparql> { ?s ?p ?o } }', 'SERVICE'],
            'FROM in SELECT' => ['SELECT * FROM <http://evil.example/g> WHERE { ?s ?p ?o }', 'FROM'],
            'update after prefix' => ["PREFIX ex: <http://x#>\nINSERT DATA { ex:a ex:b ex:c }", 'INSERT'],
            'select then insert' => ['SELECT * WHERE { ?s ?p ?o } ; INSERT DATA { ex:a ex:b ex:c }', 'INSERT'],
            'lowercase' => ['insert data { ex:a ex:b ex:c }', 'INSERT'],
        ];
    }

    #[DataProvider('forbiddenQueries')]
    public function test_update_and_federation_operations_are_rejected(string $query, string $keyword): void
    {
        $this->assertRejected($query, "Operasi {$keyword} tidak diizinkan");
    }

    public function test_construct_and_describe_are_not_supported(): void
    {
        $this->assertRejected('CONSTRUCT { ?s ?p ?o } WHERE { ?s ?p ?o }', 'Jenis query CONSTRUCT tidak didukung');
        $this->assertRejected('DESCRIBE ex:LAB-0054', 'Jenis query DESCRIBE tidak didukung');
    }

    public function test_empty_or_comment_only_query_is_rejected(): void
    {
        $this->assertRejected('', 'Query tidak boleh kosong');
        $this->assertRejected("   \n\t ", 'Query tidak boleh kosong');
        $this->assertRejected('# hanya komentar', 'Query tidak boleh kosong');
    }

    public function test_query_that_is_too_long_is_rejected(): void
    {
        $this->assertRejected('SELECT * WHERE { ?s ?p ?o } #'.str_repeat('x', 5000), 'Query terlalu panjang');
    }

    public function test_trailing_values_without_limit_requires_explicit_limit(): void
    {
        $this->assertRejected('SELECT ?p WHERE { ?p a ?c } VALUES ?c { ex:Product }', 'wajib mencantumkan LIMIT');
    }
}
