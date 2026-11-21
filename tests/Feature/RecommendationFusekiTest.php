<?php

namespace Tests\Feature;

use App\Services\Recommendation\Criteria;
use App\Services\Recommendation\RecommendationEngine;
use App\Services\SparqlException;
use App\Services\SparqlService;
use Tests\TestCase;

/**
 * Uji integrasi ke Fuseki SUNGGUHAN (dataset berisi ontology/aphrodite-v2.ttl).
 * Otomatis di-skip kalau Fuseki tidak menyala.
 *
 * Endpoint bisa diganti lewat env FUSEKI_TEST_QUERY_URL, default sama dengan .env.
 *   php artisan test --filter=RecommendationFusekiTest
 */
class RecommendationFusekiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $url = env('FUSEKI_TEST_QUERY_URL') ?: config('services.fuseki.query_url');
        config(['services.fuseki.query_url' => $url, 'recommendation.cache_ttl' => 0]);

        try {
            $ok = app(SparqlService::class)->ask('ASK { ?p a ex:Product }', false);
        } catch (SparqlException) {
            $ok = false;
        }
        if (! $ok) {
            $this->markTestSkipped("Fuseki tidak tersedia / dataset kosong di {$url}.");
        }
    }

    private function recommend(array $input): array
    {
        $engine = app(RecommendationEngine::class);

        return $engine->recommend(Criteria::fromInput($input, $engine->vocabulary()));
    }

    /** Semua ID produk yang mengandung retinoid menurut ontologi. */
    private function retinoidProducts(): array
    {
        $engine = app(RecommendationEngine::class);
        $rows = app(SparqlService::class)->select(
            'SELECT DISTINCT ?p WHERE { ?p ex:hasIngredient ?ri . '.$engine->retinoidPattern('?ri').' }', false
        );

        return array_map(fn ($r) => SparqlService::local($r['p']['value']), $rows);
    }

    public function test_kuis_kosong_mengembalikan_semua_produk(): void
    {
        $count = app(SparqlService::class)->select('SELECT (COUNT(DISTINCT ?p) AS ?n) WHERE { ?p a ex:Product ; ex:productName ?x }', false);
        $result = $this->recommend([]);

        $this->assertSame('A', $result['plan']);
        $this->assertSame((int) $count[0]['n']['value'], $result['total']);
    }

    public function test_teen_tidak_pernah_mendapat_retinoid_di_plan_a_maupun_plan_b(): void
    {
        $retinoid = $this->retinoidProducts();
        $this->assertNotEmpty($retinoid, 'Ontologi seharusnya punya produk ber-retinol.');

        $planA = $this->recommend(['age' => 'Teen']);
        $planB = $this->recommend(['age' => 'Teen', 'skin' => 'Dry', 'concern' => ['AgingFineLines', 'Acne'], 'price_max' => 1000]);

        $this->assertSame('B', $planB['plan']);
        foreach ([$planA, $planB] as $result) {
            $this->assertSame([], array_intersect($retinoid, array_column($result['products'], 'id')));
        }
    }

    public function test_kombinasi_mustahil_memicu_plan_b_dan_tidak_kosong(): void
    {
        $result = $this->recommend(['age' => 'Adult', 'skin' => 'Dry', 'concern' => ['Acne', 'EyeAreaCare'], 'price_max' => 1000]);

        $this->assertSame('B', $result['plan']);
        $this->assertNotEmpty($result['products']);
        $this->assertContains('harga', $result['relaxed']);
    }

    public function test_contoh_query_dokumen_menghasilkan_alasan_lengkap(): void
    {
        $result = $this->recommend([
            'age' => 'Teen', 'skin' => 'Oily', 'concern' => ['Acne', 'ExcessOil'],
            'ingredient' => 'Niacinamide', 'price_min' => 50000, 'price_max' => 100000,
        ]);

        $this->assertNotEmpty($result['products']);
        $this->assertNotEmpty($result['products'][0]['reasons']);
        $this->assertNotNull($result['products'][0]['brand']);
    }
}
