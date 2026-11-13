
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#F8EBDD">

    <title>Aphrodite — Skincare Recommender</title>

    <meta
        name="description"
        content="Temukan rekomendasi skincare Wardah, Emina, Kahf, Labore, Crystalure, dan tavi berdasarkan kebutuhan kulitmu dengan Web Semantik dan ontologi."
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="m-0 h-screen overflow-x-hidden overflow-y-hidden bg-[#F8EBDD] font-sans text-[#70483E] antialiased">

    {{-- NAVBAR --}}
    <header class="absolute inset-x-0 top-0 z-50 px-4 pt-4 md:px-8 md:pt-5">
        <nav class="mx-auto flex max-w-[1320px] items-center justify-between rounded-full border border-white/50 bg-[#FFF9F0]/85 px-5 py-2 shadow-sm backdrop-blur-md md:px-7">

  {{-- LOGO --}}
            <a href="#beranda" class="flex shrink-0 items-center gap-2 text-[#79262A]">
                <img
                    src="{{ asset('images/logo-aphrodite.png') }}"
                    alt="Logo Aphrodite"
                    class="h-[52px] w-[52px] shrink-0 scale-[1.7] translate-y-[12px] object-contain"
                >

                <span class="font-display text-2xl tracking-tight">
                    Aphrodite
                </span>
            </a>

  {{-- MENU DESKTOP --}}
            <div class="hidden items-center gap-8 text-[12px] lg:flex">

                <a
                    href="#beranda"
                    class="nav-link group relative inline-block pb-1.5 font-bold text-[#79262A] transition-colors duration-300 after:absolute after:bottom-0 after:left-0 after:h-[1.5px] after:w-full after:origin-center after:scale-x-100 after:bg-[#79262A] after:transition-transform after:duration-300"
                    aria-current="page"
                    data-nav="beranda"
                >
                    Beranda
                </a>

                <a
                    href="#cari"
                    class="nav-link group relative inline-block pb-1.5 font-normal text-[#795E50] transition-colors duration-300 after:absolute after:bottom-0 after:left-0 after:h-[1.5px] after:w-full after:origin-center after:scale-x-0 after:bg-[#79262A] after:transition-transform after:duration-300 hover:text-[#79262A]"
                    data-nav="cari"
                >
                    Cari Produk
                </a>

                <a
                    href="#tentang"
                    class="nav-link group relative inline-block pb-1.5 font-normal text-[#795E50] transition-colors duration-300 after:absolute after:bottom-0 after:left-0 after:h-[1.5px] after:w-full after:origin-center after:scale-x-0 after:bg-[#79262A] after:transition-transform after:duration-300 hover:text-[#79262A]"
                    data-nav="tentang"
                >
                    Tentang Kami
                </a>

            </div>

    {{-- TOMBOL KUIS DESKTOP --}}
            <a
                href="{{ route('kuis') }}"
                class="hidden items-center gap-3 rounded-full bg-[#79262A] px-6 py-3 text-xs font-medium text-white transition duration-300 hover:-translate-y-0.5 hover:bg-[#501512] sm:inline-flex"
            >
                Mulai Kuis
                <span aria-hidden="true">→</span>
            </a>

            {{-- MENU MOBILE --}}
            <details class="relative lg:hidden">

                <summary
                    aria-label="Buka menu navigasi"
                    class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full border border-[#D9C5AF] text-[#79262A] transition-colors duration-300 hover:bg-[#F1E4D3]"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        aria-hidden="true"
                    >
                        <path
                            d="M4 7h16M4 12h16M4 17h16"
                            stroke-linecap="round"
                        />
                    </svg>
                </summary>

                <div class="absolute right-0 top-14 flex w-56 flex-col gap-1 rounded-2xl border border-[#E3D6BF] bg-[#FFF9F0] p-3 shadow-xl">

                    <a
                        href="#beranda"
                        class="mobile-link rounded-xl bg-[#F1E4D3] px-4 py-3 text-sm font-bold text-[#79262A] transition-colors duration-300"
                        aria-current="page"
                        data-nav="beranda"
                    >
                        Beranda
                    </a>

                    <a
                        href="#katalog"
                        class="mobile-link rounded-xl px-4 py-3 text-sm font-normal text-[#795E50] transition-colors duration-300 hover:bg-[#F1E4D3] hover:text-[#79262A]"
                        data-nav="katalog"
                    >
                        Cari Produk
                    </a>

                    <a
                        href="{{ route('kuis') }}"
                        class="rounded-xl px-4 py-3 text-sm transition-colors duration-300 hover:bg-[#F1E4D3]"
                    >
                        Kuis Semantik
                    </a>

                    <a
                        href="#tentang"
                        class="mobile-link rounded-xl px-4 py-3 text-sm font-normal text-[#795E50] transition-colors duration-300 hover:bg-[#F1E4D3] hover:text-[#79262A]"
                        data-nav="tentang"
                    >
                        Tentang Kami
                    </a>

                    <a
                        href="{{ route('kuis') }}"
                        class="mt-1 rounded-xl bg-[#79262A] px-4 py-3 text-center text-sm text-white transition-colors duration-300 hover:bg-[#501512]"
                    >
                        Mulai Kuis →
                    </a>

                </div>
            </details>

        </nav>
    </header>

    <main>

        {{-- HERO / BERANDA --}}
        <section
            id="beranda"
            class="relative isolate flex h-screen w-full items-center overflow-hidden bg-[#F8EBDD]"
        >

            {{-- VIDEO BACKGROUND --}}
            <video
                class="absolute inset-0 z-0 h-full w-full object-cover"
                style="object-position: center 20%;"
                autoplay
                muted
                loop
                playsinline
                preload="auto"
                aria-hidden="true"
            >
                <source
                    src="{{ asset('videos/skincare-aphrodite.mp4') }}"
                    type="video/mp4"
                >
            </video>

            {{-- OVERLAY TIPIS --}}
            <div
                class="pointer-events-none absolute inset-0 z-10 bg-[#F8EBDD]/10"
                aria-hidden="true"
            ></div>

            {{-- GRADIENT UNTUK KETERBACAAN TEKS --}}
            <div
                class="pointer-events-none absolute inset-0 z-10 bg-gradient-to-r from-[#F8EBDD]/65 via-[#F8EBDD]/20 to-transparent"
                aria-hidden="true"
            ></div>

            {{-- DEKORASI LINGKARAN --}}
            <div
                class="pointer-events-none absolute -right-20 top-20 z-10 h-72 w-72 rounded-full border border-[#B49A7B]/30 md:h-[420px] md:w-[420px]"
                aria-hidden="true"
            ></div>

            <div
                class="pointer-events-none absolute -right-10 top-28 z-10 h-60 w-60 rounded-full border border-[#B49A7B]/20 md:h-[340px] md:w-[340px]"
                aria-hidden="true"
            ></div>

            {{-- KONTEN HERO --}}
            <div
                class="relative z-20 mx-auto grid h-full w-full max-w-[1240px] grid-cols-1 items-center px-5 pb-5 pt-20 md:grid-cols-[1.08fr_0.92fr] md:gap-3 md:px-9 md:pb-5 md:pt-20 lg:px-12"
            >

                {{-- TEKS HERO --}}
               <div class="relative -translate-x-15 max-w-[560px] py-2">

                    <div class="mb-3 flex items-center gap-2.5 text-[12px] uppercase tracking-[0.2em] text-[#92564B]">
                        <span class="h-px w-10 bg-[#92564B]"></span>
                        Skincare Recommender
                    </div>

                    <h1 class="font-display text-[clamp(36px,4vw,54px)] leading-[1.12] tracking-[-0.025em] text-[#602923]">
                        Kenali Kulitmu
                        <br>
                        Temukan Skincare-mu
                    </h1>

                    <p class="mt-6 max-w-[520px] text-sm leading-6 text-[#795E50] md:text-[13px] md:leading-[1.8]">
                        Kenali kebutuhan kulitmu dan temukan produk skincare
                        yang sesuai dengan jenis kulit, permasalahan, serta
                        kandungan yang kamu butuhkan. Saatnya memilih skincare
                        dengan lebih percaya diri!
                    </p>

                    {{-- TOMBOL HERO --}}
                    {{-- <div class="mt-4 flex flex-wrap items-center gap-2">

                        <a
                            href="#katalog"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#79262A] px-4 py-2.5 text-[10px] font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-[#501512]"
                        >
                            Lihat Katalog
                        </a>

                        <a
                            href="#katalog"
                            class="inline-flex items-center justify-center rounded-xl border border-[#945E50] bg-white/20 px-4 py-2.5 text-[10px] font-medium text-[#71352E] transition duration-300 hover:bg-white/50"
                        >
                            Lihat Katalog
                        </a>

                    </div> --}}




<!-- BRAND YANG TERINTEGRASI -->
<section id="brand" class="mt-5 md:mt-6">
    <div class="flex items-center gap-3">
        <span class="h-px w-8 bg-[#79262A]/70"></span>
        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#795E50]">
            Brand yang terintegrasi
        </p>
    </div>

    <div class="mt-3 grid grid-cols-3 gap-2">

        <!-- WARDAH -->
        <div class="flex h-14 items-center justify-center rounded-lg border border-[#CDAE8F] bg-[#FFF9F0]/70 px-1.5 transition duration-300 hover:-translate-y-0.5 md:h-[68px]">
            <span class="relative inline-block text-xl font-normal tracking-tight text-[#43A8B8] sm:text-2xl"
                style="font-family: 'Century Gothic', 'Montserrat', sans-serif; letter-spacing: -0.055em;">
                Wardah
                <span class="absolute right-[14px] top-[-6px] text-base font-normal leading-none">~</span>
            </span>
        </div>

        <!-- EMINA -->
        <div class="flex h-14 items-center justify-center rounded-lg border border-[#CDAE8F] bg-[#FFF9F0]/70 px-1.5 transition duration-300 hover:-translate-y-0.5 md:h-[68px]">
            <div class="flex items-center gap-2">
                <svg viewBox="0 0 40 40" class="h-5 w-5 text-[#FA7895] sm:h-6 sm:w-6" fill="none" aria-hidden="true">
                    <path d="M20 35 5 20C-2 12 9 3 17 11L20 14 23 11C31 3 42 12 35 20L20 35Z"
                        stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
                    <path d="m12 15 16 12M28 15 12 27M20 11l9 9-9 9-9-9 9-9Z"
                        stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                </svg>
                <span class="text-xl font-light tracking-tight text-[#FA7895] sm:text-2xl">
                    emina
                </span>
            </div>
        </div>

        <!-- LABORÉ -->
        <div class="flex h-14 items-center justify-center rounded-lg border border-[#CDAE8F] bg-[#FFF9F0]/70 px-1.5 transition duration-300 hover:-translate-y-0.5 md:h-[68px]">
            <span class="relative inline-block text-xl font-medium leading-none tracking-tight text-[#4D4B49] sm:text-2xl"
                style="font-family: 'Montserrat', sans-serif;">
                LABORE
                <span class="absolute right-[1px] top-[-4px] block h-[3px] w-[9px] rotate-[-48deg] rounded-[1px] bg-[#FF4774]"></span>
            </span>
        </div>

        <!-- KAHF -->
        <div class="flex h-14 items-center justify-center rounded-lg border border-[#CDAE8F] bg-[#FFF9F0]/70 px-1.5 transition duration-300 hover:-translate-y-0.5 md:h-[68px]">
            <span class="font-sans text-2xl font-normal tracking-tight text-[#52664D] sm:text-3xl">
                Kahf
            </span>
        </div>

        <!-- CRYSTALLURE -->
        <div class="flex h-14 items-center justify-center rounded-lg border border-[#CDAE8F] bg-[#FFF9F0]/70 px-1 transition duration-300 hover:-translate-y-0.5 md:h-[68px]">
            <span class="whitespace-nowrap text-[9px] font-medium tracking-[0.12em] text-[#B38A25] sm:text-xs md:text-sm">
                CRYSTALLURE
            </span>
        </div>

        <!-- TAVI -->
        <div class="flex h-14 items-center justify-center rounded-lg border border-[#CDAE8F] bg-[#FFF9F0]/70 px-1.5 transition duration-300 hover:-translate-y-0.5 md:h-[68px]">
            <span class="text-2xl font-semibold tracking-tight text-[#9270B6] sm:text-3xl">
                tavi<span class="ml-0.5 self-start text-[8px]">™</span>
            </span>
        </div>

    </div>
</section>


    {{-- SCRIPT ANIMASI NAVBAR --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const navLinks = document.querySelectorAll('.nav-link');
            const mobileLinks = document.querySelectorAll('.mobile-link');

            const activeDesktopClasses = [
                'font-bold',
                'text-[#79262A]',
                'after:scale-x-100'
            ];

            const inactiveDesktopClasses = [
                'font-normal',
                'text-[#795E50]',
                'after:scale-x-0'
            ];

            const activeMobileClasses = [
                'bg-[#F1E4D3]',
                'font-bold',
                'text-[#79262A]'
            ];

            const inactiveMobileClasses = [
                'font-normal',
                'text-[#795E50]'
            ];

            function activateDesktopLink(selectedLink) {
                navLinks.forEach(function (link) {
                    link.classList.remove(...activeDesktopClasses);
                    link.classList.add(...inactiveDesktopClasses);
                    link.removeAttribute('aria-current');
                });

                selectedLink.classList.remove(...inactiveDesktopClasses);
                selectedLink.classList.add(...activeDesktopClasses);
                selectedLink.setAttribute('aria-current', 'page');
            }

            function activateMobileLink(selectedLink) {
                mobileLinks.forEach(function (link) {
                    link.classList.remove(...activeMobileClasses);
                    link.classList.add(...inactiveMobileClasses);
                    link.removeAttribute('aria-current');
                });

                selectedLink.classList.remove(...inactiveMobileClasses);
                selectedLink.classList.add(...activeMobileClasses);
                selectedLink.setAttribute('aria-current', 'page');
            }

            navLinks.forEach(function (link) {
                link.addEventListener('click', function () {
                    activateDesktopLink(link);
                });
            });

            mobileLinks.forEach(function (link) {
                link.addEventListener('click', function () {
                    activateMobileLink(link);

                    const mobileMenu = link.closest('details');

                    if (mobileMenu) {
                        mobileMenu.removeAttribute('open');
                    }
                });
            });
        });
    </script>

</body>
</html>
