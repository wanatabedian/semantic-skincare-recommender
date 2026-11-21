<?php

namespace Tests\Feature;

use App\Services\FusekiService;
use RuntimeException;
use Tests\TestCase;

class CatalogPageTest extends TestCase
{
    public function test_catalog_page_shows_product_count_from_ontology(): void
    {
        $this->mock(FusekiService::class)
            ->shouldReceive('query')
            ->andReturn([['total' => ['value' => '42']]]);

        $this->get('/katalog')
            ->assertOk()
            ->assertSee('Katalog Aphrodite')
            ->assertSee('42')
            ->assertSee('Rak sedang ditata')
            ->assertSee('css/katalog.css', false)
            ->assertSee('js/katalog.js', false);
    }

    public function test_catalog_page_still_opens_when_fuseki_is_down(): void
    {
        $this->mock(FusekiService::class)
            ->shouldReceive('query')
            ->andThrow(new RuntimeException('Fuseki tidak dapat diakses.'));

        $this->get('/katalog')
            ->assertOk()
            ->assertSee('Jumlah produk belum dapat dimuat')
            ->assertSee('Data produk belum dapat dimuat');
    }

    public function test_catalog_keeps_search_keyword_escaped(): void
    {
        $this->mock(FusekiService::class)
            ->shouldReceive('query')
            ->andReturn([['total' => ['value' => '3']]]);

        $this->get('/katalog?q=<b>serum</b>')
            ->assertOk()
            ->assertSee('&lt;b&gt;serum&lt;/b&gt;', false)
            ->assertDontSee('<b>serum</b>', false);
    }
}
