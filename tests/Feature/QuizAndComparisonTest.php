<?php

namespace Tests\Feature;

use App\Services\FusekiService;
use Tests\TestCase;

class QuizAndComparisonTest extends TestCase
{
    private function quizOptions(): array
    {
        return [
            'age_groups' => [['value' => 'Teen', 'label' => 'Teen'], ['value' => 'Adult', 'label' => 'Adult']],
            'skin_types' => [['value' => 'Oily', 'label' => 'Berminyak']],
            'categories' => [['value' => 'SerumCategory', 'label' => 'Serum']],
            'concerns' => [['value' => 'Acne', 'label' => 'Jerawat'], ['value' => 'SkinBarrier', 'label' => 'Skin Barrier'], ['value' => 'Cleansing', 'label' => 'Pembersihan']],
            'ingredients' => [['value' => 'Niacinamide', 'label' => 'Niacinamide']],
        ];
    }

    public function test_quiz_accepts_empty_partial_and_complete_answers(): void
    {
        $fuseki = $this->mock(FusekiService::class);
        $fuseki->shouldReceive('options')->andReturn($this->quizOptions());

        $this->postJson('/api/quiz/analyze', [])->assertOk();
        $this->postJson('/api/quiz/analyze', ['skin' => 'Oily', 'concern' => ['Acne']])->assertOk();
        $this->postJson('/api/quiz/analyze', [
            'age' => 'Teen', 'skin' => 'Oily', 'category' => 'SerumCategory',
            'ingredient' => 'Niacinamide', 'concern' => ['Acne', 'SkinBarrier'],
            'price_min' => 50000, 'price_max' => 100000,
        ])->assertOk();
    }

    public function test_quiz_rejects_third_concern_and_options_outside_ontology(): void
    {
        $fuseki = $this->mock(FusekiService::class);
        $fuseki->shouldReceive('options')->andReturn($this->quizOptions());

        $this->postJson('/api/quiz/analyze', ['concern' => ['Acne', 'SkinBarrier', 'Cleansing']])->assertUnprocessable();
        $this->postJson('/api/quiz/analyze', ['age' => 'Unknown'])->assertUnprocessable();
        $this->postJson('/api/quiz/analyze', ['ingredient' => 'NotInOntology'])->assertUnprocessable();
    }

    public function test_comparison_rejects_fourth_product_and_uses_values_for_valid_ids(): void
    {
        $fuseki = $this->mock(FusekiService::class);
        $fuseki->shouldReceive('query')->once()->withArgs(fn ($query) => str_contains($query, 'VALUES ?product { ex:EMI-0001 }'))->andReturn([]);
        $fuseki->shouldReceive('query')->once()->withArgs(fn ($query) => str_contains($query, 'VALUES ?product { ex:EMI-0001 ex:WAR-0001 }'))->andReturn([]);
        $fuseki->shouldReceive('query')->once()->withArgs(fn ($query) => str_contains($query, 'VALUES ?product { ex:EMI-0001 ex:WAR-0001 ex:TAV-0001 }'))->andReturn([]);

        $this->getJson('/api/products/compare?ids[]=EMI-0001')->assertOk();
        $this->getJson('/api/products/compare?ids[]=EMI-0001&ids[]=WAR-0001')->assertOk()->assertJsonPath('products', []);
        $this->getJson('/api/products/compare?ids[]=EMI-0001&ids[]=WAR-0001&ids[]=TAV-0001')->assertOk();
        $this->getJson('/api/products/compare?ids[]=A&ids[]=B&ids[]=C&ids[]=D')->assertUnprocessable();
    }

    public function test_quiz_and_comparison_pages_render(): void
    {
        $this->get('/kuis')->assertOk()->assertSee('Kuis Analisis Kulit');
        $this->get('/bandingkan')->assertOk()->assertSee('Bandingkan skincare');
    }

    public function test_recommendation_query_parameters_are_forwarded_as_sparql_filters(): void
    {
        $fuseki = $this->mock(FusekiService::class);
        $fuseki->shouldReceive('options')->andReturn($this->quizOptions());
        $fuseki->shouldReceive('query')->once()->withArgs(fn ($query) => str_contains($query, '?skin = ex:Oily') && str_contains($query, '?price >= 50000'))->andReturn([]);

        $this->get('/rekomendasi?skin=Oily&price_min=50000')->assertOk()->assertSee('Belum ada produk yang cocok');
    }

    public function test_product_detail_displays_readable_rdf_values_without_duplicates_or_uris(): void
    {
        $fuseki = \Mockery::mock(FusekiService::class)->makePartial();
        // Query ke-2 adalah produk serupa; dikosongkan karena test ini fokus pada detail produk.
        $fuseki->shouldReceive('query')->twice()->andReturn([
            [
                'name' => ['type' => 'literal', 'value' => 'Aphrodite Serum'],
                'skin' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#Dry'],
                'age' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#Normal'],
                'ageLabel' => ['type' => 'literal', 'value' => 'Usia normal'],
                'concern' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#Acne'],
                'concernLabel' => ['type' => 'literal', 'value' => 'Jerawat'],
                'ingredient' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#Probiotic'],
                'claim' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#TranexamicAcid'],
                'claimLabel' => ['type' => 'literal', 'value' => 'Tranexamic Acid'],
            ],
            [
                'name' => ['type' => 'literal', 'value' => 'Aphrodite Serum'],
                'skin' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#Dry'],
                'ingredient' => ['type' => 'literal', 'value' => 'Ceramide'],
            ],
        ], []);

        $this->app->instance(FusekiService::class, $fuseki);

        $response = $this->get('/produk/APH-0001')->assertOk();

        $response->assertSee('Dry')
            ->assertSee('Usia normal')
            ->assertSee('Jerawat')
            ->assertSee('Probiotic')
            ->assertSee('Tranexamic Acid')
            ->assertSee('Ceramide')
            ->assertDontSee('https://example.org/skincare-ontology#');
        $this->assertSame(1, substr_count($response->getContent(), '>Dry</li>'));
    }

    public function test_quiz_options_use_clean_ontology_resources_and_rdfs_labels(): void
    {
        $fuseki = \Mockery::mock(FusekiService::class)->makePartial();
        $fuseki->shouldReceive('query')->once()->withArgs(fn ($query) => str_contains($query, '?skin a ex:SkinType') && str_contains($query, '?ingredient a ex:Ingredient'))->andReturn([
            ['age' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#Teen'], 'ageLabel' => ['type' => 'literal', 'value' => 'Remaja']],
            ['skin' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#Oily'], 'skinLabel' => ['type' => 'literal', 'value' => 'Berminyak']],
            ['category' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#CleanserCategory'], 'categoryLabel' => ['type' => 'literal', 'value' => 'Pembersih Wajah (Cleanser)']],
            ['concern' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#Acne'], 'concernLabel' => ['type' => 'literal', 'value' => 'Jerawat']],
            ['ingredient' => ['type' => 'uri', 'value' => 'https://example.org/skincare-ontology#Niacinamide'], 'ingredientLabel' => ['type' => 'literal', 'value' => 'Niacinamide (Vitamin B3)']],
        ]);

        $options = $fuseki->options();
        $this->assertSame(['value' => 'Oily', 'label' => 'Berminyak'], $options['skin_types'][0]);
        $this->assertSame(['value' => 'Niacinamide', 'label' => 'Niacinamide (Vitamin B3)'], $options['ingredients'][0]);
        $this->assertSame('Pembersih Wajah (Cleanser)', $options['categories'][0]['label']);
    }
}
