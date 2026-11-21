<?php

namespace Tests\Feature;

use App\Services\CleanOntologyService;
use RuntimeException;
use Tests\TestCase;

class SimilarProductsTest extends TestCase
{
    private const EX = 'https://example.org/skincare-ontology#';

    private function detailRows(): array
    {
        return [[
            'name' => ['type' => 'literal', 'value' => 'Produk Utama'],
            'brand' => ['type' => 'uri', 'value' => self::EX.'EminaBrand'],
            'brandLabel' => ['type' => 'literal', 'value' => 'Emina'],
        ]];
    }

    private function similarRow(string $id, string $name, string $brand = 'Wardah', string $concerns = 'Jerawat'): array
    {
        return [
            'product' => ['type' => 'uri', 'value' => self::EX.$id],
            'name' => ['type' => 'literal', 'value' => $name],
            'price' => ['type' => 'literal', 'value' => '45000'],
            'brandLabel' => ['type' => 'literal', 'value' => $brand],
            'categoryLabel' => ['type' => 'literal', 'value' => 'Serum & Essence'],
            'sharedConcernLabels' => ['type' => 'literal', 'value' => $concerns],
        ];
    }

    private function routineAgeRows(): array
    {
        return [
            ['ageGroup' => ['type' => 'uri', 'value' => self::EX.'Teen'], 'ageGroupLabel' => ['type' => 'literal', 'value' => 'Remaja']],
            ['ageGroup' => ['type' => 'uri', 'value' => self::EX.'Adult'], 'ageGroupLabel' => ['type' => 'literal', 'value' => 'Dewasa']],
        ];
    }

    /** Mock CleanOntologyService: detail produk, produk serupa, dan query Complete Your Segment. */
    private function mockFuseki(
        array $similarRows,
        ?callable $similarQueryCheck = null,
        array $routineRows = [],
        array $teenLockedIds = [],
    ): void {
        $fuseki = \Mockery::mock(CleanOntologyService::class)->makePartial();
        $fuseki->shouldReceive('query')->once()->withArgs(fn ($q) => str_contains($q, 'ex:sourceProductId'))->andReturn($this->detailRows());
        $fuseki->shouldReceive('query')->once()->withArgs(fn ($q) => str_contains($q, '?sharedConcernLabels') && ($similarQueryCheck === null || $similarQueryCheck($q)))
            ->andReturnUsing(function ($q) use ($similarRows, $teenLockedIds) {
                if (! str_contains($q, '?teenLockedIngredient')) {
                    return $similarRows;
                }

                return array_values(array_filter($similarRows, function ($row) use ($teenLockedIds) {
                    $iri = $row['product']['value'] ?? '';
                    $id = substr($iri, (strrpos($iri, '#') ?: -1) + 1);

                    return ! in_array($id, $teenLockedIds, true);
                }));
            });
        $fuseki->shouldReceive('query')->withArgs(fn ($q) => str_contains($q, '?routineCategory') || str_contains($q, '?sameSeriesFlag'))->andReturn($routineRows);
        $this->app->instance(CleanOntologyService::class, $fuseki);
    }

    public function test_similar_query_requires_shared_skin_shared_concern_and_different_brand(): void
    {
        $this->mockFuseki([], function (string $q) {
            return str_contains($q, 'BIND(ex:EMI-0026 AS ?source)')
                && str_contains($q, 'FILTER(?product != ?source)')
                && str_contains($q, 'FILTER NOT EXISTS { ?product ex:hasBrand ?sourceBrand }')
                && str_contains($q, '?product ex:suitableForSkinType ?sharedSkin')
                && str_contains($q, '?product ex:treatsConcern ?sharedConcern');
        });

        $this->get('/produk/EMI-0026')->assertOk();
    }

    public function test_shows_at_most_four_clickable_similar_products(): void
    {
        $this->mockFuseki([
            $this->similarRow('WAR-0001', 'Serupa Satu'),
            $this->similarRow('WAR-0002', 'Serupa Dua'),
            $this->similarRow('KAH-0003', 'Serupa Tiga', 'Kahf'),
            $this->similarRow('LAB-0004', 'Serupa Empat', 'LABORE'),
            $this->similarRow('TAV-0005', 'Serupa Lima', 'TAVI'),
        ]);

        $response = $this->get('/produk/EMI-0026')->assertOk();

        $similar = $response->viewData('similarProducts');
        $this->assertCount(4, $similar);
        $this->assertSame(['WAR-0001', 'WAR-0002', 'KAH-0003', 'LAB-0004'], array_column($similar, 'id'));
        $response->assertSee('Produk serupa')
            ->assertSee(route('produk.detail', 'WAR-0001'), false)
            ->assertSee('Serupa Empat')
            ->assertDontSee('Serupa Lima');
    }

    public function test_similar_products_apply_teen_lock_and_preserve_the_main_product(): void
    {
        $this->mockFuseki([
            $this->similarRow('WAR-0001', 'Produk aman'),
            $this->similarRow('WAR-0099', 'Produk Retinol'),
        ], function (string $q) {
            return str_contains($q, '?product ex:suitableForAgeGroup ex:Teen .')
                && str_contains($q, 'FILTER NOT EXISTS { ?product ex:hasIngredient ?teenLockedIngredient')
                && str_contains($q, '?teenLockedIngredient rdf:type/rdfs:subClassOf* <'.self::EX.'Retinoid>');
        }, $this->routineAgeRows(), ['WAR-0099']);

        $response = $this->get('/produk/EMI-0026?age=Teen')->assertOk();

        $this->assertSame(['WAR-0001'], array_column($response->viewData('similarProducts'), 'id'));
        $response->assertSee('Produk Utama')->assertSee('Produk aman')->assertDontSee('Produk Retinol');
    }

    public function test_adult_age_filters_similar_products_without_teen_lock(): void
    {
        $this->mockFuseki([$this->similarRow('WAR-0001', 'Produk Adult')], function (string $q) {
            return str_contains($q, '?product ex:suitableForAgeGroup ex:Adult .')
                && ! str_contains($q, '?teenLockedIngredient');
        }, $this->routineAgeRows());

        $this->get('/produk/EMI-0026?age=Adult')->assertOk();
    }

    public function test_invalid_age_keeps_unfiltered_similar_products(): void
    {
        $this->mockFuseki([$this->similarRow('WAR-0001', 'Produk Serupa')], function (string $q) {
            return ! str_contains($q, '?product ex:suitableForAgeGroup')
                && ! str_contains($q, '?teenLockedIngredient');
        }, $this->routineAgeRows());

        $this->get('/produk/EMI-0026?age=Invalid')->assertOk();
    }

    public function test_missing_age_keeps_unfiltered_similar_products(): void
    {
        $this->mockFuseki([$this->similarRow('WAR-0001', 'Produk Serupa')], function (string $q) {
            return ! str_contains($q, '?product ex:suitableForAgeGroup')
                && ! str_contains($q, '?teenLockedIngredient');
        });

        $this->get('/produk/EMI-0026')->assertOk();
    }

    public function test_main_product_and_unlinkable_legacy_ids_are_excluded(): void
    {
        $this->mockFuseki([
            $this->similarRow('EMI-0026', 'Produk Utama'),
            $this->similarRow('LAB-0053-AcneProBiomeAcneSpotGel', 'Data Lama'),
            $this->similarRow('WAR-0001', 'Serupa Valid'),
        ]);

        $similar = $this->get('/produk/EMI-0026')->assertOk()->viewData('similarProducts');

        $this->assertSame(['WAR-0001'], array_column($similar, 'id'));
        $this->assertSame(['Jerawat'], $similar[0]['shared_concerns']);
    }

    public function test_empty_state_when_no_similar_products(): void
    {
        $this->mockFuseki([]);

        $this->get('/produk/EMI-0026')->assertOk()
            ->assertSee('Produk Utama')
            ->assertSee('Belum ada produk serupa');
    }

    public function test_detail_page_still_works_when_similar_query_fails(): void
    {
        $fuseki = \Mockery::mock(CleanOntologyService::class)->makePartial();
        $fuseki->shouldReceive('query')->andReturn($this->detailRows())->once();
        $fuseki->shouldReceive('query')->andThrow(new RuntimeException('Fuseki mati'));
        $this->app->instance(CleanOntologyService::class, $fuseki);

        $this->get('/produk/EMI-0026')->assertOk()
            ->assertSee('Produk Utama')
            ->assertSee('Produk serupa sementara tidak dapat dimuat.');
    }

    /**
     * Uji integrasi dengan Fuseki sungguhan: untuk SETIAP produk, periksa bahwa
     * produk serupa (maks 4) bukan produk utama, berbeda brand, dan berbagi
     * minimal 1 jenis kulit serta 1 keluhan. Dilewati jika Fuseki tidak aktif.
     */
    public function test_real_fuseki_similar_products_follow_all_rules(): void
    {
        $fuseki = app(CleanOntologyService::class);

        try {
            $rows = $fuseki->query('SELECT ?p WHERE { ?p a ex:Product }');
        } catch (RuntimeException $e) {
            $this->markTestSkipped('Fuseki tidak aktif: '.$e->getMessage());
        }

        $ids = array_values(array_filter(
            array_map(fn ($r) => $fuseki->local($r['p']['value']), $rows),
            fn ($id) => preg_match('/^[A-Z]{3}-\d{4}$/', $id) === 1
        ));
        $this->assertNotEmpty($ids);

        $checked = 0;
        foreach ($ids as $id) {
            $similar = $this->get('/produk/'.$id)->assertOk()->viewData('similarProducts');
            $this->assertLessThanOrEqual(4, count($similar), $id);

            foreach ($similar as $item) {
                $this->assertNotSame($id, $item['id']);
                $valid = $fuseki->query("
                    SELECT ?ok WHERE {
                        ex:{$id} ex:hasBrand ?mainBrand .
                        ex:{$item['id']} ex:hasBrand ?otherBrand .
                        FILTER NOT EXISTS { ex:{$item['id']} ex:hasBrand ?mainBrand }
                        ex:{$id} ex:suitableForSkinType ?s . ex:{$item['id']} ex:suitableForSkinType ?s . ?s a ex:SkinType .
                        ex:{$id} ex:treatsConcern ?c . ex:{$item['id']} ex:treatsConcern ?c . ?c a ex:SkinConcern .
                        BIND(true AS ?ok)
                    } LIMIT 1
                ");
                $this->assertNotEmpty($valid, "{$item['id']} tidak memenuhi aturan produk serupa untuk {$id}");
                $checked++;
            }
        }

        $this->assertGreaterThan(0, $checked);
    }
}
