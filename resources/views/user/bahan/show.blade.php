{{--
    Orang 5 — Detail bahan aktif (/bahan/{id}). Data: IngredientController::show().
    Hanya menampilkan data yang ada di ontologi: nama (rdfs:label), ID, dan produk yang mengandungnya
    (ex:hasIngredient). Bagian fungsi/peringatan/konflik tidak dibuat karena datanya belum ada.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('user.katalog.head', [
        'title' => $ingredient ? $ingredient['name'].' — Bahan Aktif' : 'Bahan Aktif',
        'description' => $ingredient
            ? 'Produk skincare yang mengandung '.$ingredient['name'].' menurut ontologi Aphrodite.'
            : 'Detail bahan aktif dari ontologi Aphrodite.',
        'styles' => ['katalog', 'bahan'],
    ])
</head>
<body class="min-h-screen bg-warm font-sans text-ink antialiased">

    @include('user.katalog.navbar', ['active' => 'bahan'])

    <main id="bahan-detail">
        @if ($error)
            <div class="mx-auto max-w-3xl px-4 pt-32 pb-24 sm:px-6">
                @include('user.katalog.empty-state', [
                    'tone' => 'alert',
                    'title' => 'Detail bahan belum dapat dimuat',
                    'message' => $error,
                    'actions' => [
                        ['label' => 'Muat ulang', 'href' => request()->fullUrl(), 'primary' => true],
                        ['label' => 'Kembali ke direktori', 'href' => route('bahan')],
                    ],
                ])
            </div>
        @else
            @php($count = count($products))

            {{-- ================= HERO ================= --}}
            <header class="k-hero b-hero b-hero--detail relative overflow-hidden bg-cream pt-28 pb-20 md:pt-36 md:pb-24">
                <span class="b-hero__monogram" aria-hidden="true">{{ mb_substr($ingredient['name'], 0, 1) }}</span>

                <div class="relative mx-auto max-w-7xl px-4 sm:px-6 md:px-10 lg:px-16">
                    <nav class="k-rise text-xs font-bold text-muted" style="--d:0ms" aria-label="Breadcrumb">
                        <ol class="flex flex-wrap items-center gap-2">
                            <li><a href="{{ route('bahan') }}" class="b-crumb">Kamus Bahan</a></li>
                            <li aria-hidden="true" class="text-rose/60">/</li>
                            <li aria-current="page" class="text-ink">{{ $ingredient['name'] }}</li>
                        </ol>
                    </nav>

                    <p class="k-rise mt-8 flex items-center gap-3 text-[11px] font-black uppercase tracking-[.32em] text-teal" style="--d:80ms">
                        <span class="h-px w-8 bg-teal/60" aria-hidden="true"></span>
                        Bahan aktif
                    </p>
                    <h1 class="k-rise mt-5 max-w-4xl font-serif text-[2.4rem] font-semibold leading-[1.05] text-rose sm:text-6xl lg:text-7xl" style="--d:160ms">
                        {{ $ingredient['name'] }}
                    </h1>
                    <p class="k-rise mt-5 inline-flex items-center gap-2 rounded-full border border-teal/40 bg-warm/70 px-3 py-1.5 text-xs font-bold text-ink" style="--d:240ms">
                        <span class="text-muted">ID ontologi</span>
                        <code class="font-mono text-[11px]">ex:{{ $ingredient['id'] }}</code>
                    </p>

                    <dl class="k-rise mt-10 flex flex-wrap items-end gap-x-10 gap-y-6" style="--d:320ms">
                        <div>
                            <dt class="text-[11px] font-black uppercase tracking-[.22em] text-muted">Produk mengandung</dt>
                            <dd class="mt-1 font-serif text-5xl font-semibold text-ink tabular-nums">{{ $count }}</dd>
                        </div>
                        @if ($count > 0)
                            <div class="hidden h-12 w-px bg-rose/20 sm:block" aria-hidden="true"></div>
                            <div>
                                <dt class="text-[11px] font-black uppercase tracking-[.22em] text-muted">Dari brand</dt>
                                <dd class="mt-2 flex flex-wrap gap-2 text-xs font-bold">
                                    @foreach ($brands as $brand)
                                        <span class="rounded-full border border-teal/40 bg-warm/70 px-3 py-1.5 text-ink">{{ $brand }}</span>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </header>

            <div class="mx-auto max-w-7xl px-4 pb-24 sm:px-6 md:px-10 lg:px-16">
                {{-- Keterangan jujur soal cakupan data ontologi --}}
                <aside class="b-note k-rise mt-10" style="--d:420ms" aria-label="Keterangan data">
                    <svg class="b-note__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01" stroke-linecap="round"/></svg>
                    <p>
                        Ontologi Aphrodite saat ini mencatat <strong>nama bahan</strong> dan <strong>produk yang mengandungnya</strong>.
                        Informasi fungsi, target keluhan, peringatan, dan interaksi dengan bahan lain belum tersedia di data,
                        sehingga tidak ditampilkan di sini.
                    </p>
                </aside>

                {{-- ================= PRODUK YANG MENGANDUNG ================= --}}
                <section class="mt-14" aria-labelledby="bahan-produk-title">
                    <div class="flex flex-col gap-2 border-b border-rose/15 pb-6 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[.28em] text-teal">Relasi ontologi · ex:hasIngredient</p>
                            <h2 id="bahan-produk-title" class="mt-2 font-serif text-3xl font-semibold text-ink md:text-4xl">Produk yang mengandung {{ $ingredient['name'] }}</h2>
                        </div>
                        @if ($count > 0)
                            <p class="text-sm text-muted">
                                <span class="font-bold text-ink tabular-nums">{{ $count }}</span> produk dari
                                <span class="font-bold text-ink tabular-nums">{{ count($brands) }}</span> brand,
                                <span class="font-bold text-ink tabular-nums">{{ $categoryCount }}</span> kategori
                            </p>
                        @endif
                    </div>

                    @if ($count > 0)
                        <div class="k-grid b-products mt-8">
                            @foreach ($products as $product)
                                @include('user.katalog.product-card', ['product' => $product, 'index' => $loop->index])
                            @endforeach
                        </div>
                    @else
                        <div class="mt-8">
                            @include('user.katalog.empty-state', [
                                'title' => 'Belum ada produk tercatat',
                                'message' => 'Ontologi belum mencatat produk yang mengandung bahan ini.',
                                'actions' => [['label' => 'Lihat bahan lain', 'href' => route('bahan'), 'primary' => true]],
                            ])
                        </div>
                    @endif
                </section>

                <div class="mt-16 flex flex-wrap items-center justify-between gap-4 border-t border-rose/15 pt-8">
                    <a href="{{ route('bahan') }}" class="b-back">
                        <span aria-hidden="true">←</span> Kembali ke Kamus Bahan
                    </a>
                    <a href="{{ route('katalog') }}" class="k-btn rounded-xl border border-teal/40 px-5 py-3 text-xs font-black uppercase tracking-wider text-ink hover:bg-aqua/30">
                        Jelajahi katalog
                    </a>
                </div>
            </div>
        @endif
    </main>

    @include('user.katalog.footer')
</body>
</html>
