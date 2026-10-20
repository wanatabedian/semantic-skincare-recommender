<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aphrodite - Cerdas Memilih Skincare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#f2f9f6] text-gray-800 antialiased overflow-x-hidden">

    <!-- Navbar -->
    <nav class="flex justify-between items-center py-5 px-10 bg-white/80 backdrop-blur-md fixed w-full top-0 z-50 shadow-sm">
        <div class="text-2xl font-extrabold text-green-800 tracking-tight">
            Aphro<span class="text-gray-500 font-medium">dite</span>
        </div>
        <div class="hidden md:flex space-x-8 font-semibold text-sm text-gray-600">
            <a href="#" class="text-green-700">Beranda</a>
            <a href="#" class="hover:text-green-700 transition">Katalog</a>
            <a href="#" class="hover:text-green-700 transition">Kuis Semantik</a>
            <a href="#" class="hover:text-green-700 transition">Tentang Kami</a>
        </div>
        <!-- Tombol Arahkan ke Halaman Kuis -->
        <a href="{{ url('/kuis') }}" class="bg-green-700/90 text-white px-6 py-2.5 rounded-full font-bold hover:bg-green-800 transition shadow-lg shadow-green-200">
            Mulai Kuis
        </a>
    </nav>

    <!-- Hero Section -->
    <main class="relative pt-32 pb-20 lg:pt-40 lg:pb-28 px-10 max-w-7xl mx-auto flex flex-col-reverse lg:flex-row items-center gap-12">
        
        <!-- Kiri: Teks Utama -->
        <div class="lg:w-1/2 flex flex-col gap-6 relative z-10">
            <h1 class="text-5xl lg:text-6xl font-extrabold leading-[1.15] text-gray-900">
                Temukan Skincare Terbaik untuk Kulitmu, Secara <span class="text-green-700">Cerdas & Presisi!</span>
            </h1>
            <p class="text-lg text-gray-600 leading-relaxed pr-4">
                Sistem Rekomendasi Skincare Berbasis Web Semantik & Ontologi Apache Jena Fuseki untuk Produk Wardah, Emina, dan Kahf. Sesuaikan dengan usia, jenis kulit, keluhan, dan bahan aktif.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 mt-2">
                <a href="{{ url('/kuis') }}" class="bg-[#85a894] text-white px-8 py-4 rounded-xl font-bold text-center hover:bg-[#6c8c79] transition shadow-md">
                    Ikut Kuis 5 Langkah
                </a>
                <a href="#" class="bg-white border-2 border-gray-200 text-gray-700 px-8 py-4 rounded-xl font-bold text-center hover:border-gray-300 hover:bg-gray-50 transition">
                    Lihat Katalog
                </a>
            </div>

            <!-- Brand Integrasi -->
            <div class="mt-8">
                <p class="text-sm font-bold text-gray-800 mb-4">Brand yang Terintegrasi</p>
                <div class="flex gap-4">
                    <div class="px-5 py-3 bg-white rounded-xl shadow-sm border border-gray-100 font-bold text-green-700 flex items-center justify-center min-w-[100px]">Wardah</div>
                    <div class="px-5 py-3 bg-white rounded-xl shadow-sm border border-gray-100 font-bold text-pink-500 flex items-center justify-center min-w-[100px]">Emina</div>
                    <div class="px-5 py-3 bg-[#4a4f41] rounded-xl shadow-sm font-bold text-white flex items-center justify-center min-w-[100px]">Kahf</div>
                </div>
            </div>
        </div>

        <!-- Kanan: Visual Ilustrasi -->
        <div class="lg:w-1/2 relative w-full h-[400px] lg:h-[500px] flex items-center justify-center">
            <!-- Elemen Estetik Latar -->
            <div class="absolute top-0 right-10 w-72 h-72 bg-[#fbd4ba] rounded-full mix-blend-multiply filter blur-3xl opacity-7