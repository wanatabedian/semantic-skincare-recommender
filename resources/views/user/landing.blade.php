<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FDEEF1">
    <title>{{ config('app.name', 'Skinova') }} — Temukan Skincare yang Tepat</title>

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
                        rose: '#E6748E',
                        muted: '#657674',
                        whitewarm: '#FFFEFA',
                        aqua mist: '#B8E8EE',
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

    {{-- NAVBAR --}}
    <nav class="fixed top-0 left-0 w-full z-50 px-6 md:px-16 py-4 flex items-center justify-between bg-[#FDEEF1]/85 backdrop-blur-md border-b border-[#E6748E]/10">
        <a href="{{ url('/') }}" class="flex items-center gap-3 font-extrabold text-xl tracking-tight text-[#E6748E] no-underline">
            <span class="w-9 h-9 rounded-xl bg-[#6BB1AD] grid place-items-center text-white text-lg shadow-sm">✦</span>
            <span>{{ config('app.name', 'Skinova') }}</span>
        </a>

        <div class="hidden md:flex items-center gap-8 text-xs font-bold uppercase tracking-wider text-[#657674]">
            <a href="#produk" class="hover:text-[#E6748E] transition">Produk</a>
            <a href="#event" class="hover:text-[#E6748E] transition">Event & Talkshow</a>
            <a href="#konsultasi" class="hover:text-[#E6748E] transition">Treatment</a>
            <a href="#edukasi" class="hover:text-[#E6748E] transition">Skin Class</a>
            <a href="{{ route('bandingkan') }}" class="hover:text-[#E6748E] transition">Bandingkan</a>
            <a href="{{ route('kuis', ['new' => 1]) }}"
               class="smooth-btn rounded-xl bg-[#E6748E] px-5 py-2.5 text-xs font-black text-white shadow-md transition hover:bg-[#6BB1AD]">
                Mulai Kuis →
            </a>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('bandingkan') }}" class="md:hidden text-[10px] font-black uppercase text-[#345452]">Bandingkan</a>
            <a href="{{ route('kuis', ['new' => 1]) }}" class="md:hidden rounded-xl bg-[#E6748E] px-3 py-2 text-[10px] font-black uppercase text-white">Kuis</a>
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="smooth-btn bg-[#E6748E] hover:bg-[#E6748E] text-white px-5 py-2.5 rounded-xl text-xs font-black">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-xs font-bold px-4 py-2.5 text-[#E6748E] hover:text-[#E6748E] transition">
                        Masuk
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="smooth-btn bg-[#E6748E] hover:bg-[#E6748E] text-white px-5 py-2.5 rounded-xl text-xs font-black shadow-md">
                            Daftar
                        </a>
                    @endif
                @endauth
            @endif
        </div>
    </nav>

    {{-- HERO (STICKY PARALLAX SEPERTI INDEX) --}}
    <header class="sticky top-0 h-screen flex items-center overflow-hidden w-full z-10"
        style="background-image: url('{{ asset('images/bg_skincare.jpg') }}'); background-size: cover; background-position: center;">

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
                Temukan rekomendasi produk yang sesuai jenis kulit, event kecantikan, kelas edukasi, dan konsultasi ahli kulit terpercaya.
            </p>

            <div class="mt-8 flex flex-wrap gap-4 justify-center md:justify-start">
                <a href="#produk" class="smooth-btn bg-[#E6748E] hover:bg-[#E6748E] text-white px-8 py-4 rounded-2xl text-xs font-black uppercase tracking-wider shadow-lg">
                    Eksplor Skincare ➔
                </a>
                <a href="{{ route('kuis') }}" class="smooth-btn bg-[#FFFEFA] text-[#E6748E] border border-[#E6748E]/15 px-8 py-4 rounded-2xl text-xs font-black uppercase tracking-wider">
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
                                Formula Terbaru
                            </p>

                            <h3 class="font-serif text-4xl md:text-5xl font-bold mb-4 leading-tight">
                                {{ isset($products) && $products->count() > 0 ? $products->first()->nama_produk : 'Hydra Barrier Serum' }}
                            </h3>

                            <p class="text-sm md:text-base text-[#FDEEF1]/90 font-medium leading-relaxed">
                                @if(isset($products) && $products->count() > 0)
                                    {{ $products->first()->brand->nama_brand ?? 'Skinova Lab' }} •
                                    {{ $products->first()->jenis_kulit ?? 'Semua Jenis Kulit' }} •
                                    {{ $products->first()->kategori ?? 'Serum' }}
                                @else
                                    Skinova Botanical Lab • Kulit Sensitif & Kering • BPOM Certified
                                @endif
                            </p>
                        </div>

                        <div class="relative z-10 mt-8">
                            <a href="#produk"
                               class="smooth-btn inline-flex items-center justify-center bg-[#FFFEFA] text-[#E6748E] px-8 py-4 rounded-2xl font-black hover:bg-[#E6748E] hover:text-white shadow-lg no-underline text-xs uppercase tracking-wider">
                                Lihat Detail
                            </a>
                        </div>

                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                    <a href="#produk"
                       class="smooth-btn bg-[#E6748E] hover:bg-[#E6748E] text-white px-6 py-4 rounded-2xl font-black shadow-xl animate-pulse-glow text-center no-underline text-xs uppercase tracking-wider">
                        Lihat Semua Brand
                    </a>

                    <a href="#event"
                       class="smooth-btn bg-[#FFFEFA] text-[#E6748E] px-6 py-4 rounded-2xl font-black shadow-xl text-center border border-[#7ADDF2]/40 no-underline text-xs uppercase tracking-wider hover:bg-[#7ADDF2]/20">
                        Gabung Komunitas
                    </a>
                </div>
            </div>

            {{-- KOLOM KANAN SCROLLING --}}
            <div class="space-y-16">

                <div class="bg-[#FFFEFA]/80 backdrop-blur-sm border border-[#7ADDF2]/30 rounded-[32px] p-8 shadow-xl">
                    <p class="text-[#E6748E] text-xl md:text-2xl leading-relaxed font-medium">
                        Temukan rangkaian skincare terbaik berdasarkan kondisi skin barrier, kandungan aktif, dan kebutuhan kulitmu bersama
                        <span class="text-[#E6748E] font-black">Skinova</span>. Kami bermitra dengan klinik dan brand teruji klinis untuk kesehatan kulit jangka panjang.
                    </p>
                </div>

                <div class="border-t border-[#E6748E]/10 pt-10">
                    <p class="text-xs uppercase tracking-[3px] text-[#6BB1AD] font-black mb-8">
                        Brand Partner Terpercaya
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                        <a href="#produk"
                           class="smooth-card bg-gradient-to-br from-[#E6748E] to-[#1A2B2A] p-8 rounded-[32px] border border-[#6BB1AD]/30 shadow-xl hover:border-[#E6748E] text-center flex flex-col items-center justify-center min-h-[200px] text-white no-underline animate-fade-scale group">
                            <div class="w-20 h-20 bg-[#FDEEF1] rounded-3xl flex items-center justify-center mb-5 shadow-lg p-3 text-3xl text-[#6BB1AD] font-serif font-bold">
                                <img src="{{ asset('images/labore.jpg') }}" alt="Shopee" class="smooth-img w-full h-full object-contain">
                            </div>
                            <span class="font-serif font-bold text-xl tracking-wide">labore</span>
                        </a>

                        <a href="#produk"
                           class="smooth-card bg-gradient-to-br from-[#E6748E] to-[#1A2B2A] p-8 rounded-[32px] border border-[#6BB1AD]/30 shadow-xl hover:border-[#E6748E] text-center flex flex-col items-center justify-center min-h-[200px] text-white no-underline animate-fade-scale group">
                            <div class="w-20 h-20 bg-[#FDEEF1] rounded-3xl flex items-center justify-center mb-5 shadow-lg p-3 text-3xl text-[#E6748E] font-serif font-bold">
                                <img src="{{ asset('images/wardah.png') }}" alt="wardah" class="smooth-img w-full h-full object-contain">
                            </div>
                            <span class="font-serif font-bold text-xl tracking-wide">wardah</span>
                        </a>

                        <a href="#produk"
                           class="smooth-card bg-gradient-to-br from-[#E6748E] to-[#1A2B2A] p-8 rounded-[32px] border border-[#6BB1AD]/30 shadow-xl hover:border-[#E6748E] text-center flex flex-col items-center justify-center min-h-[200px] text-white no-underline animate-fade-scale group">
                            <div class="w-20 h-20 bg-[#FDEEF1] rounded-3xl flex items-center justify-center mb-5 shadow-lg p-3 text-3xl text-[#6BB1AD] font-serif font-bold">
                                <img src="{{ asset('images/tavi.jpg') }}"">
                            </div>
                            <span class="font-serif font-bold text-xl tracking-wide">tavi</span>
                        </a>

                        <a href="#produk"
                           class="smooth-card bg-gradient-to-br from-[#E6748E] to-[#1A2B2A] p-8 rounded-[32px] border border-[#6BB1AD]/30 shadow-xl hover:border-[#E6748E] text-center flex flex-col items-center justify-center min-h-[200px] text-white no-underline animate-fade-scale group">
                            <div class="w-20 h-20 bg-[#FDEEF1] rounded-3xl flex items-center justify-center mb-5 shadow-lg p-3 text-3xl text-[#E6748E] font-serif font-bold">
                                <img src="{{ asset('images/emina.jpg') }}" alt="Shopee" class="smooth-img w-full h-full object-contain">
                            </div>
                            <span class="font-serif font-bold text-xl tracking-wide">emina</span>
                        </a>

                    </div>
                </div>

                <div class="bg-[#FFFEFA] rounded-[36px] p-8 md:p-10 shadow-2xl border border-[#7ADDF2]/30">
                    <p class="text-xs uppercase tracking-[3px] text-[#6BB1AD] font-black mb-8">
                        Skinova Dalam Angka
                    </p>

                    <div class="grid grid-cols-2 gap-5">
                        <div class="smooth-card bg-[#FDEEF1] rounded-[28px] p-6">
                            <h3 class="font-serif text-5xl md:text-6xl font-bold text-[#6BB1AD] tracking-tight">120+</h3>
                            <p class="text-[#657674] font-black text-xs mt-3 uppercase tracking-wider">
                                Formula Teruji Klinis
                            </p>
                        </div>

                        <div class="smooth-card bg-[#FDEEF1] rounded-[28px] p-6">
                            <h3 class="font-serif text-5xl md:text-6xl font-bold text-[#E6748E] tracking-tight">98%</h3>
                            <p class="text-[#657674] font-black text-xs mt-3 uppercase tracking-wider">
                                Kulit Lebih Sehat
                            </p>
                        </div>

                        <div class="smooth-card col-span-2 bg-[#E6748E] rounded-[28px] p-6">
                            <h3 class="font-serif text-5xl font-bold text-[#E5A9A9] tracking-tight">0%</h3>
                            <p class="text-[#FDEEF1] text-base mt-3 font-medium">
                                Bebas paraben, alkohol keras, dan bahan iritan berbahaya untuk kulit sensitif.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </div>

    {{-- RIWAYAT KUIS --}}
    <section id="riwayat-kuis" class="bg-[#FFFEFA] px-6 py-12 relative z-20">
        <div class="max-w-7xl mx-auto rounded-[28px] border border-[#E6748E]/15 bg-[#FDEEF1] p-6 md:p-8">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div><p class="text-xs font-black uppercase tracking-[3px] text-[#6BB1AD]">Tersimpan di perangkat ini</p><h2 class="font-serif mt-1 text-2xl font-bold text-[#E6748E]">Riwayat analisis kulit</h2><p class="mt-1 text-sm text-[#657674]">Buka lagi rekomendasi dari lima kuis terakhirmu.</p></div>
                <a href="{{ route('kuis', ['new' => 1]) }}" class="inline-flex justify-center rounded-xl bg-[#E6748E] px-5 py-3 text-xs font-black uppercase tracking-wider text-white">Mulai kuis →</a>
            </div>
            <div id="quiz-history-list" class="mt-5 grid gap-3 md:grid-cols-2"></div>
            <p id="quiz-history-empty" class="mt-4 text-sm text-[#657674]">Belum ada riwayat kuis di perangkat ini.</p>
        </div>
    </section>

    {{-- PRODUK PILIHAN (SEPERTI SECTION LOKER DI INDEX) --}}
    <section id="produk" class="bg-gradient-to-b from-[#FDEEF1] via-[#FFFEFA] to-[#FFFEFA] text-[#E6748E] py-24 relative z-20">
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
                $dummyProducts = [
                    (object)[
                        'id' => 1,
                        'nama_produk' => 'Calming Cica Cleanser',
                        'brand' => 'Skinova Botanical',
                        'jenis_kulit' => 'Kulit Sensitif & Berjerawat',
                        'harga' => '115.000',
                        'kandungan' => 'Centella + Panthenol',
                        'ikon' => '🌿'
                    ],
                    (object)[
                        'id' => 2,
                        'nama_produk' => 'Rose Glow Niacinamide Serum',
                        'brand' => 'Skinova Radiance',
                        'jenis_kulit' => 'Kulit Kusam & Noda Hitam',
                        'harga' => '149.000',
                        'kandungan' => '5% Niacinamide + Rosehip',
                        'ikon' => '🌸'
                    ],
                    (object)[
                        'id' => 3,
                        'nama_produk' => 'Ceramide Barrier Cloud Cream',
                        'brand' => 'Skinova Derma',
                        'jenis_kulit' => 'Kulit Kering & Rusak Barrier',
                        'harga' => '165.000',
                        'kandungan' => '5X Ceramide + Hyaluronic',
                        'ikon' => '💧'
                    ],
                ];
                $listProduk = (isset($products) && count($products) > 0) ? $products : $dummyProducts;
            @endphp

            <div class="grid md:grid-cols-3 gap-8">
                @foreach($listProduk as $item)
                    <a href="#produk" class="relative bg-[#FFFEFA] rounded-[32px] border border-[#7ADDF2]/40 text-center shadow-lg transition-all duration-500 flex flex-col items-center justify-center min-h-[360px] p-8 group overflow-hidden hover:bg-[#E6748E] hover:border-[#6BB1AD] hover:shadow-[0_20px_50px_rgba(107,177,173,0.28)] hover:-translate-y-2 no-underline">
                        <!-- Icon / Foto Produk -->
                        <div class="w-28 h-28 bg-[#FDEEF1] rounded-3xl p-4 shadow-sm mb-6 flex items-center justify-center text-4xl transition-all duration-500 group-hover:scale-90">
                            {{ $item->ikon ?? '🧴' }}
                        </div>

                        <div class="transition-all duration-500 group-hover:-translate-y-4">
                            <h3 class="font-serif text-2xl font-bold text-[#E6748E] group-hover:text-white transition-colors duration-500">
                                {{ $item->nama_produk }}
                            </h3>

                            <p class="mt-2 text-[#657674] font-medium group-hover:text-[#7ADDF2] transition-colors duration-500">
                                {{ is_object($item->brand) ? $item->brand->nama_brand : $item->brand }}
                            </p>
                        </div>

                        <div class="mt-6 flex flex-col items-center gap-3 opacity-0 max-h-0 overflow-hidden transition-all duration-500 group-hover:opacity-100 group-hover:max-h-44">
                            <p class="text-[#E5A9A9] text-xs font-black uppercase tracking-wider">
                                ✨ {{ $item->jenis_kulit }}
                            </p>
                            <p class="text-white font-bold text-lg">
                                Rp {{ $item->harga }}
                            </p>
                            <span class="bg-[#6BB1AD] text-white px-4 py-2 rounded-full text-xs font-black uppercase tracking-wider">
                                {{ $item->kandungan }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-16 text-center">
                <a href="#produk"
                   class="inline-block bg-[#E6748E] text-white font-black px-16 md:px-24 py-5 rounded-2xl shadow-xl transition-all duration-300 transform hover:bg-[#E6748E] hover:scale-105 uppercase tracking-wider text-xs animate-pulse-glow">
                    Lihat Semua Katalog Skincare ➔
                </a>
            </div>

        </div>
    </section>

    {{-- EVENT & BEAUTY TALKSHOW (SEPERTI SECTION EVENT DI INDEX) --}}
    <section id="event" class="bg-gradient-to-b from-[#FFFEFA] via-[#B8E8EE] to-[#E6748E] text-white py-24 relative overflow-hidden z-20">
        <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-12 items-center">

            <div class="space-y-6">
                <div>
                    <span class="bg-[#E6748E] text-white px-4 py-1.5 rounded-full text-xs font-black tracking-widest uppercase">
                        Beauty Event & Workshop
                    </span>
                </div>

                <h2 class="font-serif text-5xl font-bold leading-tight text-white">
                    Rawat kulitmu lewat<br>
                    <span class="text-[#E5A9A9] italic">edukasi yang tepat.</span>
                </h2>

                <p class="text-[#FDEEF1]/85 text-lg leading-relaxed">
                    Ikuti beauty talkshow, skin check gratis, dan workshop interaktif bersama dermatolog serta formulator pilihan.
                </p>

                <div class="pt-4">
                    <a href="#event"
                       class="inline-flex items-center gap-3 bg-[#6BB1AD] text-white px-8 py-4 rounded-2xl font-black hover:bg-[#E6748E] transition no-underline hover:scale-105 text-xs uppercase tracking-wider shadow-lg">
                        Lihat Jadwal Event <span>➔</span>
                    </a>
                </div>
            </div>

            {{-- Visual Botol & Kartu Klinik Estetik --}}
            <div class="relative rounded-[36px] overflow-hidden bg-gradient-to-br from-[#6BB1AD]/40 to-[#E6748E] aspect-[4/3] border border-[#6BB1AD]/40 flex items-center justify-center group shadow-2xl hover:-translate-y-2 transition duration-500 p-8">
                <div class="absolute w-72 h-72 rounded-full border border-[#FDEEF1]/20 animate-spin" style="animation-duration: 30s;"></div>
                <div class="relative z-10 w-64 h-72 rounded-[45%_45%_32%_32%] bg-gradient-to-br from-[#7ADDF2] to-[#E5A9A9] flex items-center justify-center shadow-2xl">
                    <div class="w-24 h-44 rounded-3xl bg-gradient-to-r from-[#FDEEF1] via-[#FFFEFA] to-[#E5D9C7] shadow-xl flex flex-col items-center justify-center -rotate-6 border border-white">
                        <span class="text-[9px] tracking-[2px] text-[#657674] font-extrabold uppercase">SKINOVA</span>
                        <strong class="font-serif text-lg text-[#E6748E] my-1">Elixir</strong>
                        <span class="text-[8px] text-[#6BB1AD] font-bold">30 ML • SERUM</span>
                    </div>
                </div>
                <div class="absolute bottom-6 left-6 bg-[#FFFEFA]/95 text-[#E6748E] px-5 py-3 rounded-2xl shadow-lg text-xs font-bold">
                    ✨ Live Skin Analysis • 24 Nov 2026
                </div>
            </div>

        </div>
    </section>

    {{-- TREATMENT & KONSULTASI SLIDER (SEPERTI SECTION SERVICES DI INDEX) --}}
    <section id="konsultasi" class="bg-[#E6748E] text-white py-24 relative z-20 overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(107,177,173,0.28),transparent_40%)] pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-6 relative z-10">

            <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-14 gap-8">
                <div class="text-center md:text-left">
                    <p class="text-xs uppercase tracking-[4px] text-[#6BB1AD] font-black mb-3">
                        Konsultasi & Klinik
                    </p>

                    <h2 class="font-serif text-4xl md:text-5xl font-bold text-white tracking-tight leading-tight">
                        Perawatan
                        <span class="text-[#E5A9A9] italic">terbaik</span>
                    </h2>
                </div>

                <div class="flex justify-center md:justify-end">
                    <a href="#konsultasi"
                       class="bg-[#E6748E] hover:bg-[#6BB1AD] text-white px-8 py-4 rounded-2xl font-black shadow-xl transition-all duration-300 hover:scale-105 no-underline text-xs uppercase tracking-wider">
                        Lihat Semua Treatment
                    </a>
                </div>
            </div>

            @php
                $dummyTreatments = [
                    (object)['nama' => 'Deep Hydration Facial', 'dokter' => 'dr. Clara Wijaya, Sp.KK', 'lokasi' => 'Jakarta Selatan', 'desc' => 'Perawatan infus kelembapan mendalam untuk mengembalikan elastisitas dan menenangkan kulit kemerahan.', 'harga' => 350000, 'kode' => 'DH'],
                    (object)['nama' => 'Acne Calming LED Therapy', 'dokter' => 'dr. Nadya Putri', 'lokasi' => 'Bandung', 'desc' => 'Terapi sinar biru dan merah untuk meredakan peradangan jerawat aktif tanpa rasa sakit.', 'harga' => 290000, 'kode' => 'AC'],
                    (object)['nama' => 'Personal Skin Barrier Consult', 'dokter' => 'dr. Reza Pratama, Sp.DVE', 'lokasi' => 'Online / Medan', 'desc' => 'Sesi konsultasi 1-on-1 untuk menyusun urutan skincare pagi dan malam sesuai kondisi kulitmu.', 'harga' => 120000, 'kode' => 'SB'],
                    (object)['nama' => 'Botanical Peeling Glow', 'dokter' => 'dr. Amelia Santoso', 'lokasi' => 'Surabaya', 'desc' => 'Eksfoliasi lembut berbahan ekstrak tumbuhan alami untuk mengangkat sel kulit mati dan mencerahkan.', 'harga' => 420000, 'kode' => 'BP'],
                ];
            @endphp

            <div id="services-container"
                 class="flex overflow-x-auto gap-7 pb-8 cursor-grab active:cursor-grabbing scroll-smooth no-scrollbar">

                @foreach($dummyTreatments as $srv)
                    <div class="flex-none w-[300px] md:w-[360px] bg-[#FDEEF1] text-[#E6748E]
                                p-7 rounded-[32px] border border-[#7ADDF2]/40
                                shadow-[0_18px_40px_rgba(0,0,0,0.25)]
                                transition-all duration-500
                                hover:-translate-y-2 hover:bg-[#FFFEFA]
                                hover:shadow-[0_25px_60px_rgba(107,177,173,0.28)]
                                flex flex-col justify-between group">

                        <div>
                            <div class="w-20 h-20 bg-[#6BB1AD] rounded-3xl flex items-center justify-center mb-6 overflow-hidden
                                        text-white font-serif text-2xl font-bold shadow-md transition-all duration-500 group-hover:scale-105 group-hover:bg-[#E6748E]">
                                <span>{{ $srv->kode }}</span>
                            </div>

                            <h3 class="font-serif text-2xl font-bold mb-2 leading-snug text-[#E6748E] line-clamp-2">
                                {{ $srv->nama }}
                            </h3>

                            <p class="text-xs font-black text-[#6BB1AD] mb-4 tracking-wide uppercase line-clamp-1">
                                {{ $srv->dokter }} • {{ $srv->lokasi }}
                            </p>

                            <p class="text-[#657674] leading-relaxed text-sm line-clamp-3 mb-6">
                                {{ $srv->desc }}
                            </p>
                        </div>

                        <div class="pt-5 border-t border-[#E6748E]/15 flex items-center justify-between gap-4 mt-auto">
                            <div>
                                <p class="text-xs text-[#657674] font-bold mb-1">
                                    Mulai dari
                                </p>

                                <p class="text-xl font-black text-[#E6748E]">
                                    Rp{{ number_format($srv->harga, 0, ',', '.') }}
                                </p>
                            </div>

                            <a href="#konsultasi"
                               class="shrink-0 bg-[#E6748E] text-white px-5 py-3 rounded-2xl text-xs font-black tracking-wider
                                      hover:bg-[#E6748E] transition-all duration-300 uppercase no-underline">
                                Booking ➔
                            </a>
                        </div>
                    </div>
                @endforeach

            </div>

        </div>
    </section>

    {{-- SKIN CLASS & EDUKASI (SEPERTI SECTION COURSES DI INDEX) --}}
    <section id="edukasi" class="bg-gradient-to-b from-[#E6748E] via-[#345452] to-[#FDEEF1] text-white py-24 relative z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-12 gap-6">
                <div class="text-center md:text-left">
                    <span class="text-xs font-black uppercase tracking-[4px] text-[#E5A9A9]">
                        Kelas & Panduan Terpilih
                    </span>

                    <h2 class="font-serif text-4xl md:text-5xl font-bold mt-2 tracking-tight leading-tight">
                        Pahami Kandungan
                        <span class="text-[#E5A9A9] italic">Skincare</span>
                    </h2>
                </div>

                <div class="hidden md:block">
                    <a href="#edukasi"
                       class="inline-flex items-center justify-center bg-[#E5A9A9] hover:bg-[#FFFEFA] text-[#E6748E] px-7 py-4 rounded-2xl font-black shadow-xl transition-all duration-300 hover:scale-105 no-underline text-xs uppercase tracking-wider">
                        Lihat Semua Kelas
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>

            @php
                $dummyClasses = [
                    (object)[
                        'id' => 1,
                        'title' => 'Basic Skincare 101: Mengenal Jenis & Kebutuhan Kulit',
                        'description' => 'Pelajari cara membaca daftar komposisi (ingredients list), urutan pemakaian CTMP yang benar, dan menghindari layering bahan aktif yang memicu iritasi.',
                        'benefit' => 'E-Book Panduan Layering + Sertifikat + Voucher Belanja 20%',
                        'price' => 0
                    ],
                    (object)[
                        'id' => 2,
                        'title' => 'Masterclass Perbaikan Skin Barrier & Kulit Sensitif',
                        'description' => 'Panduan lengkap mengatasi kulit kemerahan, perih, dan dehidrasi dengan pendekatan minimalis berbasis ceramide dan microbiome.',
                        'benefit' => 'Sesi Tanya Jawab Live Dermatolog + Sampel Kit Skinova',
                        'price' => 99000
                    ],
                ];
            @endphp

            <div class="space-y-5">
                @foreach($dummyClasses as $class)
                    <div class="reveal-item opacity-0 translate-y-10 relative overflow-hidden
                                bg-gradient-to-r from-[#2E4D4B] via-[#E6748E] to-[#1D302F]
                                border border-[#6BB1AD]/40
                                rounded-[28px]
                                p-7 md:p-8
                                flex flex-col lg:flex-row lg:items-center justify-between gap-6
                                shadow-[0_15px_40px_rgba(0,0,0,0.22)]
                                transition-all duration-500
                                hover:-translate-y-2
                                hover:border-[#E5A9A9]
                                hover:shadow-[0_20px_50px_rgba(229,169,169,0.2)]
                                group">

                        <div class="absolute top-0 right-0 w-40 h-40 bg-[#6BB1AD]/15 rounded-full blur-3xl pointer-events-none"></div>

                        <div class="flex-1 min-w-0 relative z-10">
                            <div class="flex flex-wrap items-center gap-3 mb-4">
                                <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-black bg-[#E5A9A9] text-[#E6748E] shadow-md">
                                    {{ $class->price > 0 ? 'Masterclass' : 'Gratis' }}
                                </span>

                                <span class="text-xs font-bold text-[#7ADDF2]">
                                    Modul #{{ $class->id }}
                                </span>
                            </div>

                            <h3 class="font-serif text-2xl font-bold text-white tracking-tight leading-tight group-hover:text-[#E5A9A9] transition-colors duration-300">
                                {{ $class->title }}
                            </h3>

                            <p class="mt-3 text-sm text-[#FDEEF1]/80 line-clamp-2 max-w-3xl leading-relaxed">
                                {{ $class->description }}
                            </p>

                            <div class="mt-5 inline-flex items-center bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-xs text-[#FDEEF1]/90 max-w-full">
                                <span class="font-black text-[#6BB1AD] mr-2 shrink-0">Benefit:</span>
                                <span class="truncate max-w-xs md:max-w-md">
                                    {{ $class->benefit }}
                                </span>
                            </div>
                        </div>

                        <div class="relative z-10 flex flex-row lg:flex-col items-center lg:items-end justify-between lg:justify-center gap-4 pt-4 lg:pt-0 min-w-[190px]">
                            <div class="text-left lg:text-right">
                                <p class="text-xs text-[#7ADDF2] uppercase tracking-wider font-bold">
                                    Akses Kelas
                                </p>

                                <p class="text-2xl font-black text-[#E5A9A9] mt-1">
                                    @if($class->price > 0)
                                        Rp {{ number_format($class->price, 0, ',', '.') }}
                                    @else
                                        Gratis
                                    @endif
                                </p>
                            </div>

                            <div class="w-full lg:w-auto">
                                <a href="#edukasi"
                                   class="inline-flex items-center justify-center
                                          px-6 py-3
                                          rounded-2xl
                                          bg-[#FFFEFA]
                                          hover:bg-[#E6748E]
                                          hover:text-white
                                          text-[#E6748E]
                                          font-black
                                          text-xs uppercase tracking-wider
                                          transition-all duration-300
                                          hover:scale-105
                                          shadow-lg
                                          w-full whitespace-nowrap
                                          no-underline">
                                    Daftar Sekarang
                                </a>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="bg-[#FDEEF1] text-[#E6748E] py-12 px-6 border-t border-[#E6748E]/10 relative z-20">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4 text-xs font-bold text-[#657674]">
            <div class="flex items-center gap-2 text-[#E6748E] font-extrabold text-base">
                <span class="w-7 h-7 rounded-lg bg-[#6BB1AD] grid place-items-center text-white text-sm">✦</span>
                <span>{{ config('app.name', 'Skinova') }}</span>
            </div>
            <p>© {{ date('Y') }} Skinova Botanical & Derma Care. Semua hak dilindungi.</p>
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
