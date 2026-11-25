<?php

namespace Tests\Feature;

use App\Services\Recommendation\Criteria;
use App\Services\Recommendation\RecommendationEngine;
use App\Services\Recommendation\Vocabulary;
use App\Services\SparqlException;
use App\Services\SparqlService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Uji mesin rekomendasi TANPA Fuseki: SparqlService diganti versi palsu yang
 * mengembalikan baris tiruan. Uji dengan Fuseki sungguhan ada di
 * RecommendationFusekiTest (otomatis di-skip kalau Fuseki mati).
 */
class RecommendationEngineTest extends TestCase
{
    private const EX = 'https://example.org/skincare-ontology#';

    /** @var array<int, string> query yang dikirim ke SparqlService palsu */
    private array $queries = [];

    private array $scoreRows = [];

    private bool $askResult = false;

    private ?SparqlException $failWith = null;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.fuseki.namespace' => self::EX, 'recommendation.cache_ttl' => 0]);

        $test = $this;
        $sparql = new class($test) extends SparqlService
        {
            public function __construct(private RecommendationEngineTest $test) {}

            public function select(string $body, bool $useCache = true): array
            {
                return $this->test->fakeSelect($body);
            }

            public function ask(string $body, bool $useCache = true): bool
            {
                return $this->test->fakeAsk($body);
            }
        };

        $vocabulary = new class($sparql) extends Vocabulary
        {
            public function all(): array
            {
                return [
                    'age' => ['Teen' => 'Remaja', 'Adult' => 'Dewasa'],
                    'skin' => ['OilySkinType' => 'Berminyak', 'DrySkinType' => 'Kering', 'AllSkinType' => 'Semua Jenis Kulit'],
                    'category' => ['SerumCategory' => 'Serum', 'SunscreenCategory' => 'Sunscreen'],
                    'concern' => ['Acne' => 'Jerawat', 'ExcessOil' => 'Minyak Berlebih', 'DullnessBrightening' => 'Kusam'],
                    'ingredient' => ['NiacinamideIngredient' => 'Niacinamide', 'RetinolIngredient' => 'Retinol'],
                ];
            }
        };

        $this->app->instance(SparqlService::class, $sparql);
        $this->app->instance(Vocabulary::class, $vocabulary);
    }

    public function fakeSelect(string $body): array
    {
        if ($this->failWith) {
            throw $this->failWith;
        }
        $this->queries[] = $body;
        if (str_contains($body, 'ORDER BY DESC(?score)')) {
            return $this->scoreRows;
        }
        if (str_contains($body, 'COUNT(DISTINCT ?p)')) {
            return [['n' => ['type' => 'literal', 'value' => '3']]];
        }

        return []; // query detail: tidak wajib untuk uji logika
    }

    public function fakeAsk(string $body): bool
    {
        $this->queries[] = $body;

        return $this->askResult;
    }

    private function row(string $id, int $score, array $extra = []): array
    {
        $row = [
            'p' => ['type' => 'uri', 'value' => self::EX.$id],
            'name' => ['type' => 'literal', 'value' => "Produk {$id}"],
            'price' => ['type' => 'literal', 'value' => '75000'],
            'score' => ['type' => 'literal', 'value' => (string) $score],
        ];
        foreach ($extra as $var => $value) {
            $row[$var] = ['type' => 'literal', 'value' => (string) $value];
        }

        return $row;
    }

    private function engine(): RecommendationEngine
    {
        return $this->app->make(RecommendationEngine::class);
    }

    private function criteria(array $input): Criteria
    {
        return Criteria::fromInput($input, $this->app->make(Vocabulary::class));
    }

    // 1
    public function test_kuis_kosong_mengembalikan_semua_produk_sebagai_plan_a(): void
    {
        $this->scoreRows = [$this->row('A-1', 0), $this->row('A-2', 0), $this->row('A-3', 0)];
        $result = $this->engine()->recommend($this->criteria([]));

        $this->assertSame('A', $result['plan']);
        $this->assertSame(0, $result['max_score']);
        $this->assertSame(3, $result['total']);
        $this->assertSame([], $result['relaxed']);
        $this->assertStringContainsString('(0 AS ?score)', $this->queries[0]);
    }

    // 2
    public function test_teen_lock_memakai_kelas_retinoid_dan_tidak_pernah_dilonggarkan(): void
    {
        $query = $this->engine()->buildScoreQuery($this->criteria(['age' => 'Teen']), teenLock: true);

        $this->assertStringContainsString('?p ex:suitableForAgeGroup <'.self::EX.'Teen> .', $query);
        $this->assertStringContainsString('FILTER NOT EXISTS {', $query);
        $this->assertStringContainsString('rdf:type/rdfs:subClassOf* <'.self::EX.'Retinoid>', $query);
        $this->assertStringContainsString('ex:contraindicatedFor <'.self::EX.'Teen>', $query);
        $this->assertStringContainsString('<'.self::EX.'RetinolIngredient>', $query);
        $this->assertStringNotContainsString('REGEX', $query);
    }

    // 3
    public function test_teen_lock_juga_berlaku_di_plan_b(): void
    {
        $this->scoreRows = [$this->row('A-1', 2, ['skinVia' => 'exact', 'sSkin' => 2])];
        $result = $this->engine()->recommend($this->criteria(['age' => 'Teen', 'skin' => 'OilySkinType', 'concern' => ['Acne']]));

        // Hanya ada SATU query skor untuk Plan A dan Plan B, dan query itu memuat Teen Lock.
        $scoreQueries = array_values(array_filter($this->queries, fn ($q) => str_contains($q, 'ORDER BY DESC(?score)')));
        $this->assertCount(1, $scoreQueries);
        $this->assertStringContainsString('FILTER NOT EXISTS', $scoreQueries[0]);
        $this->assertSame('B', $result['plan']);
        $this->assertTrue($result['teen_lock']['active']);
        $this->assertSame(3, $result['teen_lock']['hidden_count']);
        $this->assertStringContainsString('ber-retinol disembunyikan', $result['teen_lock']['message']);
    }

    // 4
    public function test_dewasa_tidak_terkena_teen_lock(): void
    {
        $query = $this->engine()->buildScoreQuery($this->criteria(['age' => 'Adult']), teenLock: false);
        $this->assertStringNotContainsString('FILTER NOT EXISTS', $query);
        $this->assertFalse($this->engine()->teenLockActive($this->criteria(['age' => 'Adult'])));
    }

    // 5
    public function test_plan_a_hanya_berisi_produk_skor_maksimal(): void
    {
        $this->scoreRows = [
            $this->row('A-1', 5, ['skinVia' => 'exact', 'sSkin' => 2, 'c1Via' => 'direct', 'sC1' => 3]),
            $this->row('A-2', 3, ['skinVia' => '', 'sSkin' => 0, 'c1Via' => 'direct', 'sC1' => 3]),
        ];
        $result = $this->engine()->recommend($this->criteria(['skin' => 'OilySkinType', 'concern' => ['Acne']]));

        $this->assertSame('A', $result['plan']);
        $this->assertSame(['A-1'], array_column($result['products'], 'id'));
        $this->assertSame(100, $result['products'][0]['match_percent']);
    }

    // 6
    public function test_kombinasi_mustahil_memicu_plan_b_dengan_relaxed_terisi(): void
    {
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            $rows[] = $this->row("B-{$i}", 3, ['skinVia' => '', 'sSkin' => 0, 'c1Via' => 'direct', 'sC1' => 3, 'sPrice' => 0]);
        }
        $this->scoreRows = $rows;
        $result = $this->engine()->recommend($this->criteria(['skin' => 'DrySkinType', 'concern' => ['Acne'], 'price_max' => 1000]));

        $this->assertSame('B', $result['plan']);
        $this->assertCount(12, $result['products']);
        $this->assertSame(['jenis kulit', 'harga'], $result['relaxed']);
        $this->assertSame(['skin', 'price'], $result['relaxed_keys']);
        $this->assertSame(50, $result['products'][0]['match_percent']); // 3 dari 6
    }

    // 7
    public function test_alasan_dibangun_dari_komponen_skor_dan_label_ontologi(): void
    {
        $this->scoreRows = [$this->row('A-1', 9, [
            'skinVia' => 'all', 'sSkin' => 2, 'c1Via' => 'direct', 'sC1' => 3, 'sIng' => 2, 'sCat' => 2,
        ])];
        $result = $this->engine()->recommend($this->criteria([
            'skin' => 'OilySkinType', 'concern' => ['Acne'], 'ingredient' => 'NiacinamideIngredient', 'category' => 'SerumCategory',
        ]));

        $this->assertSame([
            'Cocok untuk semua jenis kulit (termasuk Berminyak)',
            'Mengatasi Jerawat',
            'Termasuk kategori Serum',
            'Mengandung Niacinamide',
        ], $result['products'][0]['reasons']);
    }

    // 8
    public function test_dua_keluhan_masing_masing_bernilai_tiga_poin(): void
    {
        $criteria = $this->criteria(['concern' => ['Acne', 'ExcessOil'], 'skin' => 'OilySkinType', 'price_min' => 50000]);
        $this->assertSame(3 + 3 + 2 + 1, $this->engine()->maxScore($criteria));

        $query = $this->engine()->buildScoreQuery($criteria, false);
        $this->assertStringContainsString('ex:treatsConcern <'.self::EX.'Acne>', $query);
        $this->assertStringContainsString('ex:treatsConcern <'.self::EX.'ExcessOil>', $query);
        $this->assertStringContainsString('((?sSkin + ?sC1 + ?sC2 + ?sPrice) AS ?score)', $query);
    }

    // 9
    public function test_tiga_keluhan_ditolak(): void
    {
        $this->expectException(ValidationException::class);
        $this->criteria(['concern' => ['Acne', 'ExcessOil', 'DullnessBrightening']]);
    }

    // 10
    public function test_input_injection_ditolak(): void
    {
        foreach (['} DROP ALL ; #', 'OilySkinType> } DELETE WHERE { ?s ?p ?o', 'ex:OilySkinType'] as $bad) {
            try {
                $this->criteria(['skin' => $bad]);
                $this->fail("Input berbahaya lolos: {$bad}");
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    // 11
    public function test_nilai_yang_tidak_ada_di_ontologi_ditolak(): void
    {
        $this->expectException(ValidationException::class);
        $this->criteria(['category' => 'ToothpasteCategory']);
    }

    // 12
    public function test_harga_maksimum_lebih_kecil_dari_minimum_ditolak(): void
    {
        $this->expectException(ValidationException::class);
        $this->criteria(['price_min' => 100000, 'price_max' => 50000]);
    }

    // 13
    public function test_nama_parameter_lama_tetap_diterima(): void
    {
        $criteria = $this->criteria(['age_group' => 'Teen', 'skin_type' => 'OilySkinType', 'concerns' => ['Acne']]);
        $this->assertSame('Teen', $criteria->age);
        $this->assertSame('OilySkinType', $criteria->skin);
        $this->assertSame(['Acne'], $criteria->concerns);
    }

    // 14
    public function test_teen_dengan_retinol_memberi_peringatan_dan_kriteria_bahan_diabaikan(): void
    {
        $this->askResult = true; // Retinol terdeteksi sebagai retinoid
        $this->scoreRows = [$this->row('A-1', 0)];
        $result = $this->engine()->recommend($this->criteria(['age' => 'Teen', 'ingredient' => 'RetinolIngredient']));

        $this->assertSame(0, $result['max_score']);
        $this->assertSame('A', $result['plan']);
        $this->assertStringContainsString('Retinol tidak disarankan untuk usia remaja', $result['notices'][0]);
        $scoreQuery = collect($this->queries)->first(fn ($q) => str_contains($q, 'ORDER BY DESC(?score)'));
        $this->assertStringNotContainsString('?sIng', $scoreQuery);
    }

    // 15
    public function test_api_json_memuat_plan_relaxed_score_dan_reasons(): void
    {
        $this->scoreRows = [$this->row('A-1', 3, ['c1Via' => 'direct', 'sC1' => 3, 'skinVia' => '', 'sSkin' => 0])];
        $response = $this->getJson('/api/recommendation?skin=DrySkinType&concern[]=Acne');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('plan', 'B')
            ->assertJsonPath('relaxed', ['jenis kulit'])
            ->assertJsonPath('products.0.id', 'A-1')
            ->assertJsonPath('products.0.score', 3)
            ->assertJsonPath('products.0.reasons', ['Mengatasi Jerawat']);
    }

    // 16
    public function test_api_menolak_tiga_keluhan_dengan_422(): void
    {
        $this->postJson('/api/recommendation', ['concern' => ['Acne', 'ExcessOil', 'DullnessBrightening']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('concern');
    }

    // 17
    public function test_fuseki_mati_menghasilkan_pesan_ramah(): void
    {
        $this->failWith = new SparqlException('Server Fuseki tidak dapat dihubungi.');

        $this->getJson('/api/recommendation')->assertStatus(503)->assertJsonPath('message', 'Server Fuseki tidak dapat dihubungi.');
        $this->get('/rekomendasi')->assertStatus(503)->assertSee('Server Fuseki tidak dapat dihubungi.');
    }

    // 18
    public function test_halaman_hasil_menampilkan_banner_plan_b_dan_alasan(): void
    {
        $this->scoreRows = [$this->row('A-1', 3, ['c1Via' => 'direct', 'sC1' => 3, 'skinVia' => '', 'sSkin' => 0])];

        $this->get('/rekomendasi?skin=DrySkinType&concern[]=Acne')
            ->assertOk()
            ->assertSee('Kami melonggarkan')
            ->assertSee('jenis kulit')
            ->assertSee('Mengatasi Jerawat')
            ->assertSee('60% cocok'); // skor 3 dari 5 (kulit 2 + keluhan 3)
    }

    public function test_plan_a_juga_mengembalikan_produk_cocok_sebagian_beserta_alasannya(): void
    {
        // Kriteria: kulit (2) + keluhan (3) = maks 5.
        $this->scoreRows = [
            $this->row('FULL-1', 5, ['skinVia' => 'exact', 'sSkin' => 2, 'c1Via' => 'direct', 'sC1' => 3]),
            $this->row('PART-1', 3, ['skinVia' => '', 'sSkin' => 0, 'c1Via' => 'direct', 'sC1' => 3]),
            $this->row('PART-2', 2, ['skinVia' => 'exact', 'sSkin' => 2, 'c1Via' => '', 'sC1' => 0]),
            $this->row('ZERO-1', 0, ['skinVia' => '', 'sSkin' => 0, 'c1Via' => '', 'sC1' => 0]),
        ];

        $result = $this->engine()->recommend($this->criteria(['skin' => 'DrySkinType', 'concern' => ['Acne']]));

        $this->assertSame('A', $result['plan']);
        $this->assertSame(['FULL-1'], array_column($result['products'], 'id'));
        // Skor 0 tidak ikut; urutan skor tertinggi dulu.
        $this->assertSame(2, $result['partial_total']);
        $this->assertSame(['PART-1', 'PART-2'], array_column($result['partial'], 'id'));
        $this->assertSame(60, $result['partial'][0]['match_percent']);
        $this->assertSame(['Mengatasi Jerawat'], $result['partial'][0]['reasons']);
        $this->assertSame(['Tidak tercatat untuk kulit Kering'], $result['partial'][0]['missed_detail']);
        $this->assertSame(['Tidak menargetkan keluhan Jerawat'], $result['partial'][1]['missed_detail']);
    }

    public function test_halaman_hasil_menampilkan_baris_geser_cocok_sebagian_di_akhir(): void
    {
        $this->scoreRows = [
            $this->row('FULL-1', 5, ['skinVia' => 'exact', 'sSkin' => 2, 'c1Via' => 'direct', 'sC1' => 3]),
            $this->row('PART-1', 3, ['skinVia' => '', 'sSkin' => 0, 'c1Via' => 'direct', 'sC1' => 3]),
        ];

        $html = $this->get('/rekomendasi?skin=DrySkinType&concern[]=Acne')->assertOk()
            ->assertSee('Cocok sebagian dengan pilihanmu')
            ->assertSee('60% · Cukup cocok')
            ->assertSee('Masuk karena:')
            ->assertSee('Belum memenuhi:')
            ->getContent();

        $this->assertStringContainsString('id="partial-track"', $html);
        $this->assertStringContainsString('overflow-x-auto', $html);
        // Bagian alternatif ada setelah daftar utama.
        $this->assertGreaterThan(strpos($html, 'id="grid"'), strpos($html, 'id="partial-track"'));
    }

    // 19
    public function test_halaman_hasil_menolak_input_tidak_valid_dengan_pesan(): void
    {
        $this->get('/rekomendasi?skin=Hacker')->assertStatus(422)->assertSee('Pilihan kuis tidak valid');
    }
}
