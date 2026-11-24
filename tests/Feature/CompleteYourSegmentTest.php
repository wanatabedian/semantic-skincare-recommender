<?php

namespace Tests\Feature;

use App\Services\CleanOntologyService;
use RuntimeException;
use Tests\TestCase;

class CompleteYourSegmentTest extends TestCase
{
    private const EX = 'https://example.org/skincare-ontology#';

    // ------------------------------------------------------------------
    // Bagian 1: logika pemilihan langkah, dengan data tiruan (mock).
    // ------------------------------------------------------------------

    private function candidate(string $id, string $category, array $flags = []): array
    {
        $bool = fn ($v) => ['type' => 'literal', 'datatype' => 'http://www.w3.org/2001/XMLSchema#boolean', 'value' => $v ? 'true' : 'false'];
        $int = fn ($v) => ['type' => 'literal', 'value' => (string) $v];

        return [
            'product' => ['type' => 'uri', 'value' => self::EX.$id],
            'name' => ['type' => 'literal', 'value' => $flags['name'] ?? "Produk {$id}"],
            'category' => ['type' => 'uri', 'value' => self::EX.$category],
            'price' => $int(50000),
            'brand' => ['type' => 'uri', 'value' => self::EX.($flags['brand'] ?? 'WardahBrand')],
            'sameSeries' => $bool($flags['series'] ?? false),
            'hasRetinol' => $bool($flags['retinol'] ?? false),
            'teenLocked' => $bool($flags['locked'] ?? false),
            'concernMatches' => $int($flags['concern'] ?? 0),
            'skinMatches' => $int($flags['skin'] ?? 1),
            // Bahan dari ex:hasIngredient, dikirim query sebagai URI dipisah spasi (GROUP_CONCAT).
            'ingredientList' => ['type' => 'literal', 'value' => implode(' ', array_map(fn ($i) => self::EX.$i, $flags['ingredients'] ?? []))],
        ];
    }

    private function vocabularyRows(): array
    {
        return [
            ['ageGroup' => ['type' => 'uri', 'value' => self::EX.'Teen'], 'ageGroupLabel' => ['type' => 'literal', 'value' => 'Remaja']],
            ['ageGroup' => ['type' => 'uri', 'value' => self::EX.'Adult'], 'ageGroupLabel' => ['type' => 'literal', 'value' => 'Dewasa']],
            ['routineCategory' => ['type' => 'uri', 'value' => self::EX.'SunscreenCategory'], 'routineCategoryLabel' => ['type' => 'literal', 'value' => 'Tabir Surya (Sunscreen)']],
            ['routineCategory' => ['type' => 'uri', 'value' => self::EX.'EyeCareCategory'], 'routineCategoryLabel' => ['type' => 'literal', 'value' => 'Perawatan Mata']],
            ['ingredientItem' => ['type' => 'uri', 'value' => self::EX.'Retinol'], 'ingredientItemLabel' => ['type' => 'literal', 'value' => 'Retinol']],
            ['ingredientItem' => ['type' => 'uri', 'value' => self::EX.'SalicylicAcid'], 'ingredientItemLabel' => ['type' => 'literal', 'value' => 'Salicylic Acid (BHA)']],
            ['ingredientItem' => ['type' => 'uri', 'value' => self::EX.'AHA'], 'ingredientItemLabel' => ['type' => 'literal', 'value' => 'AHA (Glycolic Acid)']],
        ];
    }

    /** Mock 4 query halaman detail: detail, produk serupa, kosakata rutinitas, kandidat rutinitas. */
    private function mockFuseki(array $candidateRows, ?callable $candidateQueryCheck = null): void
    {
        $fuseki = \Mockery::mock(CleanOntologyService::class)->makePartial();
        $fuseki->shouldReceive('query')->withArgs(fn ($q) => str_contains($q, 'ex:sourceProductId'))->andReturn([
            ['name' => ['type' => 'literal', 'value' => 'Produk Utama']],
        ]);
        $fuseki->shouldReceive('query')->withArgs(fn ($q) => str_contains($q, '?sharedConcernLabels'))->andReturn([]);
        $fuseki->shouldReceive('query')->withArgs(fn ($q) => str_contains($q, '?routineCategory'))->andReturn($this->vocabularyRows());
        $fuseki->shouldReceive('query')->once()
            ->withArgs(fn ($q) => str_contains($q, '?sameSeriesFlag') && ($candidateQueryCheck === null || $candidateQueryCheck($q)))
            ->andReturn($candidateRows);
        $this->app->instance(CleanOntologyService::class, $fuseki);
    }

    private function stepFor(array $routine, string $category): array
    {
        foreach ($routine['steps'] as $step) {
            if ($step['category'] === $category) {
                return $step;
            }
        }
        $this->fail("Langkah {$category} tidak ada");
    }

    public function test_broad_series_becomes_one_step_with_alternatives(): void
    {
        $this->mockFuseki([
            $this->candidate('WAR-0001', 'SunscreenCategory', ['series' => true]),
            $this->candidate('WAR-0002', 'SunscreenCategory', ['series' => true, 'concern' => 2]),
            $this->candidate('WAR-0003', 'SunscreenCategory', ['series' => true]),
            $this->candidate('KAH-0043', 'CleanserCategory', ['brand' => 'KahfBrand']),
        ]);

        $response = $this->get('/produk/WAR-0001')->assertOk();
        $routine = $response->viewData('routine');

        // 8 kategori -> 8 langkah, tidak ada kategori dobel.
        $this->assertCount(8, $routine['steps']);
        $this->assertSame(count($routine['steps']), count(array_unique(array_column($routine['steps'], 'category'))));

        $sunscreen = $this->stepFor($routine, 'SunscreenCategory');
        $this->assertSame('WAR-0001', $sunscreen['product']['id']);   // produk yang dibuka jadi langkah utama
        $this->assertSame('main', $sunscreen['product']['source']);
        $this->assertSame(['WAR-0002', 'WAR-0003'], array_column($sunscreen['alternatives'], 'id'));
        $this->assertSame(['pagi'], $sunscreen['times']);              // sunscreen hanya pagi

        $cleanser = $this->stepFor($routine, 'CleanserCategory');
        $this->assertSame('KAH-0043', $cleanser['product']['id']);
        $this->assertSame('other', $cleanser['product']['source']);
        $this->assertSame('Kahf', $cleanser['product']['brand']);      // label brand dari URI

        $this->assertNotContains('SunscreenCategory', array_column($routine['malam'], 'category'));
        $response->assertSee('id="lengkapi-rutinitas"', false)
            ->assertSee('Alternatif dari seri ini (2 produk)')
            ->assertSee(route('produk.detail', 'WAR-0002'), false)   // alternatif tetap bisa dibuka
            ->assertDontSee('data-routine-add="', false);            // tombol rutinitas sengaja dihapus
    }

    public function test_complement_from_other_series_must_share_skin_type(): void
    {
        $this->mockFuseki([
            $this->candidate('WAR-0015', 'CleanserCategory', ['series' => true]),
            $this->candidate('EMI-0001', 'TonerCategory', ['skin' => 0, 'concern' => 3]),
            $this->candidate('EMI-0002', 'TonerCategory', ['skin' => 1, 'concern' => 0]),
            $this->candidate('EMI-0003', 'TonerCategory', ['skin' => 1, 'concern' => 1]),
        ]);

        $routine = $this->get('/produk/WAR-0015')->assertOk()->viewData('routine');
        $toner = $this->stepFor($routine, 'TonerCategory');

        // EMI-0001 punya keluhan sama terbanyak tapi jenis kulitnya tidak sama -> tidak dipilih.
        $this->assertSame('EMI-0003', $toner['product']['id']);
        $this->assertSame('other', $toner['product']['source']);
        $this->assertSame([], $toner['alternatives']);                 // pelengkap tidak punya alternatif
        $this->assertNull($this->stepFor($routine, 'EyeCareCategory')['product']);
    }

    public function test_teen_lock_hides_locked_products_and_filters_age(): void
    {
        $this->mockFuseki([
            $this->candidate('LAB-0055', 'TonerCategory', ['series' => true]),
            $this->candidate('LAB-0054', 'SpotTreatmentCategory', ['series' => true, 'locked' => true, 'retinol' => true]),
        ], function (string $q) {
            return str_contains($q, '?product ex:suitableForAgeGroup ex:Teen')
                && str_contains($q, 'ex:contraindicatedFor')               // pola retinoidPattern() Orang 3
                && str_contains($q, 'AS ?lockedFlag');
        });

        $response = $this->get('/produk/LAB-0055?age=Teen')->assertOk();
        $routine = $response->viewData('routine');

        $this->assertTrue($routine['teen_lock']);
        $this->assertSame('Remaja', $routine['age']['label']);
        $this->assertSame(1, $routine['hidden_series_count']);
        $this->assertNull($this->stepFor($routine, 'SpotTreatmentCategory')['product']);
        $response->assertSee('Produk ber-retinol disembunyikan untuk usia remaja.')
            ->assertSee('Disaring untuk usia: Remaja')
            ->assertDontSee('Produk LAB-0054');                     // produk terkunci Teen Lock tidak tampil
    }

    public function test_adult_and_missing_age_do_not_activate_teen_lock(): void
    {
        $this->mockFuseki([], fn ($q) => str_contains($q, 'BIND(false AS ?lockedFlag)') && ! str_contains($q, 'suitableForAgeGroup'));
        $this->assertFalse($this->get('/produk/LAB-0055')->assertOk()->viewData('routine')['teen_lock']);
    }

    public function test_unknown_or_unsafe_age_is_ignored(): void
    {
        $this->mockFuseki([], fn ($q) => ! str_contains($q, 'suitableForAgeGroup'));

        $routine = $this->get('/produk/LAB-0055?age='.urlencode('Teen } DROP ALL'))->assertOk()->viewData('routine');
        $this->assertNull($routine['age']);
    }

    public function test_retinol_product_is_night_only(): void
    {
        $this->mockFuseki([
            $this->candidate('TAV-0004', 'SerumCategory', ['series' => true, 'retinol' => true]),
        ]);

        $routine = $this->get('/produk/TAV-0004')->assertOk()->viewData('routine');

        $this->assertSame(['malam'], $this->stepFor($routine, 'SerumCategory')['times']);
        $this->assertNotContains('SerumCategory', array_column($routine['pagi'], 'category'));
        $this->assertContains('SerumCategory', array_column($routine['malam'], 'category'));
    }

    public function test_detail_page_still_works_when_routine_query_fails(): void
    {
        $fuseki = \Mockery::mock(CleanOntologyService::class)->makePartial();
        $fuseki->shouldReceive('query')->withArgs(fn ($q) => str_contains($q, 'ex:sourceProductId'))->andReturn([
            ['name' => ['type' => 'literal', 'value' => 'Produk Utama']],
        ]);
        $fuseki->shouldReceive('query')->withArgs(fn ($q) => str_contains($q, '?sharedConcernLabels'))->andReturn([]);
        $fuseki->shouldReceive('query')->withArgs(fn ($q) => str_contains($q, '?routineCategory'))->andThrow(new RuntimeException('Fuseki mati'));
        $this->app->instance(CleanOntologyService::class, $fuseki);

        $this->get('/produk/WAR-0001')->assertOk()
            ->assertSee('Produk Utama')
            ->assertSee('id="lengkapi-rutinitas"', false)
            ->assertSee('Rutinitas sementara tidak dapat dimuat.');
    }

    // ------------------------------------------------------------------
    // Bagian 1b: integrasi IngredientConflictDetector (mock Fuseki).
    // ------------------------------------------------------------------

    public function test_retinol_and_bha_from_different_products_warn_only_in_the_night_routine(): void
    {
        // Data asli ontologi clean: TAV-0004 (Retinol + Peptide), TAV-0002 (Ceramide + PHA + SalicylicAcid).
        $this->mockFuseki([
            $this->candidate('TAV-0004', 'SerumCategory', ['series' => true, 'retinol' => true, 'ingredients' => ['Peptide', 'Retinol'], 'name' => 'TAVI 1% Encapsulated Retinol + Peptide']),
            $this->candidate('TAV-0002', 'TonerCategory', ['ingredients' => ['Ceramide', 'PHA', 'SalicylicAcid'], 'name' => 'TAVI Natural PHA + BHA Retexture Toner']),
        ], fn ($q) => str_contains($q, '?product ex:hasIngredient ?ingredientUri') && str_contains($q, 'AS ?ingredientList'));

        $response = $this->get('/produk/TAV-0004')->assertOk();
        $routine = $response->viewData('routine');

        $this->assertNull($routine['conflict_error']);
        // Pagi: TAV-0004 hanya Malam -> tidak ada pasangan Retinol.
        $this->assertSame([], $routine['conflicts']['pagi']['conflicts']);

        $night = $routine['conflicts']['malam'];
        $this->assertCount(1, $night['conflicts']);                     // hasil asli detektor tetap utuh
        $this->assertSame('retinoid-chemical-exfoliant', $night['conflicts'][0]['rule']);
        $this->assertCount(1, $night['groups']);
        $group = $night['groups'][0];
        $this->assertSame('Perlu diperhatikan', $group['level_label']);
        // Urutan produk mengikuti urutan langkah rutinitas: Toner (TAV-0002) sebelum Serum (TAV-0004).
        $this->assertSame(['TAV-0002', 'TAV-0004'], array_column($group['products'], 'id'));
        $this->assertSame(['Salicylic Acid (BHA)'], $group['products'][0]['ingredients']);  // label dari ontologi
        $this->assertSame(['Retinol'], $group['products'][1]['ingredients']);
        $this->assertEqualsCanonicalizing(['TAV-0004', 'TAV-0002'], $night['involved_ids']);

        $response->assertSee('Perlu diperhatikan')
            ->assertSee('Salicylic Acid (BHA) + Retinol')
            ->assertSee('Pertimbangkan untuk memisahkan waktu pemakaiannya.')
            ->assertSee('TAVI Natural PHA + BHA Retexture Toner')
            ->assertSee('https://www.aad.org/public/everyday-care/skin-care-secrets/routine/safely-exfoliate-at-home', false)
            ->assertSee('Perhatikan kombinasi bahan');
    }

    public function test_alternatives_are_never_checked_for_conflicts(): void
    {
        $this->mockFuseki([
            $this->candidate('WAR-0001', 'SunscreenCategory', ['series' => true, 'ingredients' => ['VitaminC']]),
            // Alternatif se-seri berisi AHA: kalau ikut diperiksa, akan berpasangan dengan BHA di cleanser.
            $this->candidate('WAR-0002', 'SunscreenCategory', ['series' => true, 'ingredients' => ['AHA']]),
            $this->candidate('LAB-0056', 'CleanserCategory', ['ingredients' => ['SalicylicAcid']]),
        ]);

        $routine = $this->get('/produk/WAR-0001')->assertOk()->viewData('routine');

        $this->assertSame(['WAR-0002'], array_column($this->stepFor($routine, 'SunscreenCategory')['alternatives'], 'id'));
        foreach (['pagi', 'malam'] as $time) {
            $this->assertSame([], $routine['conflicts'][$time]['conflicts'], $time);
            $this->assertNotContains('WAR-0002', $routine['conflicts'][$time]['involved_ids']);
            $this->assertNotContains('WAR-0002', array_column($routine['conflicts'][$time]['unchecked'], 'id'));
        }
    }

    public function test_repeated_warnings_are_grouped_per_rule_without_changing_raw_result(): void
    {
        // Seri AcnePro: tiga produk berisi SalicylicAcid -> tiga pasangan, satu peringatan.
        $this->mockFuseki([
            $this->candidate('LAB-0055', 'TonerCategory', ['series' => true, 'ingredients' => ['Niacinamide', 'SalicylicAcid']]),
            $this->candidate('LAB-0056', 'CleanserCategory', ['series' => true, 'ingredients' => ['Niacinamide', 'SalicylicAcid', 'Zinc']]),
            $this->candidate('LAB-0069', 'SerumCategory', ['series' => true, 'ingredients' => ['Niacinamide', 'SalicylicAcid']]),
        ]);

        $response = $this->get('/produk/LAB-0055')->assertOk();
        $morning = $response->viewData('routine')['conflicts']['pagi'];

        $this->assertCount(3, $morning['conflicts']);
        $this->assertCount(1, $morning['groups']);
        $this->assertSame(3, $morning['groups'][0]['pair_count']);
        $this->assertSame(['LAB-0056', 'LAB-0055', 'LAB-0069'], array_column($morning['groups'][0]['products'], 'id'));
        $this->assertSame(['Salicylic Acid (BHA)'], $morning['groups'][0]['ingredients']);

        // Satu peringatan per kolom (Pagi + Malam), bukan tiga.
        $reason = 'Penggunaan beberapa produk eksfolian dalam waktu yang sama dapat meningkatkan risiko eksfoliasi berlebihan.';
        $this->assertSame(2, substr_count($response->getContent(), $reason));
    }

    public function test_optional_step_product_is_checked_when_selected(): void
    {
        $this->mockFuseki([
            $this->candidate('LAB-0056', 'CleanserCategory', ['series' => true, 'ingredients' => ['SalicylicAcid']]),
            $this->candidate('MST-0001', 'FaceMistCategory', ['ingredients' => ['AHA']]),
        ]);

        $routine = $this->get('/produk/LAB-0056')->assertOk()->viewData('routine');

        $this->assertTrue($this->stepFor($routine, 'FaceMistCategory')['optional']);
        $this->assertContains('MST-0001', $routine['conflicts']['pagi']['involved_ids']);
        $this->assertSame('multiple-chemical-exfoliants', $routine['conflicts']['pagi']['conflicts'][0]['rule']);
    }

    public function test_products_without_ingredient_data_are_shown_as_unchecked(): void
    {
        $this->mockFuseki([
            $this->candidate('WAR-0015', 'CleanserCategory', ['series' => true, 'ingredients' => ['VitaminC', 'Panthenol']]),
            $this->candidate('KAH-0047', 'SunscreenCategory', ['name' => 'Bright Fresh Sunscreen Serum SPF 35 PA++++']),
        ]);

        $response = $this->get('/produk/WAR-0015')->assertOk();
        $morning = $response->viewData('routine')['conflicts']['pagi'];

        $this->assertSame(['KAH-0047'], array_column($morning['unchecked'], 'id'));
        $this->assertSame(1, $morning['checked_count']);
        $this->assertSame([], $morning['conflicts']);
        $response->assertSee('Belum dapat diperiksa')
            ->assertSee('Bright Fresh Sunscreen Serum SPF 35 PA++++')
            ->assertSee('bukan saran medis');
    }

    public function test_teen_lock_still_runs_before_conflict_check(): void
    {
        $this->mockFuseki([
            $this->candidate('LAB-0055', 'TonerCategory', ['series' => true, 'ingredients' => ['Niacinamide', 'SalicylicAcid']]),
            $this->candidate('LAB-0054', 'SpotTreatmentCategory', ['series' => true, 'locked' => true, 'retinol' => true, 'ingredients' => ['Probiotic', 'Retinol', 'SalicylicAcid']]),
        ]);

        $routine = $this->get('/produk/LAB-0055?age=Teen')->assertOk()->viewData('routine');

        $this->assertSame(1, $routine['hidden_series_count']);
        foreach (['pagi', 'malam'] as $time) {
            $this->assertNotContains('retinoid-chemical-exfoliant', array_column($routine['conflicts'][$time]['conflicts'], 'rule'));
            $this->assertNotContains('LAB-0054', $routine['conflicts'][$time]['involved_ids']);
        }
    }

    public function test_routine_still_renders_when_conflict_check_fails(): void
    {
        $this->mockFuseki([
            $this->candidate('TAV-0004', 'SerumCategory', ['series' => true, 'retinol' => true, 'ingredients' => ['Retinol']]),
        ]);
        $this->mock(\App\Services\IngredientConflictDetector::class)
            ->shouldReceive('detect')->andThrow(new RuntimeException('detektor gagal'));

        $response = $this->get('/produk/TAV-0004')->assertOk();
        $routine = $response->viewData('routine');

        $this->assertNull($routine['conflicts']);
        $this->assertSame('TAV-0004', $this->stepFor($routine, 'SerumCategory')['product']['id']);
        $response->assertSee('id="lengkapi-rutinitas"', false)
            ->assertSee('Pemeriksaan kombinasi bahan sementara tidak tersedia.')
            ->assertDontSee('Rutinitas sementara tidak dapat dimuat.');
    }

    // ------------------------------------------------------------------
    // Bagian 2: uji integrasi dengan Fuseki Clean sungguhan (dilewati jika Fuseki mati).
    // ------------------------------------------------------------------

    private function realFuseki(): CleanOntologyService
    {
        $fuseki = app(CleanOntologyService::class);
        try {
            $fuseki->query('SELECT ?p WHERE { ?p a ex:Product } LIMIT 1');
        } catch (RuntimeException $e) {
            $this->markTestSkipped('Fuseki tidak aktif: '.$e->getMessage());
        }

        return $fuseki;
    }

    private function ids(CleanOntologyService $fuseki, string $where): array
    {
        $rows = $fuseki->query("SELECT DISTINCT ?p WHERE { {$where} } ORDER BY ?p");

        return array_values(array_filter(
            array_map(fn ($r) => $fuseki->local($r['p']['value']), $rows),
            fn ($id) => preg_match('/^[A-Z]{3}-\d{4}$/', $id) === 1
        ));
    }

    private function routineIds(array $routine): array
    {
        $ids = [];
        foreach ($routine['steps'] as $step) {
            if ($step['product']) {
                $ids[] = $step['product']['id'];
            }
            foreach ($step['alternatives'] as $alt) {
                $ids[] = $alt['id'];
            }
        }

        return $ids;
    }

    public function test_real_uv_shield_series_is_not_eight_sunscreen_steps(): void
    {
        $fuseki = $this->realFuseki();
        $uvShield = $this->ids($fuseki, '?p ex:belongsToSeries ex:UVShieldSeries');
        $this->assertCount(8, $uvShield);

        foreach ($uvShield as $id) {
            $routine = $this->get('/produk/'.$id)->assertOk()->viewData('routine');

            // Setiap kategori paling banyak satu langkah.
            $this->assertSame(count($routine['steps']), count(array_unique(array_column($routine['steps'], 'category'))), $id);

            $sunscreenSteps = array_values(array_filter($routine['steps'], fn ($s) => $s['category'] === 'SunscreenCategory'));
            $this->assertCount(1, $sunscreenSteps, $id);
            $this->assertSame($id, $sunscreenSteps[0]['product']['id']);
            $this->assertCount(7, $sunscreenSteps[0]['alternatives'], $id);    // 7 sunscreen lain = alternatif
            $this->assertEqualsCanonicalizing(array_diff($uvShield, [$id]), array_column($sunscreenSteps[0]['alternatives'], 'id'));

            // Langkah lain dilengkapi dari seri lain (UV Shield hanya berisi sunscreen).
            $others = array_filter($routine['steps'], fn ($s) => $s['category'] !== 'SunscreenCategory' && $s['product']);
            $this->assertNotEmpty($others, $id);
            foreach ($others as $step) {
                $this->assertSame('other', $step['product']['source']);
                $this->assertNotContains($step['product']['id'], $uvShield);
            }
        }
    }

    public function test_real_complement_products_come_from_other_series_with_shared_skin_type(): void
    {
        $fuseki = $this->realFuseki();
        // WAR-0015 adalah satu-satunya produk di CDefenseSeries.
        $this->assertSame(['WAR-0015'], $this->ids($fuseki, '?p ex:belongsToSeries ex:CDefenseSeries'));

        $routine = $this->get('/produk/WAR-0015')->assertOk()->viewData('routine');
        $complements = array_filter($routine['steps'], fn ($s) => $s['product'] && $s['product']['id'] !== 'WAR-0015');
        $this->assertNotEmpty($complements);

        foreach ($complements as $step) {
            $other = $step['product']['id'];
            $this->assertSame('other', $step['product']['source']);
            $this->assertNotEmpty($fuseki->query("SELECT ?ok WHERE {
                ex:{$other} ex:hasCategory ex:{$step['category']} .
                FILTER NOT EXISTS { ex:{$other} ex:belongsToSeries ex:CDefenseSeries }
                ex:WAR-0015 ex:suitableForSkinType ?s . ex:{$other} ex:suitableForSkinType ?s . ?s a ex:SkinType .
                BIND(true AS ?ok) } LIMIT 1"), "{$other} bukan pelengkap yang valid");
        }
    }

    public function test_real_teen_lock_never_shows_retinol_for_any_product(): void
    {
        $fuseki = $this->realFuseki();
        $retinol = $this->ids($fuseki, '?p ex:hasIngredient ex:Retinol');
        $teen = $this->ids($fuseki, '?p ex:suitableForAgeGroup ex:Teen');
        $this->assertContains('LAB-0054', $retinol);   // produk ber-Retinol yang ditandai Teen (temuan A7)

        foreach ($this->ids($fuseki, '?p a ex:Product') as $id) {
            $routine = $this->get("/produk/{$id}?age=Teen")->assertOk()->viewData('routine');
            $shown = $this->routineIds($routine);

            $this->assertSame([], array_values(array_intersect($shown, $retinol)), "Retinol tampil untuk Teen di {$id}");
            $this->assertSame([], array_values(array_diff($shown, $teen)), "Produk non-Teen tampil di {$id}");
        }

        // AcnePro: LAB-0054 disembunyikan untuk Teen, tetapi tampil (hanya malam) tanpa umur.
        $teenRoutine = $this->get('/produk/LAB-0055?age=Teen')->assertOk()->viewData('routine');
        $this->assertGreaterThanOrEqual(1, $teenRoutine['hidden_series_count']);

        $anyRoutine = $this->get('/produk/LAB-0055')->assertOk()->viewData('routine');
        $this->assertContains('LAB-0054', $this->routineIds($anyRoutine));
        $spot = $this->stepFor($anyRoutine, 'SpotTreatmentCategory');
        $this->assertSame('LAB-0054', $spot['product']['id']);
        $this->assertSame(['malam'], $spot['times']);
    }

    public function test_real_conflict_check_uses_only_primary_products_of_each_time(): void
    {
        $fuseki = $this->realFuseki();
        $withIngredients = $this->ids($fuseki, '?p ex:hasIngredient ?i');
        $retinol = $this->ids($fuseki, '?p ex:hasIngredient ex:Retinol');

        foreach ($this->ids($fuseki, '?p a ex:Product') as $id) {
            foreach (['', '?age=Teen'] as $query) {
                $routine = $this->get("/produk/{$id}{$query}")->assertOk()->viewData('routine');
                $this->assertNull($routine['conflict_error'], $id.$query);

                $alternatives = [];
                foreach ($routine['steps'] as $step) {
                    $alternatives = array_merge($alternatives, array_column($step['alternatives'], 'id'));
                }

                foreach (['pagi', 'malam'] as $time) {
                    $primary = array_values(array_filter(array_map(fn ($s) => $s['product']['id'] ?? null, $routine[$time])));
                    $check = $routine['conflicts'][$time];
                    $involved = [];
                    foreach ($check['conflicts'] as $conflict) {
                        $involved = array_merge($involved, array_column($conflict['products'], 'id'));
                        if ($query !== '') {
                            $this->assertNotSame('retinoid-chemical-exfoliant', $conflict['rule'], "Teen {$id} {$time}");
                        }
                    }
                    $label = "{$id}{$query} {$time}";

                    $this->assertSame([], array_values(array_diff($involved, $primary)), "bukan produk utama: {$label}");
                    $this->assertSame([], array_values(array_diff($check['involved_ids'], $primary)), $label);
                    // Unchecked = tepat produk utama tanpa data bahan; checked + unchecked = semua produk utama.
                    $unchecked = array_column($check['unchecked'], 'id');
                    $this->assertEqualsCanonicalizing(array_values(array_diff($primary, $withIngredients)), $unchecked, $label);
                    $this->assertSame(count(array_unique($primary)), $check['checked_count'] + count($unchecked), $label);
                    if ($query !== '') {
                        $this->assertSame([], array_values(array_intersect($primary, $retinol)), $label);
                    }
                    // Alternatif tidak pernah ikut kecuali kebetulan juga produk utama di waktu ini.
                    $this->assertSame([], array_values(array_diff(array_intersect($involved, $alternatives), $primary)), $label);
                }
            }
        }

        // Kasus nyata: rutinitas Malam TAV-0004 berisi LAB-0054 (BHA) -> peringatan Retinol + BHA.
        $night = $this->get('/produk/TAV-0004')->assertOk()->viewData('routine')['conflicts']['malam'];
        $this->assertContains('retinoid-chemical-exfoliant', array_column($night['groups'], 'rule'));
        $this->assertContains('TAV-0004', $night['involved_ids']);
    }
}
