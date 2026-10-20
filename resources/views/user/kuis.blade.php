<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kuis Semantik - Aphrodite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#f2f9f6] text-gray-800 antialiased">

    <!-- Navbar Sederhana -->
    <nav class="flex justify-between items-center py-5 px-10 bg-white shadow-sm">
        <div class="text-2xl font-extrabold text-green-800 tracking-tight">
            Aphro<span class="text-gray-500 font-medium">dite</span>
        </div>
        <a href="{{ url('/') }}" class="text-green-700 font-semibold hover:underline">Kembali ke Beranda</a>
    </nav>

    <main class="max-w-3xl mx-auto py-12 px-6">
        <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
            <h1 class="text-3xl font-bold text-center text-gray-900 mb-2">Kuis Analisis Kulit</h1>
            <p class="text-center text-gray-500 mb-8">Pilih kriteria di bawah ini untuk mendapatkan rekomendasi skincare presisi.</p>

            <!-- Form Kuis -->
            <form id="formKuis" class="space-y-6">
                <!-- 1. Rentang Umur -->
                <div>
                    <label class="block font-semibold text-gray-700 mb-2">1. Rentang Umur</label>
                    <select name="age_group" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                        <option value="">-- Pilih Rentang Umur --</option>
                        <option value="Teen">Remaja (Teen)</option>
                        <option value="Adult">Dewasa (Adult)</option>
                    </select>
                </div>

                <!-- 2. Jenis Kulit -->
                <div>
                    <label class="block font-semibold text-gray-700 mb-2">2. Jenis Kulit</label>
                    <select name="skin_type" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500" required>
                        <option value="">-- Pilih Jenis Kulit --</option>
                        <option value="Berminyak">Berminyak</option>
                        <option value="Kering">Kering</option>
                        <option value="Kombinasi">Kombinasi</option>
                        <option value="Sensitif">Sensitif</option>
                    </select>
                </div>

                <!-- 3. Kategori Produk -->
                <div>
                    <label class="block font-semibold text-gray-700 mb-2">3. Kategori Produk yang Dicari</label>
                    <select name="category" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500" required>
                        <option value="">-- Pilih Kategori --</option>
                        <option value="Facewash">Sabun Cuci Muka (Facewash)</option>
                        <option value="Toner">Toner</option>
                        <option value="Serum">Serum</option>
                        <option value="Moisturizer">Pelembab (Moisturizer)</option>
                    </select>
                </div>

                <!-- 4. Bahan Aktif (Opsional) -->
                <div>
                    <label class="block font-semibold text-gray-700 mb-2">4. Bahan Aktif Spesifik (Opsional)</label>
                    <select name="ingredient" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500">
                        <option value="">-- Lewati jika tidak ada preferensi --</option>
                        <option value="Niacinamide">Niacinamide</option>
                        <option value="SalicylicAcid">Salicylic Acid</option>
                        <option value="VitaminC">Vitamin C</option>
                        <option value="Retinol">Retinol</option>
                    </select>
                </div>

                <!-- 5. Keluhan Kulit (Checkbox, Maksimal 2) -->
                <div>
                    <label class="block font-semibold text-gray-700 mb-2">5. Keluhan Utama (Pilih maksimal 2)</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="concerns[]" value="Jerawat" class="w-5 h-5 text-green-600 rounded border-gray-300 focus:ring-green-500">
                            <span>Berjerawat</span>
                        </label>
                        <label class="flex items