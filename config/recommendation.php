<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Mesin Rekomendasi (Orang 3)
|--------------------------------------------------------------------------
|
| Semua angka yang memengaruhi skor dan Plan A/B dikumpulkan di sini supaya
| mudah dijelaskan di laporan dan mudah diubah tanpa menyentuh query.
|
*/

return [

    // Poin per kriteria yang DIISI user. Kriteria yang dilewati tidak dihitung.
    'weights' => [
        'concern' => 3,     // per keluhan (maks 2 keluhan -> maks 6 poin)
        'skin' => 2,
        'category' => 2,
        'ingredient' => 2,
        'price' => 1,
    ],

    // Plan B: jumlah produk skor tertinggi yang ditampilkan kalau Plan A kosong.
    'plan_b_limit' => 12,

    // Maksimal keluhan yang boleh dipilih user.
    'max_concerns' => 2,

    'teen_lock' => [
        // Kelompok umur yang terkena Teen Lock.
        'age_group' => 'Teen',

        // Kelas golongan retinoid di ontologi v2 (B2). Teen Lock membuang
        // produk yang punya bahan bertipe kelas ini (atau subclass-nya).
        'class' => 'Retinoid',

        // Cadangan selama ontologi belum punya kelas ex:Retinoid:
        // individu bahan yang pasti retinoid. Hapus kalau ontologi sudah lengkap.
        'fallback_ingredients' => [
            'Retinol',
            'RetinolIngredient',
            'RetinalIngredient',
            'RetinaldehydeIngredient',
            'RetinylPalmitateIngredient',
            'RetinylRetinoateIngredient',
            'HydroxypinacoloneRetinoateIngredient',
            'GranactiveRetinoidIngredient',
            'AdapaleneIngredient',
            'TretinoinIngredient',
        ],
    ],

    // Individu jenis kulit "semua jenis kulit": cocok dengan pilihan apa pun.
    'all_skin_types' => ['AllSkinType', 'AllSkin'],

    // Lama cache hasil query SPARQL (detik). 0 = tanpa cache.
    'cache_ttl' => (int) env('SPARQL_CACHE_TTL', 600),
];
