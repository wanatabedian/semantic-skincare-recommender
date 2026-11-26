{{-- Orang 5 — Eksplorasi Katalog (tahap 1: fondasi visual) --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('user.katalog.head', [
        'title' => 'Katalog Produk',
        'description' => 'Jelajahi katalog skincare Aphrodite yang tersusun dari ontologi: jenis kulit, keluhan, dan bahan aktif.',
    ])
</head>
<body class="min-h-screen bg-warm font-sans text-ink antialiased">

    @include('user.katalog.navbar', ['active' => 'katalog'])

    <main id="katalog">

        {{-- ================= HERO ================= --}}
        <header class="k-hero relative overflow-hidden bg-cream pt-28 pb-24 md:pt-36 md:pb-32">
            <div class="k-hero__rings" aria-hidden="true">
                <span></span><span></span><span></span>
            </div>

            <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 md:grid-cols-12 md:px-10 lg:px-16">
                <div class="md:col-span-7">
                    <p class="k-rise flex items-center gap-3 text-[11px] font-black uppercase tracking-[.32em] text-teal" style="--d:0ms">
                        <span class="h-px w-8 bg-teal/60" aria-hidden="true"></span>
                        Katalog Aphrodite
                    </p>

                    <h1 class="k-rise mt-6 font-serif text-[2.6rem] font-semibold leading-[1.05] text-rose sm:text-6xl lg:text-7xl" style="--d:120ms">
                        Rak perawatan,<br>
                        disusun <em class="k-hero__em">sesuai kulitmu.</em>
                    </h1>

                    <p class="k-rise mt-7 max-w-xl text-base leading-relaxed text-muted md:text-lg" style="--d:240ms">
                        Jelajahi produk yang saling terhubung lewat ontologi: setiap item tercatat dengan jenis kulit,
                        keluhan, dan bahan aktifnya, jadi kamu bisa memilih dengan alasan, bukan tebakan.
                    </p>

                    <dl class="k-rise mt-10 flex flex-wrap items-end gap-x-10 gap-y-6" style="--d:360ms">
                        <div>
                            <dt class="text-[11px] font-black uppercase tracking-[.22em] text-muted">Produk terdata</dt>
                            @if ($productCount !== null)
                                <dd class="mt-1 font-serif text-5xl font-semibold text-ink tabular-nums">{{ number_format($productCount, 0, ',', '.') }}</dd>
                            @else
                                <dd class="mt-2 max-w-[14rem] text-sm font-semibold text-muted">Jumlah produk belum dapat dimuat saat ini.</dd>
                            @endif
                        </div>
                        <div class="hidden h-12 w-px bg-rose/20 sm:block" aria-hidden="true"></div>
                        <div>
                            <dt class="text-[11px] font-black uppercase tracking-[.22em] text-muted">Ditelusuri lewat</dt>
                            <dd class="mt-2 flex flex-wrap gap-2 text-xs font-bold">
                                <span class="rounded-full border border-teal/40 bg-warm/70 px-3 py-1.5 text-ink">Jenis kulit</span>
                                <span class="rounded-full border border-teal/40 bg-warm/70 px-3 py-1.5 text-ink">Keluhan</span>
                                <span class="rounded-full border border-teal/40 bg-warm/70 px-3 py-1.5 text-ink">Bahan aktif</span>
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Komposisi dekoratif: botol serum, toples krim, dan tetes --}}
                <div class="k-rise hidden md:col-span-5 md:block" style="--d:200ms" aria-hidden="true">
                    <div class="k-still">
                        <div class="k-still__arch"></div>
                        <svg class="k-still__art" viewBox="0 0 320 360" fill="none">
                            {{-- daun --}}
                            <path d="M60 300c10-70 50-118 118-140-22 64-62 112-118 140Z" fill="#6BB1AD" fill-opacity=".22" stroke="#6BB1AD" stroke-width="1.4"/>
                            <path d="M66 292c34-38 66-78 104-122" stroke="#6BB1AD" stroke-width="1.2" stroke-linecap="round"/>
                            {{-- botol serum --}}
                            <g class="k-float">
                                <rect x="168" y="126" width="74" height="170" rx="26" fill="#FFFEFA" stroke="#E6748E" stroke-width="1.6"/>
                                <rect x="168" y="196" width="74" height="70" fill="#FDEEF1"/>
                                <rect x="168" y="126" width="74" height="170" rx="26" stroke="#E6748E" stroke-width="1.6"/>
                                <rect x="188" y="96" width="34" height="32" rx="8" fill="#E6748E" fill-opacity=".85"/>
                                <path d="M205 96V58" stroke="#E6748E" stroke-width="1.6" stroke-linecap="round"/>
                                <ellipse cx="205" cy="50" rx="12" ry="10" fill="#E6748E" fill-opacity=".85"/>
                                <path d="M182 220h46M182 234h30" stroke="#E6748E" stroke-opacity=".6" stroke-width="1.4" stroke-linecap="round"/>
                            </g>
                            {{-- toples krim --}}
                            <rect x="84" y="250" width="92" height="56" rx="18" fill="#B8E8EE" fill-opacity=".55" stroke="#345452" stroke-opacity=".55" stroke-width="1.4"/>
                            <rect x="78" y="236" width="104" height="20" rx="8" fill="#FFFEFA" stroke="#345452" stroke-opacity=".55" stroke-width="1.4"/>
                            {{-- tetes --}}
                            <path class="k-drip" d="M270 70c8 10.5 12 17.5 12 23a12 12 0 0 1-24 0c0-5.5 4-12.5 12-23Z" fill="#6BB1AD" fill-opacity=".7"/>
                            <circle cx="70" cy="92" r="5" fill="#E5A9A9"/>
                            <circle cx="292" cy="250" r="3.5" fill="#E6748E" fill-opacity=".6"/>
                        </svg>
                        <p class="k-still__tag">Botanical &amp; Derma Care</p>
                    </div>
                </div>
            </div>
        </header>

        {{-- ================= SEARCH ================= --}}
        <section class="relative z-10 mx-auto -mt-10 max-w-5xl px-4 sm:px-6" aria-labelledby="katalog-search-label">
            {{-- Kolom search tergabung ke form filter (#katalog-form) agar search & filter selalu terkirim bersama. --}}
            <div role="search" class="k-search k-rise" style="--d:460ms">
                <label id="katalog-search-label" for="katalog-q" class="sr-only">Cari nama produk</label>
                <svg class="k-search__icon h-5 w-5 shrink-0 text-teal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5" stroke-linecap="round"/></svg>
                <input id="katalog-q" name="q" type="search" value="{{ $search }}" autocomplete="off" form="katalog-form"
                       maxlength="{{ \App\Http\Controllers\CatalogController::SEARCH_MAX_LENGTH }}" enterkeyhint="search"
                       placeholder="Cari nama produk…"
                       class="min-w-0 flex-1 bg-transparent text-sm font-medium text-ink placeholder:text-muted/70 focus:outline-none md:text-base">
                <kbd class="hidden rounded-md border border-ink/15 px-2 py-1 text-[10px] font-bold text-muted md:inline" title="Tekan / untuk mencari">/</kbd>
                <button type="submit" form="katalog-form" class="k-btn shrink-0 rounded-xl bg-rose px-4 py-3 text-xs font-black uppercase tracking-wider text-white hover:bg-teal sm:px-6">
                    Cari
                </button>
            </div>
        </section>

        {{-- ================= KATALOG: SARING BRAND + LISTING ================= --}}
        <div class="mx-auto mt-14 grid max-w-7xl gap-10 px-4 pb-24 sm:px-6 md:px-10 lg:px-16">

            {{-- ---------- SARING BRAND (di atas katalog, bukan sidebar) ---------- --}}
            <section id="katalog-filter" aria-label="Saring brand" class="min-w-0">
                @include('user.katalog.filter-panel')
            </section>

            {{-- ---------- LISTING ---------- --}}
            <section id="katalog-listing" aria-labelledby="katalog-list-title" class="k-listing min-w-0">
                {{-- Toolbar: judul hasil + sorting --}}
                <div class="flex flex-col gap-5 border-b border-rose/15 pb-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[.28em] text-teal">Koleksi</p>
                        <h2 id="katalog-list-title" class="mt-2 font-serif text-3xl font-semibold text-ink md:text-4xl" tabindex="-1" data-katalog-swap>{{ $hasFilters ? 'Hasil pilihanmu' : 'Semua produk' }}</h2>
                        <p class="mt-2 text-sm text-muted" id="katalog-result-info" role="status" aria-live="polite" data-katalog-swap>
                            @if ($error)
                                Data produk belum dapat dimuat.
                            @else
                                @php($from = $matchedCount > 0 ? ($state['page'] - 1) * \App\Http\Controllers\CatalogController::PER_PAGE + 1 : 0)
                                @if ($pagination)
                                    Menampilkan <span class="font-bold text-ink tabular-nums">{{ $from }}–{{ $from + count($products) - 1 }}</span>
                                    dari <span class="font-bold text-ink tabular-nums">{{ $matchedCount }}</span> produk
                                @else
                                    Menampilkan <span class="font-bold text-ink tabular-nums">{{ $matchedCount }}</span> produk
                                @endif
                                dari <span class="font-bold text-ink">{{ $brandCount }}</span> brand
                                @if ($search !== '')
                                    untuk pencarian <span class="font-bold text-ink">“{{ $search }}”</span>
                                @endif
                                @if ($pagination)
                                    <span class="k-info__page">Halaman {{ $pagination['page'] }} dari {{ $pagination['last'] }}</span>
                                @endif
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        {{-- Sorting ikut #katalog-form; perubahan sort selalu kembali ke halaman 1 (page tidak ada di form). --}}
                        <label class="k-sort">
                            <span class="k-sort__label text-[11px] font-black uppercase tracking-wider text-muted">Urutkan</span>
                            <select id="katalog-sort" name="sort" form="katalog-form" class="k-sort__select bg-transparent text-sm font-bold text-ink focus:outline-none">
                                @foreach ($sorts as $value => $label)
                                    <option value="{{ $value === \App\Http\Controllers\CatalogController::DEFAULT_SORT ? '' : $value }}" @selected($state['sort'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <svg class="k-sort__chev" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m3 4.5 3 3 3-3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </label>
                    </div>
                </div>

                {{-- Hasil: chip filter aktif + grid + pager / empty state (ditukar oleh katalog.js) --}}
                <div class="k-progress" aria-hidden="true"></div>
                <div id="katalog-results" class="k-results" data-katalog-swap>
                    @include('user.katalog.results')
                </div>
            </section>
        </div>
    </main>

    @include('user.katalog.footer')

    <script src="{{ asset('js/katalog.js') }}" defer></script>
</body>
</html>
