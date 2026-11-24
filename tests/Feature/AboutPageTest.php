<?php

namespace Tests\Feature;

use Tests\TestCase;

class AboutPageTest extends TestCase
{
    private const TEAM = [
        'Indah Ayu Gemilang' => 'Recommendation Engine',
        'Quinsha Ilmi Azzahra' => 'Knowledge Engineer',
        'Keizya Azalea Azka' => 'Experience & Kuis',
        'Jihan Fadhillah' => 'Produk & Rutinitas',
        'Dian Indriani' => 'Eksplorasi Katalog',
    ];

    public function test_about_page_is_public_and_shows_the_four_sections(): void
    {
        $response = $this->get('/tentang');

        $response->assertOk();
        $this->assertGuest();
        $response->assertSeeInOrder(['Tujuan Aphrodite', 'Metodologi', 'Sumber Data', 'Anggota Tim']);
        foreach (['id="tujuan"', 'id="metodologi"', 'id="sumber-data"', 'id="tim"'] as $anchor) {
            $response->assertSee($anchor, false);
        }
    }

    public function test_about_page_lists_all_team_members_with_their_roles(): void
    {
        $response = $this->get(route('tentang'));

        foreach (self::TEAM as $name => $role) {
            $response->assertSee($name);
            $response->assertSee($role);
        }
        $response->assertSeeInOrder(array_keys(self::TEAM));
    }

    public function test_navbar_links_to_the_about_page_on_desktop_and_mobile(): void
    {
        $html = $this->get(route('tentang'))->getContent();

        $this->assertSame(1, substr_count($html, 'href="'.route('tentang').'"'), 'Tautan Tentang pada navbar responsif harus tersedia untuk desktop dan mobile.');
        // Tautan lama tetap ada.
        $this->assertStringContainsString('href="'.route('katalog').'"', $html);
        $this->assertStringContainsString('href="'.route('bahan').'"', $html);
        $this->assertStringContainsString('href="'.route('kuis', ['new' => 1]).'"', $html);
    }

    public function test_data_source_numbers_match_the_clean_ontology(): void
    {
        $ttl = file_get_contents(base_path('ontology/skincare_ontology_clean.ttl'));
        $products = preg_match_all('/^ex:\S+ a ex:Product\b/m', $ttl);
        $brands = preg_match_all('/^ex:\S+ a ex:Brand\b/m', $ttl);
        preg_match_all('/ex:verificationStatus "([^"]*)"/', $ttl, $status);
        $official = count(array_filter($status[1], fn ($s) => str_contains(strtolower($s), 'situs resmi')));
        $retailer = count(array_filter($status[1], fn ($s) => ! str_contains(strtolower($s), 'situs resmi') && str_contains(strtolower($s), 'retailer')));
        $needsCheck = count(array_filter($status[1], fn ($s) => str_contains(strtolower($s), 'perlu cek')));

        $response = $this->get(route('tentang'));

        $response->assertSee("{$products} produk");
        $response->assertSee("{$brands} merek");
        $response->assertSee("{$official} produk</strong> dicatat terverifikasi dari situs resmi", false);
        $response->assertSee("{$retailer} produk</strong> dari retailer", false);
        $response->assertSee("{$needsCheck} produk masih memiliki catatan");
        $response->assertSee(route('ontology.download'), false);
    }
}
