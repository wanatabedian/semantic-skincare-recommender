<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $product['name'] ?? 'Detail Produk' }} — Aphrodite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'DM Sans',sans-serif}.serif{font-family:'Playfair Display',serif}</style>
</head>
<body class="min-h-screen bg-[#FFFEFA] text-[#345452]">
<nav class="bg-[#FDEEF1] px-5 py-4"><div class="mx-auto flex max-w-6xl justify-between"><a href="/" class="text-xl font-extrabold text-[#E6748E]">Aphrodite</a><a href="/bandingkan" class="text-sm font-bold">Bandingkan produk →</a></div></nav>
<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
@if($error)<section class="rounded-3xl border border-[#E6748E]/20 bg-white p-8 text-center"><p class="text-sm text-[#657674]">{{ $error }}</p><a href="/" class="mt-5 inline-flex rounded-xl bg-[#E6748E] px-5 py-3 text-sm font-bold text-white">Kembali</a></section>
@else
<div class="mb-6 text-xs font-extrabold uppercase tracking-[.25em] text-[#6BB1AD]">Detail produk <span class="ml-2 tracking-normal">{{ $product['id'] }}</span></div>
<section class="grid gap-6 lg:grid-cols-[.85fr_1.15fr]">
<div class="grid min-h-64 place-items-center rounded-3xl border border-[#E6748E]/10 bg-gradient-to-br from-[#FDEEF1] to-[#B8E8EE]/50 p-8 text-center sm:min-h-80"><div><div class="mx-auto grid h-20 w-20 place-items-center rounded-3xl bg-white/80 text-4xl text-[#E6748E]">✧</div><p class="mt-4 text-sm font-semibold text-[#657674]">Gambar produk belum tersedia</p></div></div>
<div class="rounded-3xl border border-[#E6748E]/10 bg-white p-5 shadow-sm sm:p-8"><p class="text-sm font-bold text-[#6BB1AD]">{{ $product['brand'] ?? 'Brand tidak tersedia' }}@if($product['series']) · {{ $product['series'] }}@endif</p><h1 class="serif mt-2 text-3xl font-bold leading-tight text-[#E6748E] sm:text-4xl">{{ $product['name'] }}</h1><p class="mt-3 text-sm text-[#657674]">{{ $product['category'] ?? 'Kategori tidak tersedia' }}</p><p class="mt-6 text-2xl font-extrabold">{{ $product['price'] !== null ? 'Rp'.number_format($product['price'],0,',','.') : 'Harga belum tersedia' }}</p>
<div class="mt-6 flex flex-wrap gap-2"><span class="rounded-full bg-[#B8E8EE]/40 px-3 py-1.5 text-xs font-bold">{{ $product['verification_status'] ?? 'Status verifikasi belum tersedia' }}</span>@if($product['sourceId'])<span class="rounded-full bg-[#FDEEF1] px-3 py-1.5 text-xs font-bold">ID sumber: {{ $product['sourceId'] }}</span>@endif</div>
@if($product['verification_notes'])<div class="mt-4 rounded-2xl bg-[#FDEEF1]/70 p-4 text-sm"><p class="font-bold">Catatan verifikasi</p><ul class="mt-2 list-inside list-disc space-y-1">@foreach($product['verification_notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div>@endif
</div></section>
<section class="mt-6 grid gap-4 sm:grid-cols-2">
@foreach([['Jenis kulit', $product['skin_types']], ['Kelompok usia', $product['age_groups']], ['Keluhan kulit', $product['concerns']], ['Kandungan', $product['ingredients']]] as [$heading, $values])
<div class="rounded-3xl border border-[#E6748E]/10 bg-white p-5"><h2 class="serif text-xl font-bold text-[#345452]">{{ $heading }}</h2>@if($values)<ul class="mt-3 flex flex-wrap gap-2">@foreach($values as $value)<li class="rounded-full bg-[#FDEEF1] px-3 py-1.5 text-sm">{{ $value }}</li>@endforeach</ul>@else<p class="mt-3 text-sm text-[#657674]">Belum tercantum.</p>@endif</div>
@endforeach
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
</main></body></html>
