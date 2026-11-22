<?php

namespace Tests\Feature;

use App\Http\Controllers\CatalogController;
use App\Services\SparqlException;
use App\Services\SparqlService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CatalogPageTest extends TestCase
{
    private const NS = 'https://example.org/skincare-ontology#';

    private array $createdFiles = [];

    /** @var array<string, string> penanda query (# katalog:…) => isi query terakhir */
    private array $queries = [];

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            File::delete($file);
        }
        parent::tearDown();
    }

    private static function lit(string $v): array
    {
        return ['type' => 'literal', 'value' => $v];
    }

    private static function uri(string $local): array
    {
        return ['type' => 'uri', 'value' => self::NS.$local];
    }

    /** Satu baris hasil SPARQL seperti yang dikembalikan Fuseki untuk PRODUCTS_QUERY. */
    private function row(string $id, string $name, string $brand, array $extra = []): array
    {
        return array_merge([
            'p' => self::uri($id),
            'sourceId' => self::lit($id),
            'name' => self::lit($name),
            'brandIri' => self::uri($brand.'Brand'),
            'brandLabel' => self::lit($brand),
        ], array_map([self::class, 'lit'], $extra));
    }

    private function option(string $group, string $local, string $label): array
    {
        return ['group' => self::lit($group), 'opt' => self::uri($local), 'labelId' => self::lit($label)];
    }

    private const DEFAULT_OPTIONS = [
        ['brand', 'WardahBrand', 'Wardah'],
        ['brand', 'EminaBrand', 'Emina'],
        ['kategori', 'SerumCategory', 'Serum & Essence'],
        ['kulit', 'Oily', 'Berminyak'],
        ['concern', 'Acne', 'Jerawat'],
    ];

    /**
     * Fuseki palsu: membalas per jenis query (berdasarkan penanda "# katalog:…")
     * dan menyimpan query yang dikirim untuk diperiksa.
     *
     * @param  array<string, array<string, int>>  $facets  kelompok => [nilai => jumlah]
     */
    private function fakeSparql(array $products, array $facets = [], ?array $options = null, int $total = 88): void
    {
        $options ??= self::DEFAULT_OPTIONS;

        $this->partialMock(SparqlService::class, function ($mock) use ($products, $facets, $options, $total) {
            $mock->shouldReceive('select')->andReturnUsing(function (string $query) use ($products, $facets, $options, $total) {
                preg_match('/# katalog:([\w:]+)/', $query, $m);
                $marker = $m[1] ?? 'lain';
                $this->queries[$marker] = $query;

                if ($marker === 'total') {
                    return [['total' => self::lit((string) $total)]];
                }
                if ($marker === 'opsi') {
                    return array_map(fn ($o) => $this->option(...$o), $options);
                }
                if ($marker === 'produk') {
                    return $products;
                }
                if (str_starts_with($marker, 'facet:')) {
                    $group = substr($marker, 6);
                    $rows = [];
                    foreach ($facets[$group] ?? [] as $value => $n) {
                        $opt = $group === 'harga' ? self::lit($value) : self::uri($value);
                        $rows[] = ['opt' => $opt, 'n' => self::lit((string) $n)];
                    }

                    return $rows;
                }

                return [];
            });
        });
    }

    public function test_catalog_lists_products_from_every_brand_with_actual_fields(): void
    {
        $this->fakeSparql([
            $this->row('WAR-0010', 'Produk Wardah Uji', 'Wardah', ['categoryLabel' => 'Serum', 'price' => '85000', 'skins' => 'Berminyak|Normal', 'concerns' => 'Jerawat']),
            $this->row('KAH-0039', 'Produk Kahf Uji', 'Kahf', ['categoryLabel' => 'Face spray/toner', 'price' => '50000']),
            $this->row('TAV-0001', 'Produk TAVI Uji', 'TAVI'),
        ]);

        $response = $this->get('/katalog')->assertOk();

        $response->assertSee('Produk Wardah Uji')
            ->assertSee('Produk Kahf Uji')
            ->assertSee('Produk TAVI Uji')
            ->assertSee('Rp85.000')
            ->assertSee('Face spray/toner')
            ->assertSee('Berminyak')
            ->assertSee('Harga belum tercatat')
            ->assertSee(route('produk.detail', ['id' => 'KAH-0039']), false)
            ->assertSee('3</span> produk', false)
            ->assertSee('Semua produk');

        // Urutan stabil mengikuti sourceProductId.
        $response->assertSeeInOrder(['Produk Kahf Uji', 'Produk TAVI Uji', 'Produk Wardah Uji']);

        // Tanpa filter: query produk tidak memuat FILTER apa pun.
        $this->assertStringNotContainsString('FILTER EXISTS', $this->queries['produk']);
        $this->assertStringNotContainsString('CONTAINS', $this->queries['produk']);
    }

    public function test_products_without_photo_show_fallback_and_are_not_hidden(): void
    {
        $this->fakeSparql([$this->row('TAV-0008', 'Produk Tanpa Foto', 'TAVI')]);

        $this->get('/katalog')
            ->assertOk()
            ->assertSee('Produk Tanpa Foto')
            ->assertSee('Foto belum tersedia')
            ->assertDontSee('images/tavi/TAV-0008', false);
    }

    public function test_local_photo_is_matched_by_source_product_id_in_brand_folder(): void
    {
        $file = public_path('images/kahf/KAH-9999.webp');
        File::ensureDirectoryExists(dirname($file));
        File::put($file, 'uji');
        $this->createdFiles[] = $file;

        $this->fakeSparql([$this->row('KAH-9999', 'Produk Kahf Uji', 'Kahf')]);

        $this->get('/katalog')
            ->assertOk()
            ->assertSee(asset('images/kahf/KAH-9999.webp'), false);
    }

    public function test_catalog_still_opens_when_fuseki_is_down(): void
    {
        $this->partialMock(SparqlService::class)
            ->shouldReceive('select')
            ->andThrow(new SparqlException('Server Fuseki tidak dapat dihubungi.'));

        $this->get('/katalog?q=serum&brand[]=WardahBrand')
            ->assertOk()
            ->assertSee('Jumlah produk belum dapat dimuat')
            ->assertSee('Server Fuseki tidak dapat dihubungi.')
            ->assertSee('Opsi filter belum dapat dimuat')
            ->assertSee('value="serum"', false);
    }

    public function test_catalog_keeps_search_keyword_escaped(): void
    {
        $this->fakeSparql([]);

        $this->get('/katalog?q=<b>serum</b>')
            ->assertOk()
            ->assertSee('&lt;b&gt;serum&lt;/b&gt;', false)
            ->assertDontSee('<b>serum</b>', false);
    }

    public function test_search_uses_case_insensitive_contains_with_escaped_literal(): void
    {
        $this->fakeSparql([]);

        $this->get('/katalog?q='.urlencode('Gel") } ; DROP ALL ; #'))->assertOk();

        $expected = 'FILTER(CONTAINS(LCASE(STR(?nm)), LCASE("Gel\\") } ; DROP ALL ; #")))';
        $this->assertStringContainsString($expected, $this->queries['produk']);
        // Search juga diterapkan pada setiap facet.
        foreach (['facet:brand', 'facet:kategori', 'facet:kulit', 'facet:concern', 'facet:harga'] as $marker) {
            $this->assertStringContainsString($expected, $this->queries[$marker]);
        }
    }

    public function test_filter_values_outside_ontology_whitelist_are_dropped(): void
    {
        $this->fakeSparql([]);

        $this->get('/katalog?brand[]=WardahBrand&brand[]='.urlencode('X> } ?s ?p ?o { <y').'&brand[]=TidakAda&harga[]=999&kulit=Oily')
            ->assertOk()
            ->assertDontSee('TidakAda');

        $query = $this->queries['produk'];
        $this->assertStringContainsString('FILTER EXISTS { ?p ex:hasBrand ?f_brand . FILTER(?f_brand IN (<'.self::NS.'WardahBrand>)) }', $query);
        $this->assertStringContainsString('FILTER EXISTS { ?p ex:suitableForSkinType ?f_kulit . FILTER(?f_kulit IN (<'.self::NS.'Oily>)) }', $query);
        $this->assertStringNotContainsString('?s ?p ?o', $query);
        $this->assertStringNotContainsString('?f_harga', $query);
    }

    public function test_or_within_group_and_between_groups(): void
    {
        $this->fakeSparql([]);

        $this->get('/katalog?brand[]=WardahBrand&brand[]=EminaBrand&concern[]=Acne')->assertOk();

        $query = $this->queries['produk'];
        // OR dalam kelompok: kedua brand dalam satu IN (…); urutan mengikuti daftar opsi (label A–Z).
        $this->assertStringContainsString('FILTER(?f_brand IN (<'.self::NS.'EminaBrand>, <'.self::NS.'WardahBrand>))', $query);
        // AND antar kelompok: blok FILTER EXISTS terpisah.
        $this->assertSame(2, substr_count($query, 'FILTER EXISTS'));
    }

    public function test_each_facet_ignores_its_own_group_but_applies_the_others(): void
    {
        $this->fakeSparql([]);

        $this->get('/katalog?brand[]=WardahBrand&concern[]=Acne&harga[]=50-100')->assertOk();

        $this->assertStringNotContainsString('?f_brand', $this->queries['facet:brand']);
        $this->assertStringContainsString('?f_concern', $this->queries['facet:brand']);
        $this->assertStringContainsString('?f_harga', $this->queries['facet:brand']);

        $this->assertStringNotContainsString('?f_concern', $this->queries['facet:concern']);
        $this->assertStringContainsString('?f_brand', $this->queries['facet:concern']);

        $this->assertStringNotContainsString('?f_harga', $this->queries['facet:harga']);
        $this->assertStringContainsString('?f_brand', $this->queries['facet:harga']);
        $this->assertStringContainsString('?f_concern', $this->queries['facet:kategori']);

        foreach ($this->queries as $marker => $query) {
            if (str_starts_with($marker, 'facet:')) {
                $this->assertStringContainsString('COUNT(DISTINCT ?p)', $query);
            }
        }
    }

    public function test_price_filter_is_numeric_half_open_and_ignores_missing_price(): void
    {
        $this->fakeSparql([]);

        $this->get('/katalog?harga[]=50-100&harga[]=gte150')->assertOk();

        $query = $this->queries['produk'];
        $this->assertStringContainsString(
            'FILTER EXISTS { ?p ex:estimatedPrice ?f_harga . FILTER(isNumeric(?f_harga) && ((?f_harga >= 50000 && ?f_harga < 100000) || (?f_harga >= 150000))) }',
            $query
        );
        $this->assertStringContainsString('FILTER(isNumeric(?price))', $this->queries['facet:harga']);
        $this->assertStringContainsString('IF((?price < 50000), "lt50", IF((?price >= 50000 && ?price < 100000), "50-100"', $this->queries['facet:harga']);
    }

    public function test_facet_counts_chips_and_reset_are_rendered(): void
    {
        $this->fakeSparql(
            [$this->row('WAR-0001', 'Serum Wardah', 'Wardah')],
            [
                'brand' => ['WardahBrand' => 28, 'EminaBrand' => 0],
                'kategori' => ['SerumCategory' => 7],
                'kulit' => ['Oily' => 5],
                'concern' => ['Acne' => 3],
                'harga' => ['lt50' => 2, '50-100' => 4],
            ],
        );

        $response = $this->get('/katalog?q=serum&brand[]=WardahBrand&harga[]=lt50')->assertOk();
        $html = $response->getContent();

        $response->assertSee('Hasil pilihanmu')
            ->assertSee('Wardah')
            ->assertSee('Di bawah Rp50.000')
            ->assertSee('<span class="sr-only">, </span>28<span class="sr-only"> produk</span>', false)
            ->assertSee('<span class="sr-only">, </span>7<span class="sr-only"> produk</span>', false);

        // Opsi berjumlah 0 yang tidak dipilih dinonaktifkan; opsi terpilih tercentang.
        $this->assertMatchesRegularExpression('/value="EminaBrand"\s+disabled/', $html);
        $this->assertMatchesRegularExpression('/value="WardahBrand"\s+checked/', $html);

        // Chip: masing-masing menghapus satu filter saja, format URL sama dengan form GET.
        $base = route('katalog');
        $response->assertSee('href="'.$base.'?brand%5B%5D=WardahBrand&amp;harga%5B%5D=lt50" class="k-chip"', false)
            ->assertSee('href="'.$base.'?q=serum&amp;harga%5B%5D=lt50" class="k-chip"', false)
            ->assertSee('href="'.$base.'?q=serum&amp;brand%5B%5D=WardahBrand" class="k-chip"', false)
            ->assertSee('href="'.$base.'" class="k-chips__reset"', false);

        // Hero tetap menampilkan total ontologi.
        $response->assertSee('>88</dd>', false);
    }

    public function test_empty_result_with_filters_shows_reset_empty_state(): void
    {
        $this->fakeSparql([]);

        $this->get('/katalog?q=tidakada')
            ->assertOk()
            ->assertSee('Belum ada produk yang cocok')
            ->assertSee('Reset semua filter')
            ->assertDontSee('Belum ada produk di ontologi');
    }

    public function test_search_term_is_sanitized(): void
    {
        $this->assertSame('', CatalogController::searchTerm(['x']));
        $this->assertSame('', CatalogController::searchTerm(null));
        $this->assertSame('', CatalogController::searchTerm("\xC3\x28"));
        $this->assertSame('serum vit c', CatalogController::searchTerm("  serum\n\tvit\x00 c  "));
        $this->assertSame(CatalogController::SEARCH_MAX_LENGTH, mb_strlen(CatalogController::searchTerm(str_repeat('a', 300))));
    }
}
