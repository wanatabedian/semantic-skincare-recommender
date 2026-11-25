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
    private function fakeSparql(array $products, array $facets = [], ?array $options = null, int $total = 88, ?int $matched = null, int $brands = 1): void
    {
        $options ??= self::DEFAULT_OPTIONS;
        $matched ??= count($products);

        $this->partialMock(SparqlService::class, function ($mock) use ($products, $facets, $options, $total, $matched, $brands) {
            $mock->shouldReceive('select')->andReturnUsing(function (string $query) use ($products, $facets, $options, $total, $matched, $brands) {
                preg_match('/# katalog:([\w:]+)/', $query, $m);
                $marker = $m[1] ?? 'lain';
                $this->queries[$marker] = $query;

                if ($marker === 'total') {
                    return [['total' => self::lit((string) $total)]];
                }
                if ($marker === 'jumlah') {
                    return [['total' => self::lit((string) $matched), 'brands' => self::lit((string) $brands)]];
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
        // Fuseki mengembalikan baris sudah terurut (ORDER BY); halaman menampilkan urutan itu apa adanya.
        $this->fakeSparql([
            $this->row('KAH-0039', 'Produk Kahf Uji', 'Kahf', ['categoryLabel' => 'Face spray/toner', 'price' => '50000']),
            $this->row('TAV-0001', 'Produk TAVI Uji', 'TAVI'),
            $this->row('WAR-0010', 'Produk Wardah Uji', 'Wardah', ['categoryLabel' => 'Serum', 'price' => '85000', 'skins' => 'Berminyak|Normal', 'concerns' => 'Jerawat']),
        ], brands: 3);

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

        $response->assertSeeInOrder(['Produk Kahf Uji', 'Produk TAVI Uji', 'Produk Wardah Uji'])
            ->assertSee('3</span> brand', false)
            ->assertDontSee('class="k-pager', false); // ≤ 12 hasil: pager tidak ditampilkan

        // Tanpa filter: query produk tidak memuat FILTER apa pun; urutan default = sourceProductId.
        $this->assertStringNotContainsString('FILTER EXISTS', $this->queries['produk']);
        $this->assertStringNotContainsString('CONTAINS', $this->queries['produk']);
        $this->assertStringContainsString("ORDER BY ASC(COALESCE(?sourceId, STR(?p))) ASC(STR(?p))\nLIMIT 12\nOFFSET 0", $this->queries['produk']);
    }

    public function test_sorting_uses_whitelisted_order_by_with_deterministic_tiebreak(): void
    {
        $tiebreak = 'ASC(COALESCE(?sourceId, STR(?p))) ASC(STR(?p))';
        $expected = [
            'harga-asc' => 'ASC(IF(BOUND(?price) && isNumeric(?price), 0, 1)) ASC(?price) '.$tiebreak,
            'harga-desc' => 'ASC(IF(BOUND(?price) && isNumeric(?price), 0, 1)) DESC(?price) '.$tiebreak,
            'nama-asc' => 'ASC(LCASE(STR(?name))) '.$tiebreak,
            'nama-desc' => 'DESC(LCASE(STR(?name))) '.$tiebreak,
            'relevansi' => $tiebreak,
            'tidak-ada' => $tiebreak,
            urlencode('harga-asc") } DROP ALL #') => $tiebreak,
        ];

        foreach ($expected as $sort => $order) {
            $this->queries = [];
            $this->fakeSparql([]);
            $this->get('/katalog?sort='.$sort.'&brand[]=WardahBrand')->assertOk();

            $this->assertStringContainsString("ORDER BY {$order}\nLIMIT 12", $this->queries['produk'], "sort={$sort}");
            $this->assertStringNotContainsString('DROP', $this->queries['produk']);
            // Sorting tidak mengubah search/filter maupun facet.
            $this->assertStringContainsString('?f_brand IN', $this->queries['produk']);
            $this->assertStringNotContainsString('ORDER BY', $this->queries['facet:brand']);
            $this->assertStringNotContainsString('LIMIT', $this->queries['jumlah']);
        }
    }

    public function test_selected_sort_is_kept_in_select(): void
    {
        $this->fakeSparql([]);

        $html = $this->get('/katalog?sort=nama-desc')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<option value="nama-desc"\s+selected>/', $html);
        $this->assertMatchesRegularExpression('/<option value=""\s*>Relevansi<\/option>/', $html);
        $this->assertSame(1, substr_count($html, 'selected>'));
    }

    public function test_pagination_uses_limit_offset_and_filtered_total(): void
    {
        $rows = array_map(fn ($i) => $this->row(sprintf('WAR-%04d', $i), "Produk {$i}", 'Wardah'), range(13, 24));
        $this->fakeSparql($rows, matched: 30, brands: 2);

        $response = $this->get('/katalog?q=serum&brand[]=WardahBrand&sort=harga-asc&page=2')->assertOk();

        $this->assertStringContainsString("LIMIT 12\nOFFSET 12", $this->queries['produk']);
        $this->assertStringContainsString('LCASE("serum")', $this->queries['jumlah']);
        $this->assertStringContainsString('?f_brand IN', $this->queries['jumlah']);
        $this->assertStringContainsString('COUNT(DISTINCT ?p)', $this->queries['jumlah']);

        $base = route('katalog').'?q=serum&amp;brand%5B%5D=WardahBrand&amp;sort=harga-asc';
        $response->assertSee('13–24</span>', false)
            ->assertSee('30</span> produk', false)
            ->assertSee('Halaman 2 dari 3')
            ->assertSee('aria-current="page"><span class="sr-only">Halaman </span>2</span>', false)
            // Link pager membawa search, filter, dan sort; halaman 1 tanpa parameter page.
            ->assertSee('href="'.$base.'" class="k-pager__btn" rel="prev"', false)
            ->assertSee('href="'.$base.'&amp;page=3" class="k-pager__btn" rel="next"', false)
            ->assertSee('href="'.$base.'&amp;page=3" class="k-pager__num"', false)
            // Chip & reset kembali ke halaman 1 dan mempertahankan sort.
            ->assertSee('href="'.route('katalog').'?brand%5B%5D=WardahBrand&amp;sort=harga-asc" class="k-chip"', false)
            ->assertSee('href="'.route('katalog').'?sort=harga-asc" class="k-chips__reset"', false);
    }

    public function test_pager_window_uses_ellipsis(): void
    {
        $this->fakeSparql([$this->row('WAR-0001', 'Produk', 'Wardah')], matched: 120);

        $html = $this->get('/katalog?page=5')->assertOk()->getContent();

        preg_match_all('/<li[^>]*>\s*(?:<a[^>]*>|<span[^>]*>)?(?:<span class="sr-only">Halaman <\/span>)?(\d+|…)/', $html, $m);
        $this->assertSame(['1', '…', '4', '5', '6', '…', '10'], $m[1]);
    }

    public function test_invalid_page_numbers_fall_back_to_first_page(): void
    {
        foreach (['abc', '-1', '0', '1.5', '9999999'] as $page) {
            $this->queries = [];
            $this->fakeSparql([], matched: 30);
            // Bukan bilangan bulat positif (atau > 6 digit) -> halaman 1, tanpa error.
            $this->get('/katalog?page='.$page)->assertOk();
            $this->assertStringContainsString('OFFSET 0', $this->queries['produk'], "page={$page}");
        }

        $this->fakeSparql([], matched: 30);
        $this->get('/katalog?page[]=2')->assertOk();
        $this->assertStringContainsString('OFFSET 0', $this->queries['produk']);
    }

    public function test_page_beyond_last_redirects_to_last_valid_page_keeping_state(): void
    {
        $this->fakeSparql([], matched: 30);

        $this->get('/katalog?q=gel&brand[]=WardahBrand&sort=nama-asc&page=99')
            ->assertRedirect(route('katalog').'?q=gel&brand%5B%5D=WardahBrand&sort=nama-asc&page=3');
        $this->assertArrayNotHasKey('produk', $this->queries);
    }

    public function test_page_with_no_results_redirects_to_first_page(): void
    {
        $this->fakeSparql([], matched: 0);

        $this->get('/katalog?q=tidakada&page=2')->assertRedirect(route('katalog').'?q=tidakada');
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
        // Search juga diterapkan pada facet sidebar (hanya Brand).
        $this->assertStringContainsString($expected, $this->queries['facet:brand']);
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

    public function test_sidebar_only_has_brand_facet_that_applies_other_url_filters(): void
    {
        $this->fakeSparql([]);

        $html = $this->get('/katalog?brand[]=WardahBrand&concern[]=Acne&harga[]=50-100')->assertOk()->getContent();

        // Hanya facet Brand yang dihitung dan ditampilkan.
        $facets = array_values(array_filter(array_keys($this->queries), fn ($m) => str_starts_with($m, 'facet:')));
        $this->assertSame(['facet:brand'], $facets);
        $this->assertStringContainsString('COUNT(DISTINCT ?p)', $this->queries['facet:brand']);
        $this->assertStringContainsString('data-group="brand"', $html);
        foreach (['kategori', 'kulit', 'concern', 'harga'] as $group) {
            $this->assertStringNotContainsString('data-group="'.$group.'"', $html);
        }

        // Facet Brand mengabaikan pilihan brand sendiri, tetapi tetap memakai filter lain dari URL.
        $this->assertStringNotContainsString('?f_brand', $this->queries['facet:brand']);
        $this->assertStringContainsString('?f_concern', $this->queries['facet:brand']);
        $this->assertStringContainsString('?f_harga', $this->queries['facet:brand']);

        // Filter dari URL tanpa panel ikut terkirim lagi lewat input hidden.
        $this->assertStringContainsString('<input type="hidden" name="concern[]" value="Acne">', $html);
        $this->assertStringContainsString('<input type="hidden" name="harga[]" value="50-100">', $html);
        $this->assertStringNotContainsString('type="hidden" name="brand[]"', $html);
    }

    public function test_category_link_from_landing_still_filters_catalog(): void
    {
        $this->fakeSparql([]);

        $this->get('/katalog?kategori[]=SerumCategory')->assertOk()
            ->assertSee('<input type="hidden" name="kategori[]" value="SerumCategory">', false);

        $this->assertStringContainsString('FILTER(?f_kategori IN (<'.self::NS.'SerumCategory>))', $this->queries['produk']);
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
            // Kategori tidak lagi punya panel di sidebar.
            ->assertDontSee('<span class="sr-only">, </span>7<span class="sr-only"> produk</span>', false);

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
