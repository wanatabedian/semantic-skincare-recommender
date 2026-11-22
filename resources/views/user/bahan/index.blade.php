{{-- Orang 5 — Direktori bahan aktif (/bahan). Data: IngredientController::index(). --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('user.katalog.head', [
        'title' => 'Kamus Bahan Aktif',
        'description' => 'Direktori bahan aktif skincare yang tercatat di ontologi Aphrodite, lengkap dengan produk yang mengandungnya.',
        'styles' => ['katalog', 'bahan'],
    ])
</head>
<body class="min-h-screen bg-warm font-sans text-ink antialiased">

    @include('user.katalog.navbar', ['active' => 'bahan'])

    <main id="bahan">

        {{-- ================= HERO ================= --}}
        <header class="k-hero b-hero relative overflow-hidden bg-cream pt-28 pb-24 md:pt-36 md:pb-28">
            <div class="b-hero__glyphs" aria-hidden="true">
                <span>Aa</span><span>Bb</span><span>Cc</span>
            </div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 md:px-10 lg:px-16">
                <p class="k-rise flex items-center gap-3 text-[11px] font-black uppercase tracking-[.32em] text-teal" style="--d:0ms">
                    <span class="h-px w-8 bg-teal/60" aria-hidden="true"></span>
                    Kamus Bahan Aktif
                </p>

                <h1 class="k-rise mt-6 max-w-3xl font-serif text-[2.6rem] font-semibold leading-[1.05] text-rose sm:text-6xl lg:text-7xl" style="--d:120ms">
                    Kenali bahannya,<br>
                    <em class="k-hero__em">pilih dengan yakin.</em>
                </h1>

                <p class="k-rise mt-7 max-w-xl text-base leading-relaxed text-muted md:text-lg" style="--d:240ms">
                    Setiap bahan di sini tercatat di ontologi Aphrodite dan terhubung langsung dengan produk yang
                    mengandungnya. Buka satu bahan untuk melihat produk mana saja yang memakainya.
                </p>

                <dl class="k-rise mt-10 flex flex-wrap items-end gap-x-10 gap-y-6" style="--d:360ms">
                    <div>
                        <dt class="text-[11px] font-black uppercase tracking-[.22em] text-muted">Bahan tercatat</dt>
                        @if ($error === null)
                            <dd class="mt-1 font-serif text-5xl font-semibold text-ink tabular-nums">{{ count($ingredients) }}</dd>
                        @else
                            <dd class="mt-2 max-w-[14rem] text-sm font-semibold text-muted">Jumlah bahan belum dapat dimuat saat ini.</dd>
                        @endif
                    </div>
                    @if ($linkedProducts !== null)
                        <div class="hidden h-12 w-px bg-rose/20 sm:block" aria-hidden="true"></div>
                        <div>
                            <dt class="text-[11px] font-black uppercase tracking-[.22em] text-muted">Produk terhubung</dt>
                            <dd class="mt-1 font-serif text-5xl font-semibold text-ink tabular-nums">{{ $linkedProducts }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </header>

        {{-- ================= SEARCH ================= --}}
        <section class="relative z-10 mx-auto -mt-10 max-w-5xl px-4 sm:px-6" aria-labelledby="bahan-search-label">
            <form action="{{ route('bahan') }}" method="GET" role="search" class="k-search k-rise" style="--d:460ms" id="bahan-form">
                <label id="bahan-search-label" for="bahan-q" class="sr-only">Cari nama bahan</label>
                <svg class="k-search__icon h-5 w-5 shrink-0 text-teal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5" stroke-linecap="round"/></svg>
                <input id="bahan-q" name="q" type="search" value="{{ $search }}" autocomplete="off"
                       maxlength="{{ \App\Http\Controllers\CatalogController::SEARCH_MAX_LENGTH }}" enterkeyhint="search"
                       placeholder="Cari nama bahan, mis. niacinamide…"
                       class="min-w-0 flex-1 bg-transparent text-sm font-medium text-ink placeholder:text-muted/70 focus:outline-none md:text-base">
                <kbd class="hidden rounded-md border border-ink/15 px-2 py-1 text-[10px] font-bold text-muted md:inline" title="Tekan / untuk mencari">/</kbd>
                <button type="submit" class="k-btn shrink-0 rounded-xl bg-rose px-4 py-3 text-xs font-black uppercase tracking-wider text-white hover:bg-teal sm:px-6">
                    Cari
                </button>
            </form>
        </section>

        <div class="mx-auto mt-12 max-w-7xl px-4 pb-24 sm:px-6 md:px-10 lg:px-16">
            @if ($error)
                <div class="mt-4">
                    @include('user.katalog.empty-state', [
                        'tone' => 'alert',
                        'title' => 'Data bahan belum dapat dimuat',
                        'message' => $error,
                        'actions' => [
                                ['label' => 'Muat ulang', 'href' => request()->fullUrl(), 'primary' => true],
                                ['label' => 'Ke katalog', 'href' => route('katalog')],
                            ],
                    ])
                </div>
            @elseif (count($ingredients) === 0)
                <div class="mt-4">
                    @include('user.katalog.empty-state', [
                        'title' => 'Belum ada bahan di ontologi',
                        'message' => 'Dataset Aphrodite yang dimuat di Fuseki belum berisi individu ex:Ingredient.',
                        'actions' => [['label' => 'Ke katalog', 'href' => route('katalog'), 'primary' => true]],
                    ])
                </div>
            @else
                {{-- Navigasi A–Z (menempel saat di-scroll) --}}
                <nav class="b-az" aria-label="Lompat ke huruf">
                    <ol class="b-az__list">
                        @foreach ($alphabet as $letter)
                            <li>
                                @if (isset($letters[$letter]))
                                    {{-- Huruf yang punya bahan: tautan; dinonaktifkan sementara bila semua bahannya tersaring pencarian. --}}
                                    @php($hasVisible = collect($letters[$letter])->contains('match', true))
                                    <a href="#huruf-{{ $letter === '#' ? 'lain' : $letter }}" data-letter="{{ $letter }}"
                                       @class(['b-az__link', 'is-off' => ! $hasVisible])
                                       @if (! $hasVisible) aria-disabled="true" tabindex="-1" @endif
                                       aria-label="Huruf {{ $letter }}, {{ count($letters[$letter]) }} bahan">{{ $letter }}</a>
                                @else
                                    <span class="b-az__link is-off" aria-hidden="true">{{ $letter }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>

                <div class="mt-8 flex flex-col gap-2 border-b border-rose/15 pb-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[.28em] text-teal">Direktori A–Z</p>
                        <h2 class="mt-2 font-serif text-3xl font-semibold text-ink md:text-4xl">Semua bahan aktif</h2>
                    </div>
                    <p id="bahan-count" class="text-sm text-muted" role="status" aria-live="polite">
                        @if ($search !== '')
                            <span class="font-bold text-ink tabular-nums" data-count>{{ $visibleCount }}</span> dari {{ count($ingredients) }} bahan cocok dengan
                            <span class="font-bold text-ink">“<span data-term>{{ $search }}</span>”</span>
                        @else
                            Menampilkan <span class="font-bold text-ink tabular-nums" data-count>{{ count($ingredients) }}</span> bahan
                        @endif
                    </p>
                </div>

                <div class="b-sections">
                    @foreach ($letters as $letter => $items)
                        @php($sectionVisible = collect($items)->contains('match', true))
                        <section id="huruf-{{ $letter === '#' ? 'lain' : $letter }}" class="b-section" data-letter="{{ $letter }}" @if (! $sectionVisible) hidden @endif
                                 aria-labelledby="huruf-{{ $letter === '#' ? 'lain' : $letter }}-title">
                            <h3 id="huruf-{{ $letter === '#' ? 'lain' : $letter }}-title" class="b-section__letter">{{ $letter }}</h3>
                            <ul class="b-grid">
                                @foreach ($items as $ingredient)
                                    @include('user.bahan.ingredient-card', ['ingredient' => $ingredient, 'max' => $maxProducts, 'index' => $loop->index])
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </div>

                {{-- Empty state pencarian: dirender server bila tidak ada yang cocok; JS menampilkan/menyembunyikannya. --}}
                <div id="bahan-empty" class="mt-10" @if ($search === '' || $visibleCount > 0) hidden @endif>
                    @include('user.katalog.empty-state', [
                        'title' => 'Bahan tidak ditemukan',
                        'message' => 'Belum ada bahan di ontologi yang namanya memuat kata kunci itu. Coba ejaan lain, nama singkat (mis. “BHA”), atau lihat semua bahan.',
                        'actions' => [['label' => 'Lihat semua bahan', 'href' => route('bahan'), 'primary' => true]],
                    ])
                </div>
            @endif
        </div>
    </main>

    @include('user.katalog.footer')

    <script src="{{ asset('js/bahan.js') }}" defer></script>
</body>
</html>
