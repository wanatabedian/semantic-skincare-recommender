<!doctype html>
<html lang="id">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/logo/logo-aphrodite.png') }}">
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $product['name'] ?? 'Detail Produk' }} — Aphrodite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    {{-- Penanda JS aktif, supaya tab Pagi/Malam hanya dipakai bila JS berjalan (tanpa JS kedua daftar tetap tampil). --}}
    <script>document.documentElement.classList.add('pdp-js');</script>
    <style>
        body{font-family:'DM Sans',sans-serif}.serif{font-family:'Playfair Display',serif}

        /* ===== Detail produk (scoped: .pdp-page / .pdp) ===== */
        body.pdp-page{background:linear-gradient(180deg,#fffdf9 0%,#fffdf9 35%,#d6eff0 100%);background-attachment:fixed;min-height:100vh;color:#2e4747}
        .pdp{--pdp-pink:#e8708f;--pdp-pink-soft:#fdebef;--pdp-pink-line:#f7d9e1;--pdp-step-line:#f6e4e9;--pdp-teal:#6fb3ae;--pdp-teal-soft:#e0f2f1;
            --pdp-text:#2e4747;--pdp-muted:#66807e;--pdp-cream:#fffdf9;--pdp-card:#ffffff;--pdp-similar:#f8f1f3;
            max-width:72rem;margin:0 auto;padding:2.5rem 1rem 4rem;color:var(--pdp-text)}
        @media (min-width:640px){.pdp{padding:3rem 1.5rem 5rem}}
        .pdp .serif{font-family:'Playfair Display',serif}
        .pdp a:focus-visible,.pdp button:focus-visible,.pdp summary:focus-visible{outline:3px solid var(--pdp-teal);outline-offset:3px;border-radius:12px}

        .pdp-eyebrow{margin-bottom:1.5rem;font-size:.75rem;font-weight:800;letter-spacing:.25em;text-transform:uppercase;color:var(--pdp-teal)}
        .pdp-card{background:var(--pdp-card);border:1px solid var(--pdp-step-line);box-shadow:0 10px 30px -18px rgba(46,71,71,.25)}
        .pdp-muted{color:var(--pdp-muted)}

        /* Hero */
        /* Satu kolom di bawah 700px; dua kolom (gambar 240–340px, info sisa) mulai 700px. Kartu sama tinggi lewat align-items:stretch. */
        .pdp-hero{display:grid;gap:28px;grid-template-columns:minmax(0,1fr);align-items:stretch}
        @media (min-width:700px){.pdp-hero{grid-template-columns:minmax(240px,340px) minmax(0,1fr)}}
        .pdp-media{display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:32px;padding:18px;text-align:center;
            background:linear-gradient(135deg,var(--pdp-pink-soft) 0%,var(--pdp-teal-soft) 100%);border:1px solid var(--pdp-pink-line)}
        @media (max-width:699.98px){.pdp-media{width:100%;max-width:340px;margin:0 auto}}
        .pdp-media:not(:has(img)){min-height:18rem}
        .pdp-media__frame{width:100%;overflow:hidden;border-radius:22px;background:#fff}
        .pdp-media__frame img{display:block;width:100%;height:auto;transition:transform .6s ease}
        .pdp-media__frame:hover img{transform:scale(1.04)}
        .pdp-media__icon{display:grid;place-items:center;width:5rem;height:5rem;margin:0 auto;border-radius:24px;background:#fff;color:var(--pdp-pink);font-size:2.25rem;box-shadow:0 8px 20px -12px rgba(232,112,143,.6)}
        .pdp-info{display:flex;flex-direction:column;justify-content:center;border-radius:32px;padding:28px 24px}
        @media (min-width:700px){.pdp-info{padding:36px 40px}}
        .pdp-info .pdp-price + div{margin-top:0}
        .pdp-brand{font-size:.875rem;font-weight:700;color:var(--pdp-teal)}
        .pdp-title{margin-top:.5rem;font-size:1.875rem;font-weight:700;line-height:1.15;color:var(--pdp-pink)}
        @media (min-width:640px){.pdp-title{font-size:2.5rem}}
        .pdp-price{margin:20px 0 16px;font-size:1.6rem;font-weight:800;color:var(--pdp-text)}
        .pdp-badge{display:inline-flex;width:fit-content;max-width:100%;border-radius:999px;background:var(--pdp-teal-soft);color:var(--pdp-text);padding:.4rem .85rem;font-size:.75rem;font-weight:700}
        .pdp-notes{margin-top:1rem;border-radius:18px;background:var(--pdp-pink-soft);padding:1rem;font-size:.875rem}

        /* Empat kartu info kulit */
        .pdp-facts{display:grid;gap:20px;margin-top:1.5rem;grid-template-columns:1fr}
        @media (min-width:640px){.pdp-facts{grid-template-columns:1fr 1fr}}
        .pdp-fact{border-radius:26px;padding:1.25rem 1.4rem}
        .pdp-fact h2{font-size:1.25rem;font-weight:700;color:var(--pdp-text)}
        .pdp-chips{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.85rem}
        .pdp-chip{border-radius:999px;background:var(--pdp-pink-soft);padding:.4rem .85rem;font-size:.875rem;color:var(--pdp-text);transition:transform .2s ease,box-shadow .2s ease}
        .pdp-chip:hover{transform:translateY(-2px);box-shadow:0 6px 14px -10px rgba(232,112,143,.8)}
        .pdp-chip a{color:inherit;text-decoration:underline;text-decoration-color:rgba(111,179,174,.7);text-underline-offset:3px}
        .pdp-chip a:hover{color:var(--pdp-pink)}

        /* Lengkapi rutinitas */
        .pdp-section{margin-top:3rem;scroll-margin-top:6rem}
        .pdp-section__title{font-size:1.75rem;font-weight:700;color:var(--pdp-pink)}
        .pdp-section__desc{margin-top:.35rem;max-width:46rem;font-size:.9rem;color:var(--pdp-muted)}
        .pdp-notice{display:inline-flex;border-radius:999px;background:var(--pdp-teal-soft);padding:.4rem .85rem;font-size:.75rem;font-weight:700}
        .pdp-notice--warn{display:block;border-radius:18px;background:#fff4d6;padding:.75rem 1rem;font-size:.875rem;font-weight:400}
        .pdp-tabs{display:none;gap:.25rem;margin-top:1.25rem;padding:.3rem;border-radius:999px;background:var(--pdp-pink-soft)}
        .pdp-js .pdp-tabs{display:inline-flex}
        .pdp-tab{min-height:44px;padding:.55rem 1.25rem;border:0;border-radius:999px;background:transparent;color:var(--pdp-text);font-size:.9rem;font-weight:700;cursor:pointer;transition:background-color .2s ease,color .2s ease}
        .pdp-tab:hover{color:var(--pdp-pink)}
        .pdp-tab[aria-selected="true"]{background:var(--pdp-teal);color:#fff}
        @media (max-width:520px){.pdp-js .pdp-tabs{display:flex;width:100%}.pdp-tab{flex:1}}
        .pdp-panel{margin-top:1.5rem}
        .pdp-panel__title{display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;font-size:1.1rem;font-weight:800}
        .pdp-js .pdp-panel__title{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}

        .pdp-steps{position:relative;list-style:none;margin:0;padding:0}
        .pdp-steps::before{content:"";position:absolute;left:19px;top:20px;bottom:20px;width:2px;background:var(--pdp-pink-line)}
        .pdp-step{position:relative;display:flex;gap:1rem;align-items:flex-start}
        .pdp-step + .pdp-step{margin-top:.9rem}
        .pdp-step__num{position:relative;z-index:1;display:grid;place-items:center;flex:none;width:40px;height:40px;border-radius:999px;background:#fff;border:1px solid var(--pdp-pink-line);color:var(--pdp-pink);font-weight:800;font-size:.95rem}
        .pdp-step__card{flex:1;min-width:0;border-radius:20px;background:#fff;border:1px solid var(--pdp-step-line);padding:.9rem 1.1rem;transition:transform .2s ease,box-shadow .2s ease}
        .pdp-step__card:hover{transform:translateY(-3px);box-shadow:0 12px 24px -16px rgba(46,71,71,.35)}
        .pdp-step--optional .pdp-step__card{border-style:dashed}
        .pdp-step__cat{font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--pdp-teal)}
        .pdp-step__name{display:block;margin-top:.15rem;font-weight:700;line-height:1.35;color:var(--pdp-text);text-decoration:none}
        .pdp-step__name:hover{color:var(--pdp-pink)}
        .pdp-step__name--main{color:var(--pdp-pink)}
        .pdp-step__meta{margin-top:.25rem;font-size:.78rem;color:var(--pdp-muted)}
        .pdp-tags{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.55rem}
        .pdp-tag{border-radius:999px;padding:.25rem .65rem;font-size:.7rem;font-weight:700;background:var(--pdp-pink-soft);color:var(--pdp-text)}
        .pdp-tag--warn{background:var(--pdp-teal-soft)}

        .pdp-checks{display:grid;gap:.75rem;margin-top:1.25rem}
        .pdp-check{border-radius:20px;padding:1rem 1.1rem;font-size:.85rem}
        .pdp-check--white{background:#fff;border:1px solid var(--pdp-step-line)}
        .pdp-check--teal{background:var(--pdp-teal-soft);border:1px solid rgba(111,179,174,.35)}
        .pdp-level{display:inline-flex;border-radius:999px;background:var(--pdp-pink-soft);color:#b23f5f;padding:.25rem .65rem;font-size:.68rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase}
        .pdp-check details summary{cursor:pointer;font-weight:600}
        .pdp-check a{text-decoration:underline}
        .pdp-check a:hover{color:var(--pdp-pink)}
        .pdp-disclaimer{font-size:.7rem;color:var(--pdp-muted)}

        .pdp-alt{margin-top:1.5rem;border:1px solid var(--pdp-step-line);border-radius:22px;background:var(--pdp-card);padding:18px 26px;transition:box-shadow .2s ease}
        .pdp-alt[open]{box-shadow:0 14px 28px -22px #e8708f}
        .pdp-alt summary{display:flex;align-items:center;justify-content:flex-start;gap:.65rem;padding:12px 0;list-style:none;cursor:pointer;font-size:.9rem;font-weight:800;color:var(--pdp-text)}
        .pdp-alt summary::-webkit-details-marker{display:none}
        .pdp-alt summary:focus-visible{outline:3px solid var(--pdp-teal);outline-offset:4px;border-radius:6px}
        .pdp-alt__chevron{flex:none;color:var(--pdp-pink);font-size:23px;line-height:1;transition:transform .2s ease}
        .pdp-alt[open] .pdp-alt__chevron{transform:rotate(90deg)}
        .pdp-alt__content{animation:pdp-alt-enter .4s ease both}
        .pdp-alt__description{margin-top:.5rem;color:var(--pdp-muted);font-size:14px}
        .pdp-alt__category{color:var(--pdp-teal);font-size:11.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
        .pdp-alt__list{display:grid;gap:10px;margin-top:.5rem;grid-template-columns:1fr}
        @media (min-width:700px){.pdp-alt__list{grid-template-columns:1fr 1fr}}
        .pdp-alt__item{border-radius:16px;background:var(--pdp-cream);border:1px solid var(--pdp-step-line);padding:12px 16px;transition:transform .2s ease,border-color .2s ease}
        .pdp-alt__item:hover{transform:translateY(-2px);border-color:var(--pdp-pink)}
        .pdp-alt__item a{display:block;font-size:14.5px;font-weight:700;color:var(--pdp-text);text-decoration:none}
        .pdp-alt__item a span{margin-top:.2rem;font-size:13px}
        .pdp-alt__item a:hover{color:var(--pdp-pink)}
        @keyframes pdp-alt-enter{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}

        /* Produk serupa */
        .pdp-similar{display:grid;gap:1rem;margin-top:1.25rem;grid-template-columns:1fr}
        @media (min-width:560px){.pdp-similar{grid-template-columns:1fr 1fr}}
        @media (min-width:1024px){.pdp-similar{grid-template-columns:repeat(4,1fr)}}
        .pdp-similar__card{display:flex;flex-direction:column;border-radius:26px;background:var(--pdp-similar);border:1px solid var(--pdp-pink-line);padding:1.25rem;color:var(--pdp-text);text-decoration:none;transition:transform .25s ease,box-shadow .25s ease}
        .pdp-similar__card:hover{transform:translateY(-5px);box-shadow:0 16px 30px -20px rgba(232,112,143,.7)}
        .pdp-similar__brand{font-size:.75rem;font-weight:700;color:var(--pdp-teal)}
        .pdp-similar__name{margin-top:.3rem;font-family:'Playfair Display',serif;font-size:1.05rem;font-weight:700;line-height:1.3}
        .pdp-similar__card:hover .pdp-similar__name{color:var(--pdp-pink)}
        .pdp-similar .pdp-chip{background:#fff;padding:.25rem .65rem;font-size:.75rem}
        .pdp-similar__price{margin-top:auto;padding-top:1rem;font-size:.9rem;font-weight:800}
        .pdp-empty{margin-top:1rem;border-radius:26px;padding:1.5rem;text-align:center}
        .pdp-empty--dashed{border:1px dashed var(--pdp-pink-line);background:#fff}

        /* Animasi */
        @keyframes pdp-rise{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
        .pdp-hero > *{animation:pdp-rise .6s ease both}
        .pdp-hero > *:nth-child(2){animation-delay:.12s}
        .pdp-fact{animation:pdp-rise .5s ease both;animation-delay:calc(.25s + var(--i, 0) * 90ms)}
        .pdp-panel.is-entering .pdp-step{animation:pdp-rise .4s ease both;animation-delay:calc(var(--i, 0) * 60ms)}
        @media (prefers-reduced-motion:reduce){
            .pdp *,.pdp *::before,.pdp *::after{animation:none!important;transition:none!important}
            .pdp-chip:hover,.pdp-step__card:hover,.pdp-similar__card:hover,.pdp-alt__item:hover{transform:none}
            .pdp-media__frame:hover img{transform:none}
        }
    </style>
</head>
<body class="pdp-page">
@include('partials.global-nav')
<nav class="bg-[#FDEEF1] px-5 py-4"><div class="mx-auto flex max-w-6xl justify-between"><a href="/" class="text-xl font-extrabold text-[#E6748E]">Aphrodite</a><a href="{{ route('katalog') }}" class="text-sm font-bold">Jelajahi katalog →</a></div></nav>
<main class="pdp">
@if($error)
<section class="pdp-card pdp-empty"><p class="text-sm pdp-muted">{{ $error }}</p><a href="/" class="mt-5 inline-flex rounded-xl bg-[#e8708f] px-5 py-3 text-sm font-bold text-white">Kembali</a></section>
@else
<div class="pdp-eyebrow">Detail produk</div>

<section class="pdp-hero">
    <div class="pdp-media">
        @if($product['image'])
            <div class="pdp-media__frame"><img src="{{ $product['image'] }}" alt="Foto {{ $product['name'] }}"></div>
        @else
            <div><div class="pdp-media__icon" aria-hidden="true">✧</div><p class="mt-4 text-sm font-semibold pdp-muted">Gambar produk belum tersedia</p></div>
        @endif
    </div>
    <div class="pdp-card pdp-info">
        <p class="pdp-brand">{{ $product['brand'] ?? 'Brand tidak tersedia' }}@if($product['series']) · {{ $product['series'] }}@endif</p>
        <h1 class="serif pdp-title">{{ $product['name'] }}</h1>
        <p class="mt-3 text-sm pdp-muted">{{ $product['category'] ?? 'Kategori tidak tersedia' }}</p>
        <p class="pdp-price">{{ $product['price'] !== null ? 'Rp'.number_format($product['price'],0,',','.') : 'Harga belum tersedia' }}</p>
        <div class="mt-6 flex flex-wrap gap-2"><span class="pdp-badge">{{ $product['verification_status'] ?? 'Status verifikasi belum tersedia' }}</span></div>
        @if($product['verification_notes'])
            <div class="pdp-notes"><p class="font-bold">Catatan verifikasi</p><ul class="mt-2 list-inside list-disc space-y-1">@foreach($product['verification_notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div>
        @endif
    </div>
</section>

<section class="pdp-facts">
@foreach([['Jenis kulit', $product['skin_types']], ['Kelompok usia', $product['age_groups']], ['Keluhan kulit', $product['concerns']], ['Kandungan', $product['ingredients']]] as [$heading, $values])
    <div class="pdp-card pdp-fact" style="--i: {{ $loop->index }}">
        <h2 class="serif">{{ $heading }}</h2>
        @if ($values)
            <ul class="pdp-chips">
                @foreach ($values as $value)
                    @if (is_array($value))
                        @if ($heading === 'Kandungan' && ! empty($value['url']))
                            <li class="pdp-chip"><a href="{{ $value['url'] }}">{{ $value['name'] }}</a></li>
                        @else
                            <li class="pdp-chip">{{ $value['name'] }}</li>
                        @endif
                    @else
                        <li class="pdp-chip">{{ $value }}</li>
                    @endif
                @endforeach
            </ul>
        @else
            <p class="mt-3 text-sm pdp-muted">Belum tercantum.</p>
        @endif
    </div>
@endforeach
</section>

{{-- Complete Your Segment v2 (Orang 4). Tombol "Lengkapi rutinitas" di halaman rekomendasi menuju #lengkapi-rutinitas. --}}
<section id="lengkapi-rutinitas" class="pdp-section" aria-labelledby="judul-rutinitas">
    <h2 id="judul-rutinitas" class="serif pdp-section__title">Lengkapi rutinitasmu</h2>
    <p class="pdp-section__desc">Satu produk untuk setiap langkah, diutamakan dari seri {{ $product['series'] ?? 'yang sama' }}. Langkah yang tidak ada di seri ini dilengkapi dari seri lain dengan jenis kulit dan keluhan yang sama.</p>
@if($routineError ?? null)
    <div class="pdp-card pdp-empty text-sm pdp-muted">{{ $routineError }}</div>
@elseif($routine)
    @if($routine['age'] || $routine['main_hidden'] || ($routine['teen_lock'] && $routine['hidden_series_count'] > 0))
        <div class="mt-4 space-y-2">
            @if($routine['age'])<p class="pdp-notice">Disaring untuk usia: {{ $routine['age']['label'] }}</p>@endif
            @if($routine['teen_lock'] && ($routine['main_hidden'] || $routine['hidden_series_count'] > 0))<p class="pdp-notice--warn">Produk ber-retinol disembunyikan untuk usia remaja.@if($routine['main_hidden']) Produk ini tidak dimasukkan ke rutinitas.@endif</p>@endif
        </div>
    @endif
    @php
        $ageQuery = $routine['age'] ? ['age' => $routine['age']['value']] : [];
        $sourceText = ['main' => 'Produk ini', 'series' => 'Dari seri ini', 'other' => 'Dari seri lain'];
        $times = ['pagi' => ['Pagi', '☀'], 'malam' => ['Malam', '☾']];
    @endphp

    <div class="pdp-tabs" role="tablist" aria-label="Waktu rutinitas">
        @foreach($times as $time => [$timeLabel, $icon])
            <button type="button" role="tab" class="pdp-tab" id="pdp-tab-{{ $time }}" data-tab="{{ $time }}" aria-controls="pdp-panel-{{ $time }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}"><span aria-hidden="true">{{ $icon }}</span> Rutinitas {{ $timeLabel }}</button>
        @endforeach
    </div>

    @foreach($times as $time => [$timeLabel, $icon])
    <div class="pdp-panel is-entering" id="pdp-panel-{{ $time }}" role="tabpanel" aria-labelledby="pdp-tab-{{ $time }}" data-panel="{{ $time }}">
        <h3 class="pdp-panel__title"><span aria-hidden="true" class="text-[#e8708f]">{{ $icon }}</span> Rutinitas {{ $timeLabel }}</h3>
        @php
            $stepNumber = 0;
            $timeCheck = $routine['conflicts'][$time] ?? null;
            $involvedIds = $timeCheck['involved_ids'] ?? [];
        @endphp
        <ol class="pdp-steps">
        @foreach($routine[$time] as $step)
            <li class="pdp-step {{ $step['optional'] ? 'pdp-step--optional' : '' }}" style="--i: {{ $loop->index }}">
                <span class="pdp-step__num">{{ $step['optional'] ? '+' : ++$stepNumber }}</span>
                <div class="pdp-step__card">
                    <p class="pdp-step__cat">{{ $step['label'] }}@if($step['optional']) · Opsional @endif</p>
                    @if($step['product'])
                        @php $item = $step['product']; @endphp
                        <a href="{{ route('produk.detail', ['id' => $item['id']] + $ageQuery) }}" class="pdp-step__name {{ $item['is_main'] ? 'pdp-step__name--main' : '' }}">{{ $item['name'] }}</a>
                        <p class="pdp-step__meta">{{ $item['brand'] ?? 'Brand tidak tersedia' }} · {{ $item['price'] !== null ? 'Rp'.number_format($item['price'],0,',','.') : 'Harga belum tersedia' }}</p>
                        <div class="pdp-tags">
                            <span class="pdp-tag">{{ $sourceText[$item['source']] }}</span>
                            @if($item['has_retinol'] && $step['times'] === ['malam'])<span class="pdp-tag pdp-tag--warn">Mengandung Retinol · dipakai malam</span>@endif
                            @if(in_array($item['id'], $involvedIds, true))<span class="pdp-tag pdp-tag--warn">Perhatikan kombinasi bahan</span>@endif
                        </div>
                    @else
                        <p class="mt-1 text-sm pdp-muted">Belum ada produk yang cocok untuk langkah ini.</p>
                    @endif
                </div>
            </li>
        @endforeach
        </ol>

        {{-- Pemeriksaan kombinasi bahan antarproduk utama di waktu ini (IngredientConflictDetector). --}}
        <div class="pdp-checks" data-conflict-check="{{ $time }}">
        @if($routine['conflict_error'] ?? null)
            <p class="pdp-check pdp-check--white pdp-muted">{{ $routine['conflict_error'] }}</p>
        @elseif($timeCheck)
            @foreach($timeCheck['groups'] as $group)
                <div class="pdp-check pdp-check--white" role="note">
                    <p class="flex flex-wrap items-center gap-2"><span class="pdp-level">{{ $group['level_label'] }}</span><span class="font-bold">{{ implode(' + ', $group['ingredients']) }}</span></p>
                    <p class="mt-2">{{ $group['reason'] }}</p>
                    <ul class="mt-2 space-y-1 text-xs pdp-muted">
                        @foreach($group['products'] as $involved)
                            <li><span class="font-semibold text-[#2e4747]">{{ $involved['name'] }}</span> — {{ implode(', ', $involved['ingredients']) }}</li>
                        @endforeach
                    </ul>
                    <details class="mt-2 text-xs pdp-muted"><summary>Sumber referensi</summary>
                        <ul class="mt-1 list-inside list-disc space-y-1">
                            @foreach($group['sources'] as $source)
                                <li><a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer">{{ $source['title'] }}</a></li>
                            @endforeach
                        </ul>
                    </details>
                </div>
            @endforeach
            @if($timeCheck['groups'] === [] && $timeCheck['checked_count'] > 0)
                <p class="pdp-check pdp-check--teal">Tidak ditemukan kombinasi bahan yang perlu diperhatikan di antara {{ $timeCheck['checked_count'] }} produk yang data bahannya tersedia.</p>
            @endif
            @if($timeCheck['unchecked'] !== [])
                <p class="pdp-check pdp-check--white pdp-muted"><span class="font-semibold">Belum dapat diperiksa</span> karena data bahan belum tersedia: {{ implode(', ', array_column($timeCheck['unchecked'], 'name')) }}.</p>
            @endif
            @if($timeCheck['groups'] !== [] || $timeCheck['checked_count'] > 0)
                <p class="pdp-disclaimer">Pemeriksaan hanya mencakup bahan aktif utama yang tercatat. Informasi umum, bukan saran medis; ikuti petunjuk pada kemasan.</p>
            @endif
        @endif
        </div>
    </div>
    @endforeach

    @php $withAlternatives = array_values(array_filter($routine['steps'], fn ($s) => $s['alternatives'] !== [])); @endphp
    @if($withAlternatives)
        <details class="pdp-card pdp-alt">
            <summary><span class="pdp-alt__chevron" aria-hidden="true">▸</span><span>Alternatif dari seri ini ({{ array_sum(array_map(fn ($s) => count($s['alternatives']), $withAlternatives)) }} produk)</span></summary>
            <div class="pdp-alt__content">
                <p class="pdp-alt__description">Produk lain dari seri yang sama untuk langkah yang sudah terisi. Pilih salah satu sebagai pengganti, bukan langkah tambahan.</p>
                @foreach($withAlternatives as $step)
                    <div class="mt-4"><p class="pdp-alt__category">{{ $step['label'] }}</p>
                        <ul class="pdp-alt__list">
                            @foreach($step['alternatives'] as $alt)
                                <li class="pdp-alt__item"><a href="{{ route('produk.detail', ['id' => $alt['id']] + $ageQuery) }}">{{ $alt['name'] }}<span class="block text-xs font-normal pdp-muted">{{ $alt['price'] !== null ? 'Rp'.number_format($alt['price'],0,',','.') : 'Harga belum tersedia' }}</span></a></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </details>
    @endif
@endif
</section>

<section class="pdp-section" aria-labelledby="produk-serupa">
    <h2 id="produk-serupa" class="serif pdp-section__title">Produk serupa</h2>
    <p class="pdp-section__desc">Dari brand lain, untuk jenis kulit dan keluhan yang sama.</p>
@if($similarError ?? null)
    <div class="pdp-card pdp-empty text-sm pdp-muted">{{ $similarError }}</div>
@elseif(empty($similarProducts))
    <div class="pdp-empty pdp-empty--dashed"><p class="text-sm font-semibold">Belum ada produk serupa</p><p class="mt-1 text-sm pdp-muted">Tidak ditemukan produk dari brand lain dengan jenis kulit dan keluhan yang sama.</p></div>
@else
    <div class="pdp-similar">
    @foreach($similarProducts as $similar)
        <a href="{{ route('produk.detail', $similar['id']) }}" class="pdp-similar__card">
            <p class="pdp-similar__brand">{{ $similar['brand'] ?? 'Brand tidak tersedia' }}</p>
            <h3 class="pdp-similar__name">{{ $similar['name'] }}</h3>
            @if($similar['category'])<p class="mt-1 text-xs pdp-muted">{{ $similar['category'] }}</p>@endif
            @if($similar['shared_concerns'])<ul class="pdp-chips">@foreach($similar['shared_concerns'] as $concern)<li class="pdp-chip">{{ $concern }}</li>@endforeach</ul>@endif
            <p class="pdp-similar__price">{{ $similar['price'] !== null ? 'Rp'.number_format($similar['price'],0,',','.') : 'Harga belum tersedia' }}</p>
        </a>
    @endforeach
    </div>
@endif
</section>
@endif
</main>
<script>
// Tab Pagi/Malam: hanya menampilkan/menyembunyikan dua daftar yang sudah dirender server.
(() => {
  const tabs = Array.from(document.querySelectorAll('.pdp-tab'));
  const panels = Array.from(document.querySelectorAll('.pdp-panel'));
  if (!tabs.length || !panels.length) return;

  const show = (name, focus = false) => {
    tabs.forEach(tab => {
      const active = tab.dataset.tab === name;
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
      tab.tabIndex = active ? 0 : -1;
      if (active && focus) tab.focus();
    });
    panels.forEach(panel => {
      const active = panel.dataset.panel === name;
      panel.hidden = !active;
      if (active) {
        panel.classList.remove('is-entering');
        void panel.offsetWidth; // mulai ulang animasi stagger
        panel.classList.add('is-entering');
      }
    });
  };

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => show(tab.dataset.tab));
    tab.addEventListener('keydown', event => {
      if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
      event.preventDefault();
      const next = tabs[(index + (event.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length];
      show(next.dataset.tab, true);
    });
  });

  show(tabs[0].dataset.tab);
})();
</script>
<script>
// Simpan ID produk ke localStorage['aphrodite-routine'] (array ID) sesuai docs/KONTRAK-orang3.md.
(() => {
  const KEY = 'aphrodite-routine';
  const read = () => { try { const v = JSON.parse(localStorage.getItem(KEY) || '[]'); return Array.isArray(v) ? v : []; } catch (e) { return []; } };
  const write = (ids) => { try { localStorage.setItem(KEY, JSON.stringify(ids)); } catch (e) {} };
  const refresh = () => {
    const saved = read();
    document.querySelectorAll('[data-routine-add]').forEach(btn => {
      const inRoutine = saved.includes(btn.dataset.routineAdd);
      btn.textContent = inRoutine ? '✓ Di rutinitas' : '+ Rutinitas';
      btn.setAttribute('aria-pressed', inRoutine ? 'true' : 'false');
    });
  };
  const add = (ids) => { const saved = read(); ids.forEach(id => { if (id && !saved.includes(id)) saved.push(id); }); write(saved); refresh(); };
  document.querySelectorAll('[data-routine-add]').forEach(btn => btn.addEventListener('click', () => {
    const id = btn.dataset.routineAdd;
    const saved = read();
    write(saved.includes(id) ? saved.filter(x => x !== id) : [...saved, id]);
    refresh();
  }));
  document.querySelectorAll('[data-routine-save-all]').forEach(btn => btn.addEventListener('click', () => add(btn.dataset.routineSaveAll.split(','))));
  refresh();
})();
</script>
</body></html>
