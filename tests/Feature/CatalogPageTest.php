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

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            File::delete($file);
        }
        parent::tearDown();
    }

    /** Satu baris hasil SPARQL seperti yang dikembalikan Fuseki untuk PRODUCTS_QUERY. */
    private function row(string $id, string $name, string $brand, array $extra = []): array
    {
        $lit = fn (string $v) => ['type' => 'literal', 'value' => $v];

        return array_merge([
            'p' => ['type' => 'uri', 'value' => self::NS.$id],
            'sourceId' => $lit($id),
            'name' => $lit($name),
            'brandIri' => ['type' => 'uri', 'value' => self::NS.$brand.'Brand'],
            'brandLabel' => $lit($brand),
        ], array_map($lit, $extra));
    }

    private function mockProducts(array $rows): void
    {
        $this->mock(SparqlService::class)
            ->shouldReceive('select')
            ->with(CatalogController::PRODUCTS_QUERY)
            ->andReturn($rows);
    }

    public function test_catalog_lists_products_from_every_brand_with_actual_fields(): void
    {
        $this->mockProducts([
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
            ->assertSee('3</span> produk', false);

        // Urutan stabil mengikuti sourceProductId.
        $response->assertSeeInOrder(['Produk Kahf Uji', 'Produk TAVI Uji', 'Produk Wardah Uji']);
    }

    public function test_products_without_photo_show_fallback_and_are_not_hidden(): void
    {
        $this->mockProducts([$this->row('TAV-0008', 'Produk Tanpa Foto', 'TAVI')]);

        $this->get('/katalog')
            ->assertOk()
            ->assertSee('Produk Tanpa Foto')
            ->assertSee('Foto belum tersedia')
            ->assertDontSee('images/products/tavi/TAV-0008', false);
    }

    public function test_local_photo_is_matched_by_source_product_id_in_brand_folder(): void
    {
        $file = public_path('images/products/kahf/KAH-0039.webp');
        File::ensureDirectoryExists(dirname($file));
        File::put($file, 'uji');
        $this->createdFiles[] = $file;

        $this->mockProducts([$this->row('KAH-0039', 'Produk Kahf Uji', 'Kahf')]);

        $this->get('/katalog')
            ->assertOk()
            ->assertSee(asset('images/products/kahf/KAH-0039.webp'), false);
    }

    public function test_catalog_still_opens_when_fuseki_is_down(): void
    {
        $this->mock(SparqlService::class)
            ->shouldReceive('select')
            ->andThrow(new SparqlException('Server Fuseki tidak dapat dihubungi.'));

        $this->get('/katalog')
            ->assertOk()
            ->assertSee('Jumlah produk belum dapat dimuat')
            ->assertSee('Server Fuseki tidak dapat dihubungi.');
    }

    public function test_catalog_keeps_search_keyword_escaped(): void
    {
        $this->mockProducts([]);

        $this->get('/katalog?q=<b>serum</b>')
            ->assertOk()
            ->assertSee('&lt;b&gt;serum&lt;/b&gt;', false)
            ->assertDontSee('<b>serum</b>', false);
    }
}
