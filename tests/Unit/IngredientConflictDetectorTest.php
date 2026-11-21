<?php

namespace Tests\Unit;

use App\Services\IngredientConflictDetector;
use PHPUnit\Framework\TestCase;

/**
 * Data produk di bawah diambil dari ontology/skincare_ontology_clean.ttl
 * (ID, nama, dan bahan ex:hasIngredient yang sebenarnya).
 */
class IngredientConflictDetectorTest extends TestCase
{
    private IngredientConflictDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new IngredientConflictDetector;
    }

    private function product(string $id, array $ingredients, ?string $name = null): array
    {
        return ['id' => $id, 'name' => $name ?? "Produk {$id}", 'ingredients' => $ingredients];
    }

    // TAV-0004: Retinol + Peptide (serum)
    private function tav0004(): array
    {
        return $this->product('TAV-0004', ['Peptide', 'Retinol'], 'TAVI 1% Encapsulated Retinol + Peptide');
    }

    // TAV-0002: PHA + SalicylicAcid + Ceramide (toner)
    private function tav0002(): array
    {
        return $this->product('TAV-0002', ['Ceramide', 'PHA', 'SalicylicAcid'], 'TAVI Natural PHA + BHA Retexture Toner');
    }

    // ---------------------------------------------------------------
    // Aturan 1: Retinol + eksfolian kimia (AHA/BHA) -> Perlu diperhatikan
    // ---------------------------------------------------------------

    public function test_retinol_and_salicylic_acid_in_different_products_need_attention(): void
    {
        $result = $this->detector->detect([$this->tav0004(), $this->tav0002()]);

        $this->assertCount(1, $result['conflicts']);
        $conflict = $result['conflicts'][0];

        $this->assertSame('retinoid-chemical-exfoliant', $conflict['rule']);
        $this->assertSame(IngredientConflictDetector::LEVEL_CAUTION, $conflict['level']);
        $this->assertSame('Perlu diperhatikan', $conflict['level_label']);
        $this->assertSame(['Retinol', 'SalicylicAcid'], $conflict['ingredients']);   // PHA tidak ikut (aturan PHA belum aktif)
        $this->assertSame(
            'Kombinasi Retinol dengan eksfolian kimia seperti AHA atau BHA berpotensi meningkatkan risiko kulit kering dan iritasi. '
            .'Pertimbangkan untuk memisahkan waktu pemakaiannya.',
            $conflict['reason']
        );

        // Nama, ID, dan bahan yang terlibat per produk.
        $this->assertSame('TAV-0004', $conflict['products'][0]['id']);
        $this->assertSame('TAVI 1% Encapsulated Retinol + Peptide', $conflict['products'][0]['name']);
        $this->assertSame(['Retinol'], array_column($conflict['products'][0]['ingredients'], 'id'));
        $this->assertSame('TAV-0002', $conflict['products'][1]['id']);
        $this->assertSame(['SalicylicAcid'], array_column($conflict['products'][1]['ingredients'], 'id'));
    }

    public function test_retinol_and_aha_need_attention_in_either_order(): void
    {
        // WAR-0019 berisi AHA + Niacinamide.
        $aha = $this->product('WAR-0019', ['AHA', 'Niacinamide']);

        foreach ([[$this->tav0004(), $aha], [$aha, $this->tav0004()]] as $products) {
            $conflicts = $this->detector->detect($products)['conflicts'];
            $this->assertCount(1, $conflicts);
            $this->assertSame('retinoid-chemical-exfoliant', $conflicts[0]['rule']);
            $this->assertSame(IngredientConflictDetector::LEVEL_CAUTION, $conflicts[0]['level']);
            $this->assertSame('Perlu diperhatikan', $conflicts[0]['level_label']);
            $this->assertEqualsCanonicalizing(['Retinol', 'AHA'], $conflicts[0]['ingredients']);
        }
    }

    // ---------------------------------------------------------------
    // Aturan 2: dua produk eksfolian kimia -> Perlu diperhatikan
    // ---------------------------------------------------------------

    public function test_aha_and_salicylic_acid_in_different_products_need_attention(): void
    {
        $result = $this->detector->detect([
            $this->product('KAH-0044', ['AHA']),
            $this->product('LAB-0055', ['Niacinamide', 'SalicylicAcid']),
        ]);

        $this->assertCount(1, $result['conflicts']);
        $this->assertSame('multiple-chemical-exfoliants', $result['conflicts'][0]['rule']);
        $this->assertSame(IngredientConflictDetector::LEVEL_CAUTION, $result['conflicts'][0]['level']);
        $this->assertSame('Perlu diperhatikan', $result['conflicts'][0]['level_label']);
        $this->assertSame(['AHA', 'SalicylicAcid'], $result['conflicts'][0]['ingredients']);
        $this->assertSame(
            'Penggunaan beberapa produk eksfolian dalam waktu yang sama dapat meningkatkan risiko eksfoliasi berlebihan. '
            .'Perhatikan frekuensi pemakaian dan reaksi kulit.',
            $result['conflicts'][0]['reason']
        );
    }

    public function test_two_products_with_the_same_exfoliant_acid_need_attention(): void
    {
        $result = $this->detector->detect([
            $this->product('LAB-0055', ['Niacinamide', 'SalicylicAcid']),
            $this->product('LAB-0056', ['Niacinamide', 'SalicylicAcid', 'Zinc']),
        ]);

        $this->assertCount(1, $result['conflicts']);
        $this->assertSame('multiple-chemical-exfoliants', $result['conflicts'][0]['rule']);
        $this->assertSame('Perlu diperhatikan', $result['conflicts'][0]['level_label']);
        $this->assertSame(['SalicylicAcid'], $result['conflicts'][0]['ingredients']);
    }

    public function test_both_rules_are_reported_in_detection_order_with_the_same_level(): void
    {
        // LAB-0054: Retinol + SalicylicAcid + Probiotic; KAH-0044: AHA.
        $result = $this->detector->detect([
            $this->product('LAB-0054', ['Probiotic', 'Retinol', 'SalicylicAcid']),
            $this->product('KAH-0044', ['AHA']),
        ]);

        // Kedua aturan sama-sama "perhatikan" -> urutan mengikuti urutan aturan di RULES.
        $this->assertSame(['retinoid-chemical-exfoliant', 'multiple-chemical-exfoliants'], array_column($result['conflicts'], 'rule'));
        $this->assertSame([IngredientConflictDetector::LEVEL_CAUTION, IngredientConflictDetector::LEVEL_CAUTION], array_column($result['conflicts'], 'level'));
        $this->assertSame(['Retinol', 'AHA'], $result['conflicts'][0]['ingredients']);
        $this->assertSame(['SalicylicAcid', 'AHA'], $result['conflicts'][1]['ingredients']);
    }

    public function test_conflicts_keep_product_pair_order_across_several_products(): void
    {
        // Pasangan diperiksa sesuai urutan input: (TAV-0004, TAV-0002), (TAV-0004, KAH-0044), (TAV-0002, KAH-0044).
        $result = $this->detector->detect([$this->tav0004(), $this->tav0002(), $this->product('KAH-0044', ['AHA'])]);

        $this->assertSame([
            ['retinoid-chemical-exfoliant', 'TAV-0004', 'TAV-0002'],
            ['retinoid-chemical-exfoliant', 'TAV-0004', 'KAH-0044'],
            ['multiple-chemical-exfoliants', 'TAV-0002', 'KAH-0044'],
        ], array_map(fn ($c) => [$c['rule'], $c['products'][0]['id'], $c['products'][1]['id']], $result['conflicts']));
    }

    public function test_no_active_rule_uses_the_avoid_level(): void
    {
        foreach (IngredientConflictDetector::RULES as $rule) {
            $this->assertSame(IngredientConflictDetector::LEVEL_CAUTION, $rule['level'], $rule['id']);
        }
    }

    // ---------------------------------------------------------------
    // Hal yang TIDAK boleh dianggap konflik
    // ---------------------------------------------------------------

    public function test_combination_inside_one_product_is_not_checked(): void
    {
        // LAB-0054 sendiri berisi Retinol + SalicylicAcid; WAR-0010 sendiri berisi AHA + PHA.
        $result = $this->detector->detect([
            $this->product('LAB-0054', ['Probiotic', 'Retinol', 'SalicylicAcid']),
            $this->product('EMI-0026', ['Niacinamide']),
        ]);

        $this->assertSame([], $result['conflicts']);
        $this->assertSame([], $this->detector->detect([$this->product('WAR-0010', ['AHA', 'PHA'])])['conflicts']);
    }

    public function test_same_product_listed_twice_is_not_compared_with_itself(): void
    {
        $result = $this->detector->detect([$this->tav0002(), $this->tav0002()]);

        $this->assertSame([], $result['conflicts']);
        $this->assertSame(1, $result['checked_count']);
    }

    public function test_inactive_rules_without_enough_evidence_never_produce_warnings(): void
    {
        $pairs = [
            'Retinol + PHA' => [['Retinol'], ['PHA']],
            'PHA + AHA' => [['PHA'], ['AHA']],
            'PHA + SalicylicAcid' => [['PHA'], ['SalicylicAcid']],
            'Retinol + Retinol' => [['Retinol'], ['Retinol']],
            'VitaminC + AHA' => [['VitaminC'], ['AHA']],
            'VitaminC + SalicylicAcid' => [['VitaminC'], ['SalicylicAcid']],
            'Retinol + VitaminC' => [['Retinol'], ['VitaminC']],
            'Cysteamine + AHA' => [['Cysteamine'], ['AHA']],
            'Peptide + AHA' => [['Peptide'], ['AHA']],
            'Niacinamide + VitaminC' => [['Niacinamide'], ['VitaminC']],
        ];

        foreach ($pairs as $label => [$a, $b]) {
            $result = $this->detector->detect([$this->product('AAA-0001', $a), $this->product('BBB-0002', $b)]);
            $this->assertSame([], $result['conflicts'], "{$label} tidak boleh menjadi peringatan");
        }
    }

    public function test_common_non_irritating_duplicates_are_not_conflicts(): void
    {
        $result = $this->detector->detect([
            $this->product('EMI-0026', ['Niacinamide']),
            $this->product('EMI-0027', ['Niacinamide', 'HyaluronicAcid']),
            $this->product('CRY-0001', ['Peptide', 'HyaluronicAcid']),
            $this->product('LAB-0056', ['Niacinamide', 'SalicylicAcid', 'Zinc']),
        ]);

        $this->assertSame([], $result['conflicts']);
        $this->assertSame(4, $result['checked_count']);
    }

    // ---------------------------------------------------------------
    // Produk tanpa data bahan: tidak dapat diperiksa, bukan aman
    // ---------------------------------------------------------------

    public function test_products_without_ingredient_data_are_marked_unchecked(): void
    {
        $result = $this->detector->detect([
            $this->tav0004(),
            $this->product('KAH-0047', [], 'Bright Fresh Sunscreen Serum SPF 35 PA++++'),
            ['id' => 'WAR-0005', 'name' => 'UV Shield Active Protection Serum', 'ingredients' => null],
            ['id' => 'WAR-0017', 'name' => 'Renew You Anti Aging Eye Cream'],        // kunci ingredients tidak ada
            $this->product('WAR-0023', ['', '   ']),                                   // hanya nilai kosong
        ]);

        $this->assertSame([], $result['conflicts']);
        $this->assertSame(1, $result['checked_count']);
        $this->assertSame(['KAH-0047', 'WAR-0005', 'WAR-0017', 'WAR-0023'], array_column($result['unchecked'], 'id'));
        $this->assertSame('Bright Fresh Sunscreen Serum SPF 35 PA++++', $result['unchecked'][0]['name']);
    }

    public function test_unchecked_products_do_not_hide_conflicts_between_other_products(): void
    {
        $result = $this->detector->detect([$this->tav0004(), $this->product('KAH-0047', []), $this->tav0002()]);

        $this->assertCount(1, $result['conflicts']);
        $this->assertSame(['KAH-0047'], array_column($result['unchecked'], 'id'));
    }

    // ---------------------------------------------------------------
    // Format input/output
    // ---------------------------------------------------------------

    public function test_full_uris_and_ingredient_labels_are_supported(): void
    {
        $ex = 'https://example.org/skincare-ontology#';
        $result = $this->detector->detect([
            ['id' => 'TAV-0004', 'name' => 'TAVI Retinol', 'ingredients' => [$ex.'Retinol'], 'ingredient_labels' => ['Retinol' => 'Retinol']],
            ['id' => 'TAV-0002', 'name' => 'TAVI Toner', 'ingredients' => [$ex.'SalicylicAcid'], 'ingredient_labels' => ['SalicylicAcid' => 'Salicylic Acid (BHA)']],
        ]);

        $this->assertCount(1, $result['conflicts']);
        $this->assertSame('Salicylic Acid (BHA)', $result['conflicts'][0]['products'][1]['ingredients'][0]['label']);
        $this->assertSame('Retinol', $result['conflicts'][0]['products'][0]['ingredients'][0]['label']);
    }

    public function test_empty_input_and_single_product(): void
    {
        $this->assertSame(['conflicts' => [], 'unchecked' => [], 'checked_count' => 0], $this->detector->detect([]));
        $this->assertSame([], $this->detector->detect([$this->tav0004()])['conflicts']);
    }

    public function test_every_active_rule_has_a_valid_level_and_a_written_source(): void
    {
        foreach (IngredientConflictDetector::RULES as $rule) {
            $this->assertArrayHasKey($rule['level'], IngredientConflictDetector::LEVEL_LABELS, $rule['id']);
            $this->assertNotEmpty($rule['sources'], $rule['id']);
            foreach ($rule['sources'] as $source) {
                $this->assertStringStartsWith('https://', $source['url']);
                $this->assertNotEmpty($source['quotes']);
            }
            foreach ($rule['groups'] as $group) {
                $this->assertArrayHasKey($group, IngredientConflictDetector::GROUPS);
            }
        }

        // PHA sengaja tidak masuk golongan mana pun.
        foreach (IngredientConflictDetector::GROUPS as $ingredients) {
            $this->assertNotContains('PHA', $ingredients);
        }
    }

    public function test_group_ingredients_exist_in_clean_ontology(): void
    {
        $ttl = file_get_contents(__DIR__.'/../../ontology/skincare_ontology_clean.ttl');

        foreach (IngredientConflictDetector::GROUPS as $ingredients) {
            foreach ($ingredients as $ingredient) {
                $this->assertMatchesRegularExpression('/^ex:'.preg_quote($ingredient, '/').' a ex:Ingredient\b/m', $ttl, $ingredient);
            }
        }
    }
}
