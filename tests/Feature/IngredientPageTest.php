<?php

namespace Tests\Feature;

use App\Http\Controllers\IngredientController;
use App\Services\SparqlException;
use App\Services\SparqlService;
use Tests\TestCase;

class IngredientPageTest extends TestCase
{
    private const NS = 'https://example.org/skincare-ontology#';

    /** @var array<string, string> penanda query (# bahan:…) => isi query terakhir */
    private array $queries = [];

    private static function lit(string $v, ?string $lang = null): array
    {
        return ['type' => 'literal', 'value' => $v] + ($lang !== null ? ['xml:lang' => $lang] : []);
    }

    private static function uri(string $local): array
    {
        return ['type' => 'uri', 'value' => self::NS.$local];
    }

    private function ingredientRow(string $id, ?string $label, int $products): array
    {
        return array_filter([
            'i' => self::uri($id),
            'labelId' => $label !== null ? self::lit($label, 'id') : null,
            'allLabels' => $label !== null ? self::lit($label) : null,
            'products' => self::lit((string) $products),
        ]);
    }

    private function productRow(string $id, string $name, string $brand, string $category): array
    {
        return [
            'p' => self::uri($id),
            'sourceId' => self::lit($id),
            'name' => self::lit($name),
            'brandIri' => self::uri($brand.'Brand'),
            'brandLabel' => self::lit($brand),
            'categoryLabel' => self::lit($category),
            'price' => self::lit('85000'),
        ];
    }

    private const LIST = [
        ['Niacinamide', 'Niacinamide (Vitamin B3)', 18],
        ['AHA', 'AHA (Glycolic Acid)', 5],
        ['SalicylicAcid', 'Salicylic Acid (BHA)', 5],
        ['VitaminC', 'Vitamin C', 6],
        ['Retinol', 'Retinol', 3],
    ];

    /**
     * Fuseki palsu per jenis query (penanda "# bahan:…").
     *
     * @param  array<string, array>|null  $details  id bahan => baris DETAIL_QUERY (kosong = bukan bahan)
     * @param  array<string, array>  $products  id bahan => baris PRODUCTS_QUERY
     */
    private function fakeSparql(?array $list = null, array $search = [], array $details = [], array $products = []): void
    {
        $list ??= array_map(fn ($r) => $this->ingredientRow(...$r), self::LIST);

        $this->partialMock(SparqlService::class, function ($mock) use ($list, $search, $details, $products) {
            $mock->shouldReceive('select')->andReturnUsing(function (string $query) use ($list, $search, $details, $products) {
                preg_match('/# bahan:([\w-]+)/', $query, $m);
                $marker = $m[1] ?? 'lain';
                $this->queries[$marker] = $query;

                return match ($marker) {
                    'daftar' => $list,
                    'produk-terhubung' => [['n' => self::lit('69')]],
                    'cari' => array_map(fn ($id) => ['i' => self::uri($id)], $search),
                    'detail' => $this->rowsFor($query, $details),
                    'produk' => $this->rowsFor($query, $products),
                    default => [],
                };
            });
        });
    }

    private function rowsFor(string $query, array $byId): array
    {
        foreach ($byId as $id => $rows) {
            if (str_contains($query, '<'.self::NS.$id.'>')) {
                return $rows;
            }
        }

        return [];
    }

    public function test_directory_lists_every_ingredient_grouped_by_letter_with_correct_links(): void
    {
        $this->fakeSparql();

        $response = $this->get('/bahan')->assertOk();
        $html = $response->getContent();

        $response->assertSee('Niacinamide (Vitamin B3)')
            ->assertSee('Salicylic Acid (BHA)')
            ->assertSee('>5</dd>', false)   // jumlah bahan tercatat
            ->assertSee('>69</dd>', false)  // produk terhubung
            ->assertSee('href="'.route('bahan.detail', ['id' => 'SalicylicAcid']).'"', false)
            ->assertSee('Dipakai di <strong class="tabular-nums">18</strong> produk', false);
        $this->assertMatchesRegularExpression('/aria-current="page"\s*>Bahan<\/a>/', $html);

        // Urut nama, dikelompokkan per huruf awal; huruf tanpa bahan bukan tautan.
        $response->assertSeeInOrder(['AHA (Glycolic Acid)', 'Niacinamide (Vitamin B3)', 'Retinol', 'Salicylic Acid (BHA)', 'Vitamin C']);
        foreach (['A', 'N', 'R', 'S', 'V'] as $letter) {
            $this->assertStringContainsString('<section id="huruf-'.$letter.'"', $html);
            $this->assertStringContainsString('href="#huruf-'.$letter.'"', $html);
        }
        $this->assertStringNotContainsString('href="#huruf-B"', $html);
        $this->assertSame(21, substr_count($html, 'class="b-az__link is-off" aria-hidden="true"'));
    }

    public function test_label_falls_back_and_unsafe_iris_are_skipped(): void
    {
        $this->fakeSparql([
            $this->ingredientRow('PlantOil', null, 1),
            ['i' => ['type' => 'uri', 'value' => 'https://lain.example/x#Asing'], 'products' => self::lit('2')],
            ['i' => self::uri('Bad>Id'), 'labelId' => self::lit('Jahat'), 'products' => self::lit('1')],
        ]);

        $this->get('/bahan')->assertOk()
            ->assertSee('Plant Oil')       // tanpa label: nama lokal dirapikan
            ->assertDontSee('Asing')
            ->assertDontSee('Jahat');
    }

    public function test_search_uses_escaped_case_insensitive_contains_and_hides_non_matches(): void
    {
        $this->fakeSparql(search: ['VitaminC', 'Niacinamide']);

        $html = $this->get('/bahan?q='.urlencode('ViTaMin") } ; DROP ALL #'))->assertOk()->getContent();

        $this->assertStringContainsString('FILTER(CONTAINS(LCASE(STR(?l)), LCASE("ViTaMin\\") } ; DROP ALL #")))', $this->queries['cari']);
        $this->assertMatchesRegularExpression('/data-ingredient="VitaminC"[^>]*style="--i: \d+"\s*>/s', $html);
        $this->assertMatchesRegularExpression('/data-ingredient="Retinol"[^>]*hidden\s*>/s', $html);
        // Seksi & huruf tanpa hasil dinonaktifkan; empty state tidak tampil karena ada hasil.
        $this->assertMatchesRegularExpression('/<section id="huruf-R"[^>]*hidden/s', $html);
        $this->assertMatchesRegularExpression('/href="#huruf-R"[^>]*class="b-az__link is-off"/s', $html);
        $this->assertMatchesRegularExpression('/<div id="bahan-empty" class="mt-10"\s+hidden\s*>/', $html);
        $this->assertStringContainsString('dari 5 bahan cocok dengan', $html);
    }

    public function test_search_without_results_shows_empty_state(): void
    {
        $this->fakeSparql(search: []);

        $html = $this->get('/bahan?q=zzz')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<div id="bahan-empty" class="mt-10"\s*>/', $html);
        $this->assertStringContainsString('Bahan tidak ditemukan', $html);
        $this->assertSame(5, preg_match_all('/class="b-card"[^>]*hidden\s*>/s', $html));
    }

    public function test_detail_shows_only_ontology_data_and_products_with_real_ids(): void
    {
        $this->fakeSparql(
            details: ['Niacinamide' => [
                ['label' => self::lit('Niacinamide (Vitamin B3)', 'id'), 'lang' => self::lit('id')],
                ['label' => self::lit('Niacinamide', 'en'), 'lang' => self::lit('en')],
            ]],
            products: ['Niacinamide' => [
                $this->productRow('EMI-0003', 'Produk Emina', 'Emina', 'Serum & Essence'),
                $this->productRow('WAR-0052', 'Produk Wardah', 'Wardah', 'Serum & Essence'),
            ]],
        );

        $response = $this->get('/bahan/Niacinamide')->assertOk();

        $response->assertSee('Niacinamide (Vitamin B3)')
            ->assertSee('ex:Niacinamide</code>', false)
            ->assertSee('Produk Emina')
            ->assertSee('Produk Wardah')
            ->assertSee('href="'.route('produk.detail', ['id' => 'EMI-0003']).'"', false)
            ->assertSee('href="'.route('produk.detail', ['id' => 'WAR-0052']).'"', false)
            ->assertSee('belum tersedia di data')
            ->assertSeeInOrder(['Produk Emina', 'Produk Wardah']);

        // Tidak ada bagian yang datanya tidak ada di ontologi.
        $html = $response->getContent();
        foreach (['Fungsi bahan', 'Peringatan', 'Kontraindikasi', 'Konflik dengan'] as $section) {
            $this->assertDoesNotMatchRegularExpression('/<h[23][^>]*>[^<]*'.$section.'/i', $html);
        }

        // Query memakai IRI bahan yang diminta, bukan nama mirip.
        $this->assertStringContainsString('<'.self::NS.'Niacinamide> a ex:Ingredient', $this->queries['detail']);
        $this->assertStringContainsString('ex:hasIngredient <'.self::NS.'Niacinamide>', $this->queries['produk']);
    }

    public function test_detail_without_products_shows_empty_state(): void
    {
        $this->fakeSparql(details: ['Squalane' => [['label' => self::lit('Squalane', 'id'), 'lang' => self::lit('id')]]]);

        $this->get('/bahan/Squalane')->assertOk()
            ->assertSee('Belum ada produk tercatat');
    }

    public function test_unknown_or_invalid_ids_return_404(): void
    {
        $this->fakeSparql(details: ['Niacinamide' => [['label' => self::lit('Niacinamide', 'id'), 'lang' => self::lit('id')]]]);

        foreach (['TidakAda', 'niacinamide', 'CRY-0001'] as $id) {
            $this->get('/bahan/'.$id)->assertNotFound();
        }
        // Ditolak sebelum menyentuh SPARQL (pola route / nama lokal tidak aman).
        $this->get('/bahan/x%20y')->assertNotFound();
        $this->get('/bahan/'.urlencode('Niacinamide> } #'))->assertNotFound();
    }

    public function test_pages_still_open_when_fuseki_is_down(): void
    {
        $this->partialMock(SparqlService::class)
            ->shouldReceive('select')
            ->andThrow(new SparqlException('Server Fuseki tidak dapat dihubungi.'));

        $this->get('/bahan?q=vit')->assertOk()
            ->assertSee('Data bahan belum dapat dimuat')
            ->assertSee('Server Fuseki tidak dapat dihubungi.')
            ->assertSee('Jumlah bahan belum dapat dimuat');

        $this->get('/bahan/Niacinamide')->assertStatus(503)
            ->assertSee('Detail bahan belum dapat dimuat')
            ->assertSee('Server Fuseki tidak dapat dihubungi.');
    }

    public function test_letter_of_groups_non_latin_into_hash(): void
    {
        $this->assertSame('A', IngredientController::letterOf('aha'));
        $this->assertSame('Z', IngredientController::letterOf('  Zinc'));
        $this->assertSame('#', IngredientController::letterOf('3-O-Ethyl'));
        $this->assertSame('#', IngredientController::letterOf('Ésther'));
    }
}
