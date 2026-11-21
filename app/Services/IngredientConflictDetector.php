<?php

namespace App\Services;

/**
 * Pendeteksi kombinasi bahan aktif ANTARPRODUK dalam satu waktu pemakaian
 * (mis. rutinitas Malam). Service ini murni PHP: tidak memanggil Fuseki,
 * sehingga bisa dipakai Complete Your Segment maupun Routine Builder.
 *
 * Prinsip:
 *  - Hanya aturan yang punya sumber tertulis yang diaktifkan (lihat RULES).
 *    Kandidat aturan yang sumbernya belum cukup dicatat di INACTIVE_RULES
 *    dan TIDAK pernah menghasilkan peringatan.
 *  - Kombinasi bahan di dalam satu produk tidak diperiksa (formulasi produsen).
 *  - Produk tanpa data bahan TIDAK dianggap aman: dikembalikan sebagai "unchecked".
 *  - Nama bahan memakai nama lokal individu ex:Ingredient di
 *    ontology/skincare_ontology_clean.ttl (mis. Retinol, AHA, SalicylicAcid).
 */
class IngredientConflictDetector
{
    /**
     * Tingkat peringatan. Saat ini SEMUA aturan aktif memakai LEVEL_CAUTION.
     * LEVEL_AVOID tetap disediakan untuk aturan masa depan yang punya sumber eksplisit
     * "jangan digunakan bersamaan"; belum ada aturan aktif yang memakainya.
     */
    public const LEVEL_AVOID = 'hindari';
    public const LEVEL_CAUTION = 'perhatikan';

    public const LEVEL_LABELS = [
        self::LEVEL_AVOID => 'Perlu dihindari',
        self::LEVEL_CAUTION => 'Perlu diperhatikan',
    ];

    /**
     * Golongan bahan, berisi nama lokal ex:Ingredient dari ontologi clean.
     * Ontologi clean belum punya kelas golongan (mis. ex:Retinoid), jadi peta ini ditulis di kode.
     *  - retinoid: AAD menyebut "retinoid creams" dan "products containing retinol".
     *  - chemical_exfoliant: AAD mendefinisikan eksfoliasi kimia sebagai
     *    "alpha and beta hydroxy acids" -> AHA dan BHA (SalicylicAcid).
     *    PHA sengaja TIDAK dimasukkan karena tidak disebut oleh sumber.
     */
    public const GROUPS = [
        'retinoid' => ['Retinol'],
        'chemical_exfoliant' => ['AHA', 'SalicylicAcid'],
    ];

    private const SOURCE_AAD_EXFOLIATE = [
        'title' => 'American Academy of Dermatology — How to safely exfoliate at home (last updated 2/6/26)',
        'url' => 'https://www.aad.org/public/everyday-care/skin-care-secrets/routine/safely-exfoliate-at-home',
        'quotes' => [
            'Some medications and even over-the-counter products may cause your skin to be more sensitive or peel, such as prescription retinoid creams or products containing retinol or benzoyl peroxide.',
            'Exfoliating while using these products may worsen dry skin or even cause acne breakouts.',
            'Chemical exfoliation uses chemicals, such as alpha and beta hydroxy acids, to dissolve dead skin cells.',
        ],
        'accessed' => '2026-11-21',
    ];

    private const SOURCE_AAD_OVER_EXFOLIATE = [
        'title' => 'American Academy of Dermatology — How to safely exfoliate at home (last updated 2/6/26)',
        'url' => 'https://www.aad.org/public/everyday-care/skin-care-secrets/routine/safely-exfoliate-at-home',
        'quotes' => [
            'Chemical exfoliation uses chemicals, such as alpha and beta hydroxy acids, to dissolve dead skin cells.',
            'Be careful not to over-exfoliate, as this could lead to skin that is red and irritated.',
        ],
        'accessed' => '2026-11-21',
    ];

    private const SOURCE_RETIN_A_LABEL = [
        'title' => 'DailyMed — RETIN-A (tretinoin) cream/gel label, Bausch Health US, Precautions: Drug Interactions (revised 03/2024)',
        'url' => 'https://dailymed.nlm.nih.gov/dailymed/drugInfo.cfm?setid=9556d73d-c573-4e0a-9feb-764ce2d1107b',
        'quotes' => [
            'Particular caution should be exercised in using preparations containing sulfur, resorcinol, or salicylic acid with RETIN-A.',
        ],
        'accessed' => '2026-11-21',
        'note' => 'Label untuk tretinoin (retinoid resep), dipakai sebagai pendukung untuk kombinasi retinoid + salicylic acid.',
    ];

    /** Aturan AKTIF. Setiap aturan wajib punya minimal satu sumber dengan URL dan kutipan. */
    public const RULES = [
        [
            // Level "perhatikan", bukan "hindari": AAD menjelaskan risikonya (kulit lebih sensitif,
            // eksfoliasi dapat memperburuk kulit kering / memicu jerawat), tetapi tidak menyatakan
            // bahwa semua kombinasi Retinol + AHA/BHA harus dihindari.
            'id' => 'retinoid-chemical-exfoliant',
            'level' => self::LEVEL_CAUTION,
            'groups' => ['retinoid', 'chemical_exfoliant'],
            'reason' => 'Kombinasi Retinol dengan eksfolian kimia seperti AHA atau BHA berpotensi meningkatkan risiko '
                .'kulit kering dan iritasi. Pertimbangkan untuk memisahkan waktu pemakaiannya.',
            'sources' => [self::SOURCE_AAD_EXFOLIATE, self::SOURCE_RETIN_A_LABEL],
        ],
        [
            'id' => 'multiple-chemical-exfoliants',
            'level' => self::LEVEL_CAUTION,
            'groups' => ['chemical_exfoliant', 'chemical_exfoliant'],
            'reason' => 'Penggunaan beberapa produk eksfolian dalam waktu yang sama dapat meningkatkan risiko '
                .'eksfoliasi berlebihan. Perhatikan frekuensi pemakaian dan reaksi kulit.',
            'sources' => [self::SOURCE_AAD_OVER_EXFOLIATE],
        ],
    ];

    /**
     * Kandidat aturan yang BELUM diaktifkan karena dasar tertulisnya belum cukup.
     * Hanya dokumentasi: tidak pernah dipakai saat deteksi.
     */
    public const INACTIVE_RULES = [
        ['pair' => 'Retinol + PHA', 'why' => 'Sumber (AAD) hanya menyebut alpha dan beta hydroxy acids; PHA tidak disebut.'],
        ['pair' => 'PHA + AHA/BHA/PHA', 'why' => 'PHA tidak disebut sebagai eksfolian kimia oleh sumber yang dipakai.'],
        ['pair' => 'Retinol + Retinol', 'why' => 'Belum ditemukan sumber tertulis yang membahas dua produk retinol sekaligus.'],
        ['pair' => 'Vitamin C + AHA/BHA', 'why' => 'Bergantung bentuk Vitamin C dan pH; ontologi tidak menyimpan informasi itu dan belum ada sumber.'],
        ['pair' => 'Retinol + Vitamin C', 'why' => 'Belum ditemukan sumber tertulis; rutinitas sudah memisahkan Retinol ke Malam.'],
        ['pair' => 'Cysteamine + bahan lain', 'why' => 'Hanya ada di satu produk (sunscreen); cara pakai dan konsentrasi tidak tercatat.'],
        ['pair' => 'Peptide + asam / Vitamin C', 'why' => 'Jenis peptide tidak tercatat di ontologi; belum ada sumber.'],
        ['pair' => 'Niacinamide + Vitamin C', 'why' => 'Tidak ditemukan dasar konflik yang dapat dipertanggungjawabkan.'],
    ];

    /**
     * Deteksi konflik antarproduk.
     *
     * @param  array<int, array{id: string, name?: string, ingredients?: array<int, string>|null, ingredient_labels?: array<string, string>}>  $products
     *         Produk yang DIPAKAI BERSAMAAN (satu waktu pemakaian). Jangan masukkan produk alternatif.
     *         `ingredients` berisi nama lokal (Retinol) atau URI lengkap (…#Retinol).
     * @return array{
     *     conflicts: array<int, array<string, mixed>>,
     *     unchecked: array<int, array{id: string, name: string}>,
     *     checked_count: int
     * }
     */
    public function detect(array $products): array
    {
        $normalized = [];
        $unchecked = [];

        foreach ($products as $product) {
            $id = trim((string) ($product['id'] ?? ''));
            if ($id === '' || isset($normalized[$id]) || isset($unchecked[$id])) {
                continue;   // ID kosong atau produk yang sama muncul dua kali: jangan dibandingkan dengan dirinya sendiri.
            }

            $name = trim((string) ($product['name'] ?? '')) ?: $id;
            $ingredients = $this->normalizeIngredients($product['ingredients'] ?? null);

            if ($ingredients === []) {
                // Tanpa data bahan: tidak bisa diperiksa, BUKAN berarti aman.
                $unchecked[$id] = ['id' => $id, 'name' => $name];
                continue;
            }

            $normalized[$id] = [
                'id' => $id,
                'name' => $name,
                'ingredients' => $ingredients,
                'labels' => is_array($product['ingredient_labels'] ?? null) ? $product['ingredient_labels'] : [],
            ];
        }

        $list = array_values($normalized);
        $conflicts = [];

        for ($i = 0; $i < count($list); $i++) {
            for ($j = $i + 1; $j < count($list); $j++) {
                foreach (self::RULES as $rule) {
                    $match = $this->matchRule($rule, $list[$i], $list[$j]);
                    if ($match !== null) {
                        $conflicts[] = $match;
                    }
                }
            }
        }

        // Urutkan per tingkat (LEVEL_AVOID lebih dulu jika kelak ada aturannya). Di dalam satu tingkat,
        // urutan deteksi dipertahankan: pasangan produk sesuai urutan input, lalu urutan aturan di RULES.
        $order = [self::LEVEL_AVOID => 0, self::LEVEL_CAUTION => 1];
        $indexed = array_map(null, $conflicts, array_keys($conflicts));
        usort($indexed, fn ($a, $b) => [$order[$a[0]['level']], $a[1]] <=> [$order[$b[0]['level']], $b[1]]);

        return [
            'conflicts' => array_column($indexed, 0),
            'unchecked' => array_values($unchecked),
            'checked_count' => count($list),
        ];
    }

    /** Cocokkan satu aturan pada pasangan produk A-B (dua arah). */
    private function matchRule(array $rule, array $a, array $b): ?array
    {
        [$groupX, $groupY] = $rule['groups'];

        $fromA = [];
        $fromB = [];
        foreach ([[$groupX, $groupY], [$groupY, $groupX]] as [$first, $second]) {
            $inA = array_values(array_intersect($a['ingredients'], self::GROUPS[$first]));
            $inB = array_values(array_intersect($b['ingredients'], self::GROUPS[$second]));
            if ($inA !== [] && $inB !== []) {
                $fromA = array_merge($fromA, $inA);
                $fromB = array_merge($fromB, $inB);
            }
        }

        if ($fromA === [] || $fromB === []) {
            return null;
        }

        $fromA = array_values(array_unique($fromA));
        $fromB = array_values(array_unique($fromB));

        return [
            'rule' => $rule['id'],
            'level' => $rule['level'],
            'level_label' => self::LEVEL_LABELS[$rule['level']],
            'products' => [
                $this->productResult($a, $fromA),
                $this->productResult($b, $fromB),
            ],
            'ingredients' => array_values(array_unique(array_merge($fromA, $fromB))),
            'reason' => $rule['reason'],
            'sources' => $rule['sources'],
        ];
    }

    private function productResult(array $product, array $ingredients): array
    {
        return [
            'id' => $product['id'],
            'name' => $product['name'],
            'ingredients' => array_map(fn ($ing) => [
                'id' => $ing,
                'label' => trim((string) ($product['labels'][$ing] ?? '')) ?: $ing,
            ], $ingredients),
        ];
    }

    /** Terima nama lokal atau URI lengkap; buang nilai kosong / bukan string / duplikat. */
    private function normalizeIngredients(mixed $ingredients): array
    {
        if (! is_array($ingredients)) {
            return [];
        }

        $result = [];
        foreach ($ingredients as $ingredient) {
            if (! is_string($ingredient)) {
                continue;
            }
            $local = trim($ingredient);
            if (str_contains($local, '#') || str_contains($local, '/')) {
                $parts = preg_split('/[#\/]/', rtrim($local, '#/'));
                $local = (string) end($parts);
            }
            if ($local !== '') {
                $result[] = $local;
            }
        }

        return array_values(array_unique($result));
    }
}
