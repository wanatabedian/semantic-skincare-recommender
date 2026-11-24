<!doctype html>
<html lang="id">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/logo/logo-aphrodite.png') }}">
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $product['name'] ?? 'Detail Produk' }} — Aphrodite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'DM Sans',sans-serif}.serif{font-family:'Playfair Display',serif}</style>
</head>
<body class="min-h-screen bg-[#FFFEFA] text-[#345452]">
@include('partials.global-nav')
<nav class="bg-[#FDEEF1] px-5 py-4"><div class="mx-auto flex max-w-6xl justify-between"><a href="/" class="text-xl font-extrabold text-[#E6748E]">Aphrodite</a><a href="{{ route('katalog') }}" class="text-sm font-bold">Jelajahi katalog →</a></div></nav>
<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
@if($error)<section class="rounded-3xl border border-[#E6748E]/20 bg-white p-8 text-center"><p class="text-sm text-[#657674]">{{ $error }}</p><a href="/" class="mt-5 inline-flex rounded-xl bg-[#E6748E] px-5 py-3 text-sm font-bold text-white">Kembali</a></section>
@else
<div class="mb-6 text-xs font-extrabold uppercase tracking-[.25em] text-[#6BB1AD]">Detail produk</div>
<section class="grid gap-6 lg:grid-cols-[.85fr_1.15fr]">
<div class="grid min-h-64 place-items-center overflow-hidden rounded-3xl border border-[#E6748E]/10 bg-gradient-to-br from-[#FDEEF1] to-[#B8E8EE]/50 p-8 text-center sm:min-h-80">@if($product['image'])<img src="{{ $product['image'] }}" alt="Foto {{ $product['name'] }}" class="h-full max-h-[28rem] w-full object-contain">@else<div><div class="mx-auto grid h-20 w-20 place-items-center rounded-3xl bg-white/80 text-4xl text-[#E6748E]">✧</div><p class="mt-4 text-sm font-semibold text-[#657674]">Gambar produk belum tersedia</p></div>@endif</div>
<div class="rounded-3xl border border-[#E6748E]/10 bg-white p-5 shadow-sm sm:p-8"><p class="text-sm font-bold text-[#6BB1AD]">{{ $product['brand'] ?? 'Brand tidak tersedia' }}@if($product['series']) · {{ $product['series'] }}@endif</p><h1 class="serif mt-2 text-3xl font-bold leading-tight text-[#E6748E] sm:text-4xl">{{ $product['name'] }}</h1><p class="mt-3 text-sm text-[#657674]">{{ $product['category'] ?? 'Kategori tidak tersedia' }}</p><p class="mt-6 text-2xl font-extrabold">{{ $product['price'] !== null ? 'Rp'.number_format($product['price'],0,',','.') : 'Harga belum tersedia' }}</p>
<div class="mt-6 flex flex-wrap gap-2"><span class="rounded-full bg-[#B8E8EE]/40 px-3 py-1.5 text-xs font-bold">{{ $product['verification_status'] ?? 'Status verifikasi belum tersedia' }}</span></div>
@if($product['verification_notes'])<div class="mt-4 rounded-2xl bg-[#FDEEF1]/70 p-4 text-sm"><p class="font-bold">Catatan verifikasi</p><ul class="mt-2 list-inside list-disc space-y-1">@foreach($product['verification_notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div>@endif
</div></section>
<section class="mt-6 grid gap-4 sm:grid-cols-2">
@foreach([['Jenis kulit', $product['skin_types']], ['Kelompok usia', $product['age_groups']], ['Keluhan kulit', $product['concerns']], ['Kandungan', $product['ingredients']]] as [$heading, $values])
<div class="rounded-3xl border border-[#E6748E]/10 bg-white p-5">
    <h2 class="serif text-xl font-bold text-[#345452]">{{ $heading }}</h2>
    @if ($values)
        <ul class="mt-3 flex flex-wrap gap-2">
            @foreach ($values as $value)
                <li class="rounded-full bg-[#FDEEF1] px-3 py-1.5 text-sm">
                    @if (is_array($value))
                        @if ($heading === 'Kandungan' && ! empty($value['url']))
                            <a href="{{ $value['url'] }}" class="underline decoration-[#6BB1AD]/60 underline-offset-2 hover:text-[#E6748E]">{{ $value['name'] }}</a>
                        @else
                            {{ $value['name'] }}
                        @endif
                    @else
                        {{ $value }}
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-3 text-sm text-[#657674]">Belum tercantum.</p>
    @endif
</div>
@endforeach
</section>
{{-- Complete Your Segment v2 (Orang 4). Tombol "Lengkapi rutinitas" di halaman rekomendasi menuju #lengkapi-rutinitas. --}}
<section id="lengkapi-rutinitas" class="mt-10 scroll-mt-6" aria-labelledby="judul-rutinitas">
<div class="flex flex-wrap items-end justify-between gap-3">
<div><h2 id="judul-rutinitas" class="serif text-2xl font-bold text-[#345452]">Lengkapi rutinitasmu</h2>
<p class="mt-1 text-sm text-[#657674]">Satu produk untuk setiap langkah, diutamakan dari seri {{ $product['series'] ?? 'yang sama' }}. Langkah yang tidak ada di seri ini dilengkapi dari seri lain dengan jenis kulit dan keluhan yang sama.</p></div>
</div>
@if($routineError ?? null)
<div class="mt-4 rounded-3xl border border-[#E6748E]/10 bg-white p-6 text-center text-sm text-[#657674]">{{ $routineError }}</div>
@elseif($routine)
@if($routine['age'] || $routine['main_hidden'] || ($routine['teen_lock'] && $routine['hidden_series_count'] > 0))
<div class="mt-4 space-y-2">
@if($routine['age'])<p class="inline-flex rounded-full bg-[#B8E8EE]/40 px-3 py-1.5 text-xs font-bold">Disaring untuk usia: {{ $routine['age']['label'] }}</p>@endif
@if($routine['teen_lock'] && ($routine['main_hidden'] || $routine['hidden_series_count'] > 0))<p class="rounded-2xl bg-[#FFF4D6] px-4 py-3 text-sm">Produk ber-retinol disembunyikan untuk usia remaja.@if($routine['main_hidden']) Produk ini tidak dimasukkan ke rutinitas.@endif</p>@endif
</div>
@endif
@php
    $ageQuery = $routine['age'] ? ['age' => $routine['age']['value']] : [];
    $sourceText = ['main' => 'Produk ini', 'series' => 'Dari seri ini', 'other' => 'Dari seri lain'];
@endphp
<div class="mt-5 grid gap-5 lg:grid-cols-2">
@foreach(['pagi' => ['Pagi', '☀'], 'malam' => ['Malam', '☾']] as $time => [$timeLabel, $icon])
<div class="rounded-3xl border border-[#E6748E]/10 bg-white p-5 sm:p-6">
<h3 class="flex items-center gap-2 text-lg font-extrabold"><span aria-hidden="true" class="text-[#E6748E]">{{ $icon }}</span> Rutinitas {{ $timeLabel }}</h3>
<ol class="mt-4 space-y-3">
@php
    $stepNumber = 0;
    $timeCheck = $routine['conflicts'][$time] ?? null;
    $involvedIds = $timeCheck['involved_ids'] ?? [];
@endphp
@foreach($routine[$time] as $step)
<li class="flex gap-3 rounded-2xl border border-[#E6748E]/10 p-3 {{ $step['optional'] ? 'border-dashed' : '' }}">
<span class="grid h-8 w-8 shrink-0 place-items-center rounded-full {{ $step['optional'] ? 'bg-white text-[#6BB1AD] ring-1 ring-[#6BB1AD]/40' : 'bg-[#FDEEF1] text-[#E6748E]' }} text-sm font-extrabold">{{ $step['optional'] ? '+' : ++$stepNumber }}</span>
<div class="min-w-0 flex-1">
<p class="text-xs font-bold uppercase tracking-wide text-[#6BB1AD]">{{ $step['label'] }}@if($step['optional']) · Opsional @endif</p>
@if($step['product'])
@php $item = $step['product']; @endphp
<a href="{{ route('produk.detail', ['id' => $item['id']] + $ageQuery) }}" class="mt-0.5 block font-bold leading-snug hover:text-[#E6748E] {{ $item['is_main'] ? 'text-[#E6748E]' : '' }}">{{ $item['name'] }}</a>
<p class="mt-1 text-xs text-[#657674]">{{ $item['brand'] ?? 'Brand tidak tersedia' }} · {{ $item['price'] !== null ? 'Rp'.number_format($item['price'],0,',','.') : 'Harga belum tersedia' }}</p>
<div class="mt-2 flex flex-wrap items-center gap-1.5">
<span class="rounded-full bg-[#FDEEF1] px-2.5 py-1 text-[11px] font-bold">{{ $sourceText[$item['source']] }}</span>
@if($item['has_retinol'] && $step['times'] === ['malam'])<span class="rounded-full bg-[#345452]/10 px-2.5 py-1 text-[11px] font-bold">Mengandung Retinol · dipakai malam</span>@endif
@if(in_array($item['id'], $involvedIds, true))<span class="rounded-full bg-[#FFF4D6] px-2.5 py-1 text-[11px] font-bold text-[#8A5A00]">Perhatikan kombinasi bahan</span>@endif
</div>
@else
<p class="mt-1 text-sm text-[#657674]">Belum ada produk yang cocok untuk langkah ini.</p>
@endif
</div>
</li>
@endforeach
</ol>
{{-- Pemeriksaan kombinasi bahan antarproduk utama di waktu ini (IngredientConflictDetector). --}}
<div class="mt-4 space-y-3" data-conflict-check="{{ $time }}">
@if($routine['conflict_error'] ?? null)
<p class="rounded-2xl bg-[#F4F6F6] px-4 py-3 text-xs text-[#657674]">{{ $routine['conflict_error'] }}</p>
@elseif($timeCheck)
@foreach($timeCheck['groups'] as $group)
<div class="rounded-2xl border border-[#E8B54A]/40 bg-[#FFF8E6] p-4 text-sm" role="note">
<p class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-[#E8B54A]/25 px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wide text-[#8A5A00]">{{ $group['level_label'] }}</span><span class="font-bold">{{ implode(' + ', $group['ingredients']) }}</span></p>
<p class="mt-2 text-[#345452]">{{ $group['reason'] }}</p>
<ul class="mt-2 space-y-1 text-xs text-[#657674]">
@foreach($group['products'] as $involved)
<li><span class="font-semibold text-[#345452]">{{ $involved['name'] }}</span> — {{ implode(', ', $involved['ingredients']) }}</li>
@endforeach
</ul>
<details class="mt-2 text-xs text-[#657674]"><summary class="cursor-pointer font-semibold">Sumber referensi</summary>
<ul class="mt-1 list-inside list-disc space-y-1">
@foreach($group['sources'] as $source)
<li><a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer" class="underline hover:text-[#E6748E]">{{ $source['title'] }}</a></li>
@endforeach
</ul>
</details>
</div>
@endforeach
@if($timeCheck['groups'] === [] && $timeCheck['checked_count'] > 0)
<p class="rounded-2xl bg-[#F0F8F7] px-4 py-3 text-xs text-[#345452]">Tidak ditemukan kombinasi bahan yang perlu diperhatikan di antara {{ $timeCheck['checked_count'] }} produk yang data bahannya tersedia.</p>
@endif
@if($timeCheck['unchecked'] !== [])
<p class="rounded-2xl bg-[#F4F6F6] px-4 py-3 text-xs text-[#657674]"><span class="font-semibold">Belum dapat diperiksa</span> karena data bahan belum tersedia: {{ implode(', ', array_column($timeCheck['unchecked'], 'name')) }}.</p>
@endif
@if($timeCheck['groups'] !== [] || $timeCheck['checked_count'] > 0)
<p class="text-[11px] text-[#657674]">Pemeriksaan hanya mencakup bahan aktif utama yang tercatat. Informasi umum, bukan saran medis; ikuti petunjuk pada kemasan.</p>
@endif
@endif
</div>
</div>
@endforeach
</div>
@php $withAlternatives = array_values(array_filter($routine['steps'], fn ($s) => $s['alternatives'] !== [])); @endphp
@if($withAlternatives)
<details class="mt-5 rounded-3xl border border-[#E6748E]/10 bg-white p-5 sm:p-6">
<summary class="cursor-pointer text-sm font-extrabold">Alternatif dari seri ini ({{ array_sum(array_map(fn ($s) => count($s['alternatives']), $withAlternatives)) }} produk)</summary>
<p class="mt-2 text-sm text-[#657674]">Produk lain dari seri yang sama untuk langkah yang sudah terisi. Pilih salah satu sebagai pengganti, bukan langkah tambahan.</p>
@foreach($withAlternatives as $step)
<div class="mt-4"><p class="text-xs font-bold uppercase tracking-wide text-[#6BB1AD]">{{ $step['label'] }}</p>
<ul class="mt-2 grid gap-2 sm:grid-cols-2">
@foreach($step['alternatives'] as $alt)
<li class="flex items-center gap-2 rounded-2xl bg-[#FFFEFA] p-3 ring-1 ring-[#E6748E]/10"><a href="{{ route('produk.detail', ['id' => $alt['id']] + $ageQuery) }}" class="min-w-0 flex-1 text-sm font-semibold hover:text-[#E6748E]">{{ $alt['name'] }}<span class="block text-xs font-normal text-[#657674]">{{ $alt['price'] !== null ? 'Rp'.number_format($alt['price'],0,',','.') : 'Harga belum tersedia' }}</span></a></li>
@endforeach
</ul></div>
@endforeach
</details>
@endif
@endif
</section>
<section class="mt-10" aria-labelledby="produk-serupa">
<h2 id="produk-serupa" class="serif text-2xl font-bold text-[#345452]">Produk serupa</h2>
<p class="mt-1 text-sm text-[#657674]">Dari brand lain, untuk jenis kulit dan keluhan yang sama.</p>
@if($similarError ?? null)
<div class="mt-4 rounded-3xl border border-[#E6748E]/10 bg-white p-6 text-center text-sm text-[#657674]">{{ $similarError }}</div>
@elseif(empty($similarProducts))
<div class="mt-4 rounded-3xl border border-dashed border-[#E6748E]/30 bg-white p-6 text-center"><p class="text-sm font-semibold">Belum ada produk serupa</p><p class="mt-1 text-sm text-[#657674]">Tidak ditemukan produk dari brand lain dengan jenis kulit dan keluhan yang sama.</p></div>
@else
<div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
@foreach($similarProducts as $similar)
<a href="{{ route('produk.detail', $similar['id']) }}" class="group flex flex-col rounded-3xl border border-[#E6748E]/10 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-[#E6748E]/40 hover:shadow-md">
<p class="text-xs font-bold text-[#6BB1AD]">{{ $similar['brand'] ?? 'Brand tidak tersedia' }}</p>
<h3 class="mt-1 font-bold leading-snug text-[#345452] group-hover:text-[#E6748E]">{{ $similar['name'] }}</h3>
@if($similar['category'])<p class="mt-1 text-xs text-[#657674]">{{ $similar['category'] }}</p>@endif
@if($similar['shared_concerns'])<ul class="mt-3 flex flex-wrap gap-1.5">@foreach($similar['shared_concerns'] as $concern)<li class="rounded-full bg-[#FDEEF1] px-2.5 py-1 text-xs">{{ $concern }}</li>@endforeach</ul>@endif
<p class="mt-auto pt-4 text-sm font-extrabold">{{ $similar['price'] !== null ? 'Rp'.number_format($similar['price'],0,',','.') : 'Harga belum tersedia' }}</p>
</a>
@endforeach
</div>
@endif
</section>
@endif
</main>
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
