{{-- Orang 5 — Eksplorasi Katalog (tahap 1: fondasi visual) --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FDEEF1">
    <title>Katalog Produk — {{ config('app.name', 'Skinova') }}</title>
    <meta name="description" content="Jelajahi katalog skincare Aphrodite yang tersusun dari ontologi: jenis kulit, keluhan, dan bahan aktif.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Palet dan font yang sama dengan landing page Aphrodite.
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"DM Sans"', 'Arial', 'sans-serif'],
                        serif: ['"Playfair Display"', 'Georgia', 'serif'],
                    },
                    colors: {
                        rose: '#E6748E',
                        teal: '#6BB1AD',
                        cream: '#FDEEF1',
                        aqua: '#B8E8EE',
                        blush: '#E5A9A9',
                        ink: '#345452',
                        muted: '#657674',
                        warm: '#FFFEFA',
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="{{ asset('css/katalog.css') }}">
</head>
<body class="min-h-screen bg-warm font-sans text-ink antialiased">

    @include('user.katalog.navbar')

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
            <form action="{{ route('katalog') }}" method="GET" role="search" class="k-search k-rise" style="--d:460ms">
                <label id="katalog-search-label" for="katalog-q" class="sr-only">Cari produk</label>
                <svg class="h-5 w-5 shrink-0 text-teal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5" stroke-linecap="round"/></svg>
                <input id="katalog-q" name="q" type="search" value="{{ $search }}" autocomplete="off"
                       placeholder="Cari nama produk, brand, atau bahan aktif…"
                       class="min-w-0 flex-1 bg-transparent text-sm font-medium text-ink placeholder:text-muted/70 focus:outline-none md:text-base">
                <kbd class="hidden rounded-md border border-ink/15 px-2 py-1 text-[10px] font-bold text-muted md:inline" title="Tekan / untuk mencari">/</kbd>
                <button type="submit" class="k-btn shrink-0 rounded-xl bg-rose px-4 py-3 text-xs font-black uppercase tracking-wider text-white hover:bg-teal sm:px-6">
                    Cari
                </button>
            </form>
        </section>

        {{-- ================= KATALOG: FILTER + LISTING ================= --}}
        <div class="mx-auto mt-14 grid max-w-7xl gap-10 px-4 pb-24 sm:px-6 md:px-10 lg:grid-cols-[17rem_minmax(0,1fr)] lg:px-16">

            {{-- Overlay drawer filter (mobile/tablet) --}}
            <div class="k-overlay" data-filter-close aria-hidden="true"></div>

            {{-- ---------- SIDEBAR FILTER ---------- --}}
            <aside id="katalog-filter" class="k-filter" aria-labelledby="katalog-filter-title">
                <div class="flex items-center justify-between">
                    <h2 id="katalog-filter-title" class="font-serif text-2xl font-semibold text-ink">Saring</h2>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-full text-muted hover:bg-cream lg:hidden" data-filter-close aria-label="Tutup filter">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke-linecap="round"/></svg>
                    </button>
                </div>
                <p class="mt-2 text-xs leading-relaxed text-muted">Opsi filter dimuat dari ontologi pada tahap berikutnya.</p>

                <form class="mt-6" aria-describedby="katalog-filter-note" onsubmit="return false">
                    <fieldset disabled class="space-y-1">
                        @foreach (['Kategori' => 4, 'Jenis kulit' => 5, 'Keluhan kulit' => 6, 'Bahan aktif' => 5, 'Brand' => 4] as $group => $placeholders)
                            <details class="k-group" {{ $loop->index < 2 ? 'open' : '' }}>
                                <summary>{{ $group }}</summary>
                                <div class="k-group__body" aria-hidden="true">
                                    @for ($i = 0; $i < $placeholders; $i++)
                                        <span class="k-skel" style="--w: {{ [62, 48, 74, 55, 68, 44][$i % 6] }}%"></span>
                                    @endfor
                                </div>
                            </details>
                        @endforeach

                        <details class="k-group" open>
                            <summary>Rentang harga</summary>
                            <div class="k-group__body space-y-2">
                                @foreach (['Di bawah Rp50.000', 'Rp50.000 – Rp100.000', 'Rp100.000 – Rp150.000', 'Di atas Rp150.000'] as $range)
                                    <label class="k-option">
                                        <input type="radio" name="harga" class="accent-teal">
                                        <span>{{ $range }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </details>

                        <div class="flex gap-2 pt-5">
                            <button type="submit" class="flex-1 rounded-xl bg-ink px-4 py-3 text-xs font-black uppercase tracking-wider text-white">Terapkan</button>
                            <button type="reset" class="rounded-xl border border-ink/15 px-4 py-3 text-xs font-black uppercase tracking-wider text-muted">Reset</button>
                        </div>
                    </fieldset>
                    <p id="katalog-filter-note" class="mt-3 text-center text-[11px] font-semibold text-muted">Filter belum aktif — segera hadir.</p>
                </form>
            </aside>

            {{-- ---------- LISTING ---------- --}}
            <section aria-labelledby="katalog-list-title" class="min-w-0">
                {{-- Toolbar: judul hasil + sorting --}}
                <div class="flex flex-col gap-5 border-b border-rose/15 pb-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[.28em] text-teal">Koleksi</p>
                        <h2 id="katalog-list-title" class="mt-2 font-serif text-3xl font-semibold text-ink md:text-4xl">Semua produk</h2>
                        <p class="mt-2 text-sm text-muted" id="katalog-result-info">
                            @if ($error)
                                Data produk belum dapat dimuat.
                            @else
                                Menampilkan <span class="font-bold text-ink">{{ count($products) }}</span> produk
                                dari <span class="font-bold text-ink">{{ collect($products)->pluck('brand')->filter()->unique()->count() }}</span> brand.
                                @if ($search !== '')
                                    <span class="block text-xs">Pencarian “{{ $search }}” belum aktif — semua produk ditampilkan.</span>
                                @endif
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" class="k-btn inline-flex items-center gap-2 rounded-xl border border-ink/15 bg-white px-4 py-3 text-xs font-black uppercase tracking-wider text-ink lg:hidden"
                                data-filter-open aria-controls="katalog-filter" aria-expanded="false">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" stroke-linecap="round"/></svg>
                            Filter
                        </button>

                        <label class="k-sort">
                            <span class="text-[11px] font-black uppercase tracking-wider text-muted">Urutkan</span>
                            <select name="sort" disabled class="bg-transparent text-sm font-bold text-ink focus:outline-none" aria-describedby="katalog-filter-note">
                                <option>Relevansi</option>
                                <option>Nama A–Z</option>
                                <option>Nama Z–A</option>
                                <option>Harga terendah</option>
                                <option>Harga tertinggi</option>
                            </select>
                        </label>
                    </div>
                </div>

                {{-- Grid produk dari ontologi --}}
                @if (!$error && count($products) > 0)
                    <div id="katalog-grid" class="k-grid mt-8" aria-live="polite">
                        @foreach ($products as $product)
                            @include('user.katalog.product-card', ['product' => $product])
                        @endforeach
                    </div>
                @else
                    {{-- Empty state --}}
                    <div class="mt-8">
                        @if ($error)
                            @include('user.katalog.empty-state', [
                                'tone' => 'alert',
                                'title' => 'Data produk belum dapat dimuat',
                                'message' => $error,
                                'actions' => [
                                    ['label' => 'Muat ulang', 'href' => request()->fullUrl(), 'primary' => true],
                                    ['label' => 'Ke beranda', 'href' => url('/')],
                                ],
                            ])
                        @else
                            @include('user.katalog.empty-state', [
                                'title' => 'Belum ada produk di ontologi',
                                'message' => 'Dataset Aphrodite yang dimuat di Fuseki belum berisi produk. Sambil menunggu, cari tahu produk yang cocok lewat kuis analisis kulit.',
                                'actions' => [
                                    ['label' => 'Mulai kuis →', 'href' => route('kuis', ['new' => 1]), 'primary' => true],
                                    ['label' => 'Bandingkan produk', 'href' => route('bandingkan')],
                                ],
                            ])
                        @endif
                    </div>
                @endif

                {{-- Pagination --}}
                <nav class="k-pager mt-12" aria-label="Halaman katalog">
                    <span class="k-pager__btn" aria-disabled="true">
                        <span aria-hidden="true">←</span> Sebelumnya
                    </span>
                    <ol class="flex items-center gap-1.5">
                        <li><span class="k-pager__num is-current" aria-current="page">1</span></li>
                    </ol>
                    <span class="k-pager__btn" aria-disabled="true">
                        Berikutnya <span aria-hidden="true">→</span>
                    </span>
                </nav>
            </section>
        </div>
    </main>

    @include('user.katalog.footer')

    <script src="{{ asset('js/katalog.js') }}" defer></script>
</body>
</html>
