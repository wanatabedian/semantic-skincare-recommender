<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/logo/logo-aphrodite.png') }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FDEEF1">
    <title>{{ config('app.name', 'Aph') }} — Temukan Skincare yang Tepat</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"DM Sans"', 'Arial', 'sans-serif'],
                        serif: ['"Playfair Display"', 'Georgia', 'serif'],
                    },
                    colors: {
                        teal: '#6BB1AD',
                        mist: '#7ADDF2',
                        cream: '#FDEEF1',
                        blush: '#E5A9A9',
                        rose: '#E6748E',
                        muted: '#657674',
                        whitewarm: '#FFFEFA',
                        'aqua-mist': '#B8E8EE',
                        wardah: '#7ADDF2',
                    }
                }
            }
        }
    </script>

    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes textReveal {
            0% { opacity: 0; transform: translateY(110%); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @keyframes floatSoft {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 16px 35px rgba(230, 116, 142, .22); }
            50% { box-shadow: 0 22px 55px rgba(230, 116, 142, .42); }
        }

        @keyframes fadeScale {
            from { opacity: 0; transform: scale(.94); }
            to { opacity: 1; transform: scale(1); }
        }

        .delay-\[200ms\] { animation-delay: 200ms; }
        .delay-\[400ms\] { animation-delay: 400ms; }

        .animate-float-soft { animation: floatSoft 4s ease-in-out infinite; }
        .animate-pulse-glow { animation: pulseGlow 3s ease-in-out infinite; }
        .animate-fade-scale { animation: fadeScale .9s ease-out both; }
        .animate-fade-up { animation: fadeInUp .9s ease-out both; }

        .smooth-card {
            transition:
                transform .55s cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow .55s cubic-bezier(0.22, 1, 0.36, 1),
                border-color .45s ease,
                background .45s ease;
            will-change: transform;
        }
        .smooth-card:hover { transform: translateY(-10px); }

        .smooth-btn {
            transition:
                transform .35s cubic-bezier(0.22, 1, 0.36, 1),
                background-color .35s ease,
                color .35s ease,
                box-shadow .35s ease;
            will-change: transform;
        }
        .smooth-btn:hover { transform: translateY(-3px) scale(1.03); }

        .smooth-img { transition: transform .5s cubic-bezier(0.22, 1, 0.36, 1); }
        .group:hover .smooth-img { transform: scale(1.08); }

        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-[#B8E8EE] text-[#E6748E] min-h-screen font-sans antialiased">
    @include('partials.global-nav')

    {{-- NAVBAR --}}
    <nav class="fixed top-0 left-0 w-full z-50 px-6 md:px-16 py-4 flex items-center justify-between bg-[#FDEEF1]/85 backdrop-blur-md border-b border-[#E6748E]/10">
        <a href="{{ url('/') }}" class="flex items-center gap-3 font-extrabold text-xl tracking-tight text-[#E6748E] no-underline">
            <span class="w-9 h-9 rounded-xl bg-[#6BB1AD] grid place-items-center text-white text-lg shadow-sm">✦</span>
            <span>{{ config('app.name', 'Aphro') }}</span>
        </a>

        <div class="hidden md:flex items-center gap-8 text-xs font-bold uppercase tracking-wider text-[#657674]">
            <a href="{{ route('katalog') }}" class="hover:text-[#E6748E] transition">Katalog</a>
            <a href="{{ route('bahan') }}" class="hover:text-[#E6748E] transition">Kandungan</a>
            <a href="{{ route('kuis', ['new' => 1]) }}"
               class="smooth-btn rounded-xl bg-[#E6748E] px-5 py-2.5 text-xs font-black text-white shadow-md transition hover:bg-[#6BB1AD]">
                Mulai Kuis →
            </a>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('katalog') }}" class="md:hidden text-[10px] font-black uppercase text-[#345452]">Katalog</a>
            <a href="{{ route('kuis', ['new' => 1]) }}" class="md:hidden rounded-xl bg-[#E6748E] px-3 py-2 text-[10px] font-black uppercase text-white">Kuis</a>
        </div>
    </nav>

    {{-- HERO (STICKY PARALLAX SEPERTI INDEX) --}}
    <header class="sticky top-0 h-screen flex items-center overflow-hidden w-full z-10 bg-[#FDEEF1]">

        {{-- BACKGROUND VIDEO --}}
        <video autoplay loop muted playsinline preload="auto"
               class="absolute inset-0 w-full h-full object-cover z-0 pointer-events-none">
            <source src="{{ asset('videos/video-aphrodite.mp4') }}" type="video/mp4">
        </video>

        <div class="absolute inset-0 bg-gradient-to-br from-[#FDEEF1]/95 via-[#FDEEF1]/80 to-[#6BB1AD]/30"></div>

        {{-- Dekorasi Lingkaran Halus --}}
        <div class="absolute -right-32 top-24 w-[500px] h-[500px] rounded-full border border-[#6BB1AD]/30 pointer-events-none"></div>
        <div class="absolute -right-16 top-40 w-[370px] h-[370px] rounded-full border border-[#E6748E]/25 pointer-events-none"></div>

        <div class="relative z-20 max-w-7xl mx-auto px-6 md:px-16 w-full text-center md:text-left animate-fade-scale">
            <p class="text-[#6BB1AD] font-black tracking-[6px] uppercase mb-5 text-xs md:text-sm">
                Platform Skincare & Perawatan Kulit
            </p>

            <h1 class="font-serif text-5xl md:text-7xl font-bold text-[#E6748E] leading-tight">
                Temukan <span class="text-[#E6748E] italic">Skincare</span><br>
                Impian Kulitmu
            </h1>

            <p class="mt-6 text-base md:text-xl text-[#657674] max-w-xl leading-relaxed mx-auto md:mx-0 font-medium">
                Jelajahi produk dan kandungannya dari dataset ontologi Aphrodite, lalu temukan pilihan yang sesuai dengan kebutuhan kulitmu.
            </p>

            <div class="mt-8 flex flex-wrap gap-4 justify-center md:justify-start">
                <a href="#produk" class="smooth-btn bg-[#E6748E] hover:bg-[#E6748E] text-white px-8 py-4 rounded-2xl text-xs font-black uppercase tracking-wider shadow-lg">
                    Eksplor Skincare ➔
                </a>
                <a href="{{ route('kuis', ['new' => 1]) }}" class="smooth-btn bg-[#FFFEFA] text-[#E6748E] border border-[#E6748E]/15 px-8 py-4 rounded-2xl text-xs font-black uppercase tracking-wider">
                    Mulai Skin Quiz
                </a>
            </div>
        </div>
    </header>

    {{-- MAIN CONTENT SHEET (MENUMPUK DI ATAS HERO SEPERTI INDEX) --}}
    <div class="relative z-20 bg-[#FDEEF1] shadow-[0_-20px_50px_rgba(36,59,58,0.12)] rounded-t-[40px]">

        {{-- INTRO SECTION --}}
        <section class="max-w-7xl mx-auto px-6 pt-24 pb-24 grid md:grid-cols-2 gap-16 items-start relative animate-fade-up">

   {{-- KOLOM KIRI STICKY --}}
            <div class="md:sticky md:top-28 self-start">

                <div class="bg-[#f4ebee] rounded-[40px] p-4 shadow-2xl border border-[#6BB1AD]/30 overflow-hidden group animate-float-soft">
                    <div class="relative bg-gradient-to-br from-[#B8E8EE] via-[#B8E8EE] to-[#fc9bb2] text-[#5f9ba3] rounded-[32px] p-8 md:p-10 min-h-[360px] flex flex-col justify-between overflow-hidden transition-all duration-700 ease-out group-hover:scale-[1.02]">

                        <div class="absolute -top-16 -right-16 w-44 h-44 bg-[#E5A9A9]/35 rounded-full blur-3xl"></div>
                        <div class="absolute -bottom-20 -left-20 w-56 h-56 bg-[#E6748E]/60 rounded-full blur-3xl"></div>

                        <div class="relative z-10">
                            <p class="text-xs uppercase tracking-[4px] mb-4 text-[#E6748E] font-black">
                                apakah skincaremu sudah benar?
                            </p>

                            <h3 class="font-serif text-5xl md:text-5xl font-bold mb-4 leading-tight">
                                Ayo mulai dengar kulitmu!
                            </h3>

                            <p class="text-sm md:text-base text-[#b582a1] font-medium leading-relaxed">
                                Jawab 5 pertanyaan singkat untuk mendapatkan rekomendasi produk yang paling sesuai.
                            </p>
                        </div>

                        <div class="relative z-10 mt-8">
                            <a href="{{ route('kuis', ['new' => 1]) }}"
                               class="smooth-btn inline-flex items-center justify-center bg-[#FFFEFA] text-[#E6748E] px-8 py-4 rounded-2xl font-black hover:bg-[#E6748E] hover:text-white shadow-lg no-underline text-xs uppercase tracking-wider">
                                Mulai Kuis Sekarang ➔
                            </a>
                        </div>

                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 mt-6">
                    <a href="{{ url('/katalog') }}"
                       class="smooth-btn bg-[#E6748E] hover:bg-[#E6748E] text-white px-6 py-4 rounded-2xl font-black shadow-xl animate-pulse-glow text-center no-underline text-xs uppercase tracking-wider">
                        Lihat Semua Produk
                    </a>
                </div>
            </div>

            {{-- KOLOM KANAN SCROLLING (ELEGAN, TANPA GRADASI & TANPA KOTAK LOGO) --}}
            <div class="space-y-16">

                {{-- Editorial Intro (Lebih besar & elegan) --}}
                <div class="border-l-2 border-[#E6748E]/40 pl-6 md:pl-8 py-2">
                    <p class="font-serif text-2xl md:text-3xl text-[#2D2426] leading-relaxed font-normal">
                        Temukan rangkaian skincare terbaik berdasarkan kondisi <span class="italic text-[#6BB1AD]">skin barrier</span>, kandungan aktif, dan kebutuhan kulitmu bersama
                        <span class="font-bold text-[#E6748E]">Aphrodite</span>. Kami bermitra dengan brand teruji klinis untuk kesehatan kulit jangka panjang.
                    </p>
                </div>

                {{-- Brand Partner Terpercaya --}}
                <div class="border-t border-[#E6748E]/15 pt-12">
                    <div class="flex items-center justify-between mb-8">
                        <p class="text-xs uppercase tracking-[4px] text-[#6BB1AD] font-black">
                            Brand Partner Terpercaya
                        </p>
                        <span class="text-xs font-serif italic text-[#657674]">6 Curated Brands</span>
                    </div>

                    @php
                        $brands = [
                            ['name' => 'Laboré', 'logo' => 'images/logo/labore.jpg'],
                            ['name' => 'Wardah', 'filter' => 'WardahBrand', 'logo' => 'images/logo/wardah.png'],
                            ['name' => 'Emina', 'filter' => 'EminaBrand', 'logo' => 'images/logo/emina.jpg'],
                            ['name' => 'Kahf', 'filter' => 'KahfBrand', 'logo' => 'images/logo/kahf.png'],
                            ['name' => 'Tavi', 'filter' => 'TAVIBrand', 'logo' => 'images/logo/tavi.jpg'],
                            ['name' => 'Crystallure', 'filter' => 'CrystallureBrand', 'logo' => 'images/logo/Crystallure.jpg'],
                        ];
                    @endphp

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-5">
                        @foreach($brands as $brand)
                            <a href="{{ route('katalog', ['brand' => $brand['filter'] ?? 'LABOREBrand']) }}"
                               class="smooth-card bg-[#FFFEFA] hover:bg-white p-7 rounded-[28px] border border-[#E6748E]/15 hover:border-[#E6748E]/50 shadow-[0_8px_30px_rgba(230,116,142,0.06)] hover:shadow-[0_18px_40px_rgba(230,116,142,0.14)] flex flex-col items-center justify-between min-h-[190px] no-underline group">

                                {{-- Area Logo Bebas Tanpa Kotak --}}
                                <div class="flex-1 w-full flex items-center justify-center py-2">
                                    <img src="{{ asset($brand['logo']) }}"
                                         alt="{{ $brand['name'] }}"
                                         class="smooth-img max-h-16 max-w-[125px] w-auto object-contain mix-blend-multiply opacity-90 group-hover:opacity-100">
                                </div>

                                {{-- Nama Brand Elegan di Bawah --}}
                                <div class="w-full pt-3 border-t border-[#E6748E]/10 text-center">
                                    <span class="font-serif text-lg font-semibold tracking-wide text-[#2D2426] group-hover:text-[#E6748E] transition-colors duration-300">
                                        {{ $brand['name'] }}
                                    </span>
                                </div>

                            </a>
                        @endforeach
                    </div>
                </div>

            </div>
        </section>
    </div>

   {{-- RIWAYAT KUIS (LAYOUT 1:3 MENYAMPING) --}}
    <section id="riwayat-kuis" class="bg-gradient-to-b from-[#FDEEF1] via-[#FDEEF1] to-[#FFFEFA] px-6 py-28 relative z-20">
        <div class="max-w-7xl mx-auto rounded-[32px] border border-[#6BB1AD]/20 bg-[#e0fbff] p-8 md:p-12 shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">

                {{-- KOLOM KIRI (1 Bagian: Judul & Tombol Kuis) --}}
                <div class="lg:col-span-1 flex flex-col justify-between space-y-5 lg:border-r lg:border-[#E6748E]/15 lg:pr-6">
                    <div>
                        <h2 class="font-serif mt-2 text-4xl font-bold text-[#E6748E] leading-snug">
                            Riwayat Analisis Kulit
                        </h2>
                        <p class="mt-2 text-sm text-[#657674] leading-relaxed">
                            Buka lagi rekomendasi dari lima kuis terakhirmu.
                        </p>
                    </div>

                    <div>
                        <a href="{{ route('kuis', ['new' => 1]) }}"
                           class="smooth-btn inline-flex items-center justify-center w-full sm:w-auto lg:w-full rounded-xl bg-[#E6748E] hover:bg-[#6BB1AD] px-5 py-3.5 text-xs font-black uppercase tracking-wider text-white shadow-sm no-underline">
                            Mulai kuis →
                        </a>
                    </div>
                </div>

                {{-- KOLOM KANAN (3 Bagian: Daftar Kartu Riwayat) --}}
                <div class="lg:col-span-3">
                    <div id="quiz-history-list" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"></div>
                    <div id="quiz-history-empty" class="rounded-2xl border border-dashed border-[#E6748E]/25 bg-white/60 p-8 text-center">
                        <p class="text-sm text-[#657674]">
                            Belum ada riwayat kuis di perangkat ini.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>
    
    {{-- PRODUK PILIHAN (EFEK HOVER DUAL-IMAGE ALA KAHF) --}}
<section id="produk" class="bg-gradient-to-b from-[#FFFEFA] via-[#B8E8EE] to-[#c8eef3] text-white py-24 relative overflow-hidden z-20">
    <div class="max-w-7xl mx-auto px-6">

        <div class="text-center md:text-left mb-16">
            <p class="text-xs uppercase tracking-[4px] text-[#6BB1AD] font-black mb-2">
                Rekomendasi Pilihan
            </p>

            <h2 class="font-serif text-5xl font-bold text-[#E6748E] tracking-tight overflow-hidden flex flex-wrap justify-center md:justify-start gap-x-3">
                <span>Temukan</span>
                <span class="text-[#E6748E] italic inline-block animate-[textReveal_0.8s_cubic-bezier(0.77,0,0.175,1)_1_both] delay-[200ms]">skincare</span>
                <span class="text-[#E6748E] italic inline-block animate-[textReveal_0.8s_cubic-bezier(0.77,0,0.175,1)_1_both] delay-[400ms]">terbaik</span>
            </h2>
        </div>

        @php
            // Menggunakan foto produk asli yang sudah ada di folder public/images/
        @endphp

<div class="grid md:grid-cols-3 gap-8">
            @foreach($products as $item)
                @php
                    $brandName = $item['brand'] ?? 'Brand tidak tersedia';
                    $fotoUtama = $item['image'] ?? asset('images/logo/logo-aphrodite.png');
                    $fotoHover = $fotoUtama;
                @endphp

                <a href="{{ $item['detail_url'] ?? route('katalog') }}"
                   class="group relative h-[450px] rounded-[28px] bg-[#F7F3F4] border border-[#E5A9A9]/40 overflow-hidden shadow-md hover:shadow-[0_20px_50px_rgba(230,116,142,0.25)] hover:-translate-y-2 transition-all duration-500 block no-underline">

                    {{-- LAYER 1: TAMPILAN NORMAL (Foto Produk di Tengah Atas) --}}
                    <div class="relative z-10 h-[310px] flex items-center justify-center p-6 pt-8 transition-all duration-500 group-hover:opacity-0 group-hover:scale-95">
                        <img src="{{ $fotoUtama }}"
                             alt="{{ $item['name'] }}"
                             class="max-h-60 w-auto object-contain drop-shadow-md">
                    </div>

                    {{-- LAYER 2: FOTO HOVER FULL-CARD --}}
                    <div class="absolute inset-0 z-10 opacity-0 scale-105 group-hover:opacity-100 group-hover:scale-100 transition-all duration-700 ease-out pointer-events-none">
                        <img src="{{ $fotoHover }}"
                             alt="{{ $item['name'] }} preview"
                             class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-black/10 to-transparent"></div>
                    </div>

                    {{-- LAYER 3: KONTEN TEKS MENEMPEL DI BAWAH (Tumbuh ke atas saat hover, anti kepotong) --}}
                    <div class="absolute inset-x-0 bottom-0 z-20 p-7 flex items-end justify-between gap-4">
                        <div class="text-left flex-1 min-w-0">
                            {{-- Badge Brand --}}
                            <span class="inline-block text-[11px] font-black uppercase tracking-[2px] text-[#6fb0ad] group-hover:text-[#FDEEF1] transition-colors duration-300 mb-1">
                                {{ $brandName }}
                            </span>

                            {{-- Nama Produk --}}
                            <h3 class="font-serif text-xl font-bold text-[#c9949f] group-hover:text-white leading-snug transition-colors duration-300">
                                {{ $item['name'] }}
                            </h3>

                            {{-- Deskripsi Tambahan --}}
                            <div class="max-h-0 opacity-0 overflow-hidden group-hover:max-h-48 group-hover:opacity-100 transition-all duration-500 ease-out">
                                <p class="text-white/90 text-xs leading-relaxed mt-2">
                                    {{ $item['category'] ?? 'Kategori skincare' }} · Cocok untuk {{ implode(', ', $item['skin_types']) ?: 'berbagai jenis kulit' }}.
                                </p>
                                <p class="text-[#379db4] font-bold text-sm mt-2">
                                    {{ $item['price'] !== null ? 'Rp '.number_format($item['price'], 0, ',', '.') : 'Harga belum tercatat' }}
                                </p>
                            </div>
                        </div>

                        {{-- Ikon Panah Kanan --}}
                        <div class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-[#2D2426] group-hover:text-white group-hover:translate-x-1 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>

                </a>
            @endforeach
        </div>

        <div class="mt-16 text-center">
            <a href="{{ url('/katalog') }}"
               class="inline-block bg-[#E6748E] text-white font-black px-16 md:px-24 py-5 rounded-2xl shadow-xl transition-all duration-300 transform hover:bg-[#d6627d] hover:scale-105 uppercase tracking-wider text-xs animate-pulse-glow">
                Lihat Semua Katalog Skincare ➔
            </a>
        </div>

    </div>
</section>


    {{-- TREATMENT & KONSULTASI SLIDER (SEPERTI SECTION SERVICES DI INDEX) --}}
    <section id="konsul" class="bg-gradient-to-b from-[#c8eef3] via-[#FFFEFA] to-[#FDEEF1] text-white py-24 relative overflow-hidden z-20">
        

        <div class="max-w-7xl mx-auto px-6 relative z-10">

            <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-14 gap-8">
                <div class="text-center md:text-left">
                    <p class="text-xs uppercase tracking-[4px] text-[#6BB1AD] font-black mb-3">
                        Kategori Produk Skincare
                    </p>

                    <h2 class="font-serif text-4xl md:text-5xl font-bold text-white tracking-tight leading-tight">
                        Jenis Produk
                        <span class="text-[#E5A9A9] italic">terbaik</span>
                    </h2>
                </div>

                <div class="flex justify-center md:justify-end">
                    <a href="{{ route('katalog') }}"
                       class="bg-[#E6748E] hover:bg-[#6BB1AD] text-white px-8 py-4 rounded-2xl font-black shadow-xl transition-all duration-300 hover:scale-105 no-underline text-xs uppercase tracking-wider">
                        Lihat Semua Kategori
                    </a>
                </div>
            </div>

            @php
                $categoryIcons = ['CleanserCategory' => 'CL', 'TonerCategory' => 'TN', 'SerumCategory' => 'SR', 'MoisturizerCategory' => 'MO', 'SunscreenCategory' => 'SS', 'EyeCareCategory' => 'EC', 'FaceMistCategory' => 'FM', 'SpotTreatmentCategory' => 'ST'];
            @endphp

            <div id="services-container"
                 class="flex overflow-x-auto gap-7 pb-8 cursor-grab active:cursor-grabbing scroll-smooth no-scrollbar">

                @foreach($categories as $category)
                    @php($categoryUrl = route('katalog', ['kategori' => [$category['id']]]))
                    <a href="{{ $categoryUrl }}" class="flex-none w-[300px] md:w-[360px] bg-[#FDEEF1] text-[#E6748E]
                                p-7 rounded-[32px] border border-[#7ADDF2]/40
                                shadow-[0_10px_30px_rgba(230,116,142,0.08)]
                                transition-all duration-500
                                hover:-translate-y-2 hover:bg-[#FFFEFA]
                                hover:shadow-[0_25px_60px_rgba(107,177,173,0.28)]
                                flex flex-col justify-between group no-underline">

                        <div>
                            <div class="w-20 h-20 bg-[#6BB1AD] rounded-3xl flex items-center justify-center mb-6 overflow-hidden
                                        text-white font-serif text-2xl font-bold shadow-md transition-all duration-500 group-hover:scale-105 group-hover:bg-[#E6748E]">
                                <span>{{ $categoryIcons[$category['id']] ?? 'SK' }}</span>
                            </div>

                            <h3 class="font-serif text-2xl font-bold mb-2 leading-snug text-[#E6748E] line-clamp-2">
                                {{ $category['name'] }}
                            </h3>

                            <p class="text-xs font-black text-[#6BB1AD] mb-4 tracking-wide uppercase line-clamp-1">
                                Kategori produk dari ontologi
                            </p>

                            <p class="text-[#657674] leading-relaxed text-sm line-clamp-3 mb-6">
                                Jelajahi produk kategori {{ strtolower($category['name']) }} yang tercatat dalam katalog Aphrodite.
                            </p>
                        </div>

                        <div class="pt-5 border-t border-[#E6748E]/15 flex items-center justify-between gap-4 mt-auto">
                            <div>
                                <p class="text-xs text-[#657674] font-bold mb-1">
                                    Telusuri
                                </p>

                                <p class="text-xl font-black text-[#E6748E]">
                                    Katalog
                                </p>
                            </div>

                            <span class="shrink-0 bg-[#E6748E] text-white px-5 py-3 rounded-2xl text-xs font-black tracking-wider
                                      hover:bg-[#E6748E] transition-all duration-300 uppercase no-underline">
                                Lihat produk ➔
                            </span>
                        </div>
                    </a>
                @endforeach

            </div>

        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="bg-[#FDEEF1] text-[#E6748E] py-12 px-6 border-t border-[#E6748E]/10 relative z-20">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4 text-xs font-bold text-[#657674]">
            <div class="flex items-center gap-2 text-[#E6748E] font-extrabold text-base">
                <span class="w-7 h-7 rounded-lg bg-[#6BB1AD] grid place-items-center text-white text-sm">✦</span>
                <span>{{ config('app.name', 'Aphrodite') }}</span>
            </div>
            <p>© {{ date('Y') }} Aphrodite Botanical & Derma Care. Semua hak dilindungi.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const historyList = document.getElementById('quiz-history-list');
        try {
            const history = JSON.parse(localStorage.getItem('aphrodite-quiz-history') || '[]').slice(0, 5);
            document.getElementById('quiz-history-empty').classList.toggle('hidden', history.length > 0);
            history.forEach((entry, index) => {
                const a = entry.answers || {};
                const params = new URLSearchParams();
                ['age','skin','category','ingredient','price_min','price_max'].forEach(key => { if (a[key]) params.set(key, a[key]); });
                (a.concern || []).slice(0, 2).forEach(value => params.append('concern[]', value));
                // Brand pilihan dan bahan yang dihindari juga bagian dari kriteria kuis.
                ['brand', 'avoid'].forEach(key => (a[key] || []).forEach(value => params.append(key + '[]', value)));
                const card = document.createElement('a');
                card.href = '/rekomendasi?' + params.toString();
                card.className = 'rounded-2xl border border-[#E6748E]/10 bg-white p-4 no-underline transition hover:border-[#6BB1AD]';
                const date = entry.created_at ? new Date(entry.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : 'Tanggal tidak tersedia';
                const detail = [a.skin, ...(a.concern || [])].filter(Boolean).join(' · ') || 'Analisis tanpa kriteria khusus';
                card.innerHTML = `<span class="text-[10px] font-black uppercase tracking-wider text-[#6BB1AD]">Analisis ${index + 1} · ${date}</span><span class="mt-1 block text-sm font-bold text-[#345452]">${detail.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}</span><span class="mt-2 block text-xs font-bold text-[#E6748E]">Buka hasil →</span>`;
                historyList.appendChild(card);
            });
        } catch (_) { document.getElementById('quiz-history-empty').textContent = 'Riwayat kuis tidak dapat dibaca pada perangkat ini.'; }

        const slider = document.getElementById('services-container');

        if (slider) {
            let isDown = false;
            let startX;
            let scrollLeft;

            slider.addEventListener('mousedown', (e) => {
                isDown = true;
                slider.classList.add('active');
                startX = e.pageX - slider.offsetLeft;
                scrollLeft = slider.scrollLeft;
            });

            slider.addEventListener('mouseleave', () => { isDown = false; });
            slider.addEventListener('mouseup', () => { isDown = false; });

            slider.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - slider.offsetLeft;
                const walk = (x - startX) * 1.5;
                slider.scrollLeft = scrollLeft - walk;
            });
        }

        const items = document.querySelectorAll('.reveal-item');

        if (items.length > 0) {
            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.remove('opacity-0', 'translate-y-10');
                        entry.target.classList.add('opacity-100', 'translate-y-0');
                        obs.unobserve(entry.target);
                    }
                });
            }, { root: null, rootMargin: '0px', threshold: 0.15 });

            items.forEach(item => observer.observe(item));
        }
    });
    </script>
</body>
</html>
