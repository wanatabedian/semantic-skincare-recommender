<!doctype html>
<html lang="id">
<head>
<link rel="icon" type="image/png" href="{{ asset('images/logo/logo-aphrodite.png') }}">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Hasil Analisis — Aphrodite</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
<style>body{font-family:'DM Sans',sans-serif}.serif{font-family:'Playfair Display',serif}</style>
</head>
<body class="min-h-screen bg-[#FFFEFA] text-[#345452]">
@include('partials.global-nav')
<nav class="bg-[#FDEEF1] px-5 py-4">
  <div class="mx-auto flex max-w-6xl justify-between">
    <a href="/" class="text-xl font-extrabold text-[#E6748E]">Aphrodite</a>
    <a href="/kuis?new=1" class="text-sm font-bold">Ulangi kuis →</a>
  </div>
</nav>

<main class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
  <p class="text-xs font-extrabold uppercase tracking-[.25em] text-[#6BB1AD]">Hasil analisis</p>
  <h1 class="serif mt-2 text-3xl font-bold text-[#E6748E] sm:text-4xl">Produk yang cocok untukmu</h1>

  @if($error)
    {{-- Fuseki mati / query gagal --}}
    <div class="mt-6 rounded-2xl bg-red-50 p-5 text-sm text-red-700" role="alert">
      <p class="font-bold">Rekomendasi belum bisa dimuat.</p>
      <p class="mt-1">{{ $error }}</p>
    </div>

  @elseif(count($validationErrors))
    {{-- Input kuis tidak lolos whitelist / validasi --}}
    <div class="mt-6 rounded-2xl bg-red-50 p-5 text-sm text-red-700" role="alert">
      <p class="font-bold">Pilihan kuis tidak valid.</p>
      <ul class="mt-2 list-disc pl-5">@foreach($validationErrors as $message)<li>{{ $message }}</li>@endforeach</ul>
      <a href="/kuis" class="mt-4 inline-flex rounded-xl bg-[#E6748E] px-5 py-3 text-sm font-bold text-white">Ubah jawaban</a>
    </div>

  @elseif($result)
    @if($labels)
      <div class="mt-4 flex flex-wrap gap-2" aria-label="Kriteria kamu">
        @foreach($labels as $label)
          <span class="rounded-full bg-[#B8E8EE]/40 px-3 py-1 text-xs font-bold">{{ $label }}</span>
        @endforeach
      </div>
    @endif

    {{-- Banner Plan A / Plan B --}}
    @if($result['plan'] === 'A')
      <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-800" role="status">
        <p class="font-bold">✓ Semua kriteria terpenuhi</p>
        <p class="mt-1">{{ $result['total'] }} produk cocok dengan semua pilihanmu{{ $result['max_score'] === 0 ? ' (kuis dikosongkan, jadi semua produk ditampilkan)' : '' }}.</p>
      </div>
    @elseif($result['plan'] === 'B')
      <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900" role="status">
        <p class="font-bold">Tidak ada produk yang memenuhi semua kriteria.</p>
        <p class="mt-1">Kami melonggarkan: <b>{{ implode(', ', $result['relaxed']) }}</b>. Berikut {{ $result['total'] }} produk paling mendekati.</p>
      </div>
    @endif

    @if($result['teen_lock']['message'])
      <div class="mt-3 rounded-2xl border border-[#E6748E]/20 bg-[#FDEEF1] p-4 text-sm" role="note">
        🔒 {{ $result['teen_lock']['message'] }} Retinoid dapat membuat kulit remaja iritasi dan lebih sensitif terhadap matahari.
      </div>
    @endif
    @foreach($result['notices'] as $notice)
      <div class="mt-3 rounded-2xl border border-[#E6748E]/20 bg-[#FDEEF1] p-4 text-sm" role="note">⚠️ {{ $notice }}</div>
    @endforeach

    @if($result['total'] === 0)
      <div class="mt-6 rounded-2xl border border-[#E6748E]/10 bg-white p-8 text-center">
        <h2 class="serif text-2xl font-bold">Belum ada produk yang cocok</h2>
        <p class="mt-2 text-sm text-[#657674]">Tidak ada produk untuk kelompok umur ini. Coba ubah atau kurangi kriteria kuis.</p>
        <a href="/kuis" class="mt-5 inline-flex rounded-xl bg-[#E6748E] px-5 py-3 text-sm font-bold text-white">Ubah jawaban</a>
      </div>
    @else
      @php($brands = collect($result['products'])->pluck('brand')->filter()->unique()->sort()->values())
      <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-2" id="brand-chips" aria-label="Filter brand">
          <button type="button" data-brand="" class="chip rounded-full border border-[#6BB1AD] bg-[#B8E8EE]/40 px-3 py-1 text-xs font-bold">Semua brand</button>
          @foreach($brands as $brand)
            <button type="button" data-brand="{{ $brand }}" class="chip rounded-full border border-[#E6748E]/20 px-3 py-1 text-xs font-bold">{{ $brand }}</button>
          @endforeach
        </div>
        <label class="text-xs font-bold">Urutkan
          <select id="sort" class="ml-2 rounded-xl border border-[#E6748E]/20 bg-white px-3 py-2 text-sm">
            <option value="score">Skor kecocokan</option>
            <option value="price_asc">Harga termurah</option>
            <option value="price_desc">Harga termahal</option>
          </select>
        </label>
      </div>
      <p class="mt-3 text-sm text-[#657674]"><span id="visible-count">{{ $result['total'] }}</span> produk ditampilkan dari ontologi Aphrodite.</p>

      <div id="grid" class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach($result['products'] as $i => $product)
          <article class="card flex flex-col rounded-3xl border border-[#E6748E]/10 bg-white p-5 shadow-sm"
                   data-order="{{ $i }}" data-price="{{ $product['price'] ?? '' }}" data-brand="{{ $product['brand'] ?? '' }}">
            @include('user.partials.rekomendasi-photo', ['product' => $product])
            <div class="flex items-start justify-between gap-3">
              <p class="text-xs font-bold text-[#6BB1AD]">{{ $product['brand'] ?? 'Brand tidak tersedia' }} · {{ $product['series'] ?? 'Tanpa seri' }}</p>
              @if($result['max_score'] > 0)
                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-extrabold {{ $product['match_percent'] === 100 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900' }}"
                      title="Skor {{ $product['score'] }} dari {{ $product['max_score'] }}">{{ $product['match_percent'] }}% cocok</span>
              @endif
            </div>
            <h2 class="serif mt-2 text-xl font-bold">{{ $product['name'] }}</h2>
            <p class="mt-1 text-xs text-[#657674]">{{ $product['category'] ?? 'Kategori tidak tersedia' }}</p>
            <p class="mt-3 text-lg font-extrabold text-[#E6748E]">{{ $product['price'] ? 'Rp'.number_format($product['price'], 0, ',', '.') : 'Harga tidak tersedia' }}</p>

            @if($product['reasons'])
              <div class="mt-3 rounded-2xl bg-[#B8E8EE]/20 p-3 text-xs">
                <p class="font-bold">Direkomendasikan karena:</p>
                <ul class="mt-1 space-y-1">@foreach($product['reasons'] as $reason)<li>✓ {{ $reason }}</li>@endforeach</ul>
              </div>
            @endif
            @if($product['missed'])
              <p class="mt-2 text-xs text-amber-800">Tidak memenuhi: {{ implode(', ', $product['missed']) }}</p>
            @endif

            <div class="mt-auto grid gap-2 pt-5">
              <a href="{{ route('produk.detail', ['id' => $product['id']]) }}" class="inline-flex justify-center rounded-xl bg-[#E6748E] px-4 py-3 text-sm font-bold text-white">Lihat detail</a>
              <div>
                <a href="{{ route('produk.detail', ['id' => $product['id']]) }}{{ $result['criteria']['age'] ?? null ? '?age='.urlencode($result['criteria']['age']) : '' }}#lengkapi-rutinitas"
                   data-routine="{{ $product['id'] }}" class="routine inline-flex w-full justify-center rounded-xl border border-[#6BB1AD]/40 px-3 py-3 text-xs font-bold">Lengkapi rutinitas</a>
              </div>
            </div>
          </article>
        @endforeach
      </div>
    @endif

    {{-- Alternatif: produk yang cocok sebagian, ditampilkan di akhir sebagai baris geser ke samping. --}}
    @if(! empty($result['partial']))
      {{-- Tingkat kecocokan: >= 75% hampir cocok, 50–74% cukup cocok, < 50% sebagian cocok. --}}
      @php($tiers = [
        'high' => ['label' => 'Hampir cocok', 'badge' => 'bg-[#B8E8EE]/60 text-[#2B5E5B]', 'bar' => 'bg-[#6BB1AD]', 'ring' => 'border-[#6BB1AD]/40'],
        'mid' => ['label' => 'Cukup cocok', 'badge' => 'bg-amber-100 text-amber-900', 'bar' => 'bg-amber-400', 'ring' => 'border-amber-200'],
        'low' => ['label' => 'Sebagian cocok', 'badge' => 'bg-[#FDEEF1] text-[#B04A63]', 'bar' => 'bg-[#E6748E]', 'ring' => 'border-[#E6748E]/25'],
      ])
      <section class="mt-14 border-t border-[#E6748E]/10 pt-10" aria-labelledby="partial-title">
        <div class="flex flex-wrap items-end justify-between gap-4">
          <div class="max-w-2xl">
            <p class="text-xs font-extrabold uppercase tracking-[.25em] text-[#6BB1AD]">Alternatif lain</p>
            <h2 id="partial-title" class="serif mt-1 text-2xl font-bold text-[#345452] sm:text-3xl">Cocok sebagian dengan pilihanmu</h2>
            
          </div>
          <div class="hidden gap-2 sm:flex">
            <button type="button" data-scroll="-1" aria-label="Geser ke kiri" class="grid h-10 w-10 place-items-center rounded-full border border-[#E6748E]/20 bg-white text-lg font-bold hover:border-[#6BB1AD]">←</button>
            <button type="button" data-scroll="1" aria-label="Geser ke kanan" class="grid h-10 w-10 place-items-center rounded-full border border-[#E6748E]/20 bg-white text-lg font-bold hover:border-[#6BB1AD]">→</button>
          </div>
        </div>

        
        <div id="partial-track" tabindex="0" aria-label="Daftar produk cocok sebagian, geser ke samping"
             class="-mx-4 mt-5 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth px-4 pb-5 sm:-mx-6 sm:px-6 focus-visible:outline-2 focus-visible:outline-[#6BB1AD]">
          @foreach($result['partial'] as $product)
            @php($t = $tiers[$product['match_percent'] >= 75 ? 'high' : ($product['match_percent'] >= 50 ? 'mid' : 'low')])
            <article class="partial-card flex w-[80vw] max-w-[310px] shrink-0 snap-start flex-col rounded-3xl border {{ $t['ring'] }} bg-white p-5 shadow-sm sm:w-[300px]">
              @include('user.partials.rekomendasi-photo', ['product' => $product])
              <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-bold text-[#6BB1AD]">{{ $product['brand'] ?? 'Brand tidak tersedia' }}</p>
                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-extrabold {{ $t['badge'] }}"
                      title="Skor {{ $product['score'] }} dari {{ $product['max_score'] }}">{{ $product['match_percent'] }}% · {{ $t['label'] }}</span>
              </div>
              <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-[#345452]/10" role="img" aria-label="Kecocokan {{ $product['match_percent'] }} persen">
                <div class="h-full rounded-full {{ $t['bar'] }}" style="width: {{ $product['match_percent'] }}%"></div>
              </div>
              <h3 class="serif mt-3 text-lg font-bold leading-snug">{{ $product['name'] }}</h3>
              <p class="mt-1 text-xs text-[#657674]">{{ $product['category'] ?? 'Kategori tidak tersedia' }}</p>
              <p class="mt-2 font-extrabold text-[#E6748E]">{{ $product['price'] ? 'Rp'.number_format($product['price'], 0, ',', '.') : 'Harga tidak tersedia' }}</p>

              <div class="mt-3 rounded-2xl bg-[#B8E8EE]/20 p-3 text-xs">
                <p class="font-bold">Masuk karena:</p>
                <ul class="mt-1 space-y-1">@foreach($product['reasons'] as $reason)<li>✓ {{ $reason }}</li>@endforeach</ul>
              </div>
              @if($product['missed_detail'])
                <div class="mt-2 rounded-2xl bg-[#FDEEF1]/70 p-3 text-xs">
                  <p class="font-bold text-[#B04A63]">Belum memenuhi:</p>
                  <ul class="mt-1 space-y-1 text-[#7A3B4B]">@foreach($product['missed_detail'] as $miss)<li>✗ {{ $miss }}</li>@endforeach</ul>
                </div>
              @endif

              <a href="{{ route('produk.detail', ['id' => $product['id']]) }}" class="mt-auto inline-flex justify-center rounded-xl border border-[#E6748E]/40 px-4 py-3 text-sm font-bold text-[#E6748E] hover:bg-[#FDEEF1]">Lihat detail</a>
            </article>
          @endforeach
        </div>
      </section>
    @endif
  @endif

  <div class="mt-8 flex flex-wrap gap-3">
    <a href="/kuis?new=1" class="rounded-xl bg-[#E6748E] px-5 py-3 text-sm font-bold text-white">Ulangi kuis</a>
  </div>
</main>

<script>
(() => {
  const read = (key) => { try { return JSON.parse(localStorage.getItem(key) || '[]'); } catch { return []; } };
  const write = (key, value) => { try { localStorage.setItem(key, JSON.stringify(value)); } catch {} };

  // Lengkapi rutinitas: simpan ID ke localStorage untuk Routine Builder (Orang 4), lalu buka detail.
  document.querySelectorAll('.routine').forEach(link => link.addEventListener('click', () => {
    const routine = read('aphrodite-routine');
    if (!routine.includes(link.dataset.routine)) { routine.push(link.dataset.routine); write('aphrodite-routine', routine); }
  }));

  // Tombol geser untuk baris "cocok sebagian".
  const track = document.querySelector('#partial-track');
  document.querySelectorAll('[data-scroll]').forEach(btn => btn.addEventListener('click', () => {
    if (!track) return;
    const card = track.querySelector('.partial-card');
    const step = card ? card.getBoundingClientRect().width + 16 : track.clientWidth * 0.8;
    track.scrollBy({ left: Number(btn.dataset.scroll) * step, behavior: 'smooth' });
  }));

  // Urutkan & filter brand di sisi browser (kriteria kuis tetap di URL).
  const grid = document.querySelector('#grid');
  if (!grid) return;
  const cards = [...grid.querySelectorAll('.card')];
  const price = (c) => c.dataset.price === '' ? null : Number(c.dataset.price);
  document.querySelector('#sort').onchange = (e) => {
    const mode = e.target.value;
    cards.sort((a, b) => {
      if (mode === 'score') return a.dataset.order - b.dataset.order;
      const pa = price(a), pb = price(b);
      if (pa === null) return 1; if (pb === null) return -1;
      return mode === 'price_asc' ? pa - pb : pb - pa;
    }).forEach(c => grid.appendChild(c));
  };
  const chips = document.querySelectorAll('.chip');
  chips.forEach(chip => chip.onclick = () => {
    chips.forEach(c => c.classList.remove('border-[#6BB1AD]', 'bg-[#B8E8EE]/40'));
    chip.classList.add('border-[#6BB1AD]', 'bg-[#B8E8EE]/40');
    let visible = 0;
    cards.forEach(c => { const show = !chip.dataset.brand || c.dataset.brand === chip.dataset.brand; c.style.display = show ? '' : 'none'; visible += show; });
    document.querySelector('#visible-count').textContent = visible;
  });
})();
</script>
</body>
</html>
