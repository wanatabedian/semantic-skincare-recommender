<!doctype html>
<html lang="id">
<head>
<link rel="icon" type="image/png" href="{{ asset('images/logo/logo-aphrodite.png') }}">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Hasil Analisis — Aphrodite</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
<style>
  body{font-family:'DM Sans',sans-serif}.serif{font-family:'Playfair Display',serif}
  /* Gradasi biru sangat halus: krem → biru muda, mengikuti tinggi halaman. */
  .rk-bg{background:linear-gradient(180deg,#FFFEFA 0%,#EEF8FA 35%,#DDF1F5 100%)}
  /* Fade-in ringan saat halaman dibuka. */
  @keyframes rk-in{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
  .rk-in{animation:rk-in .45s ease-out both}
  .rk-in-2{animation-delay:.08s}.rk-in-3{animation-delay:.16s}
  /* Kartu hanya fade (tanpa transform) supaya efek hover naik tetap berjalan. */
  @keyframes rk-fade{from{opacity:0}to{opacity:1}}
  .rk-fade{animation:rk-fade .5s ease-out .2s both}
  /* Baris chip brand bisa digeser di mobile tanpa scrollbar tebal. */
  .rk-scroll{scrollbar-width:none}.rk-scroll::-webkit-scrollbar{display:none}
  @media (prefers-reduced-motion: reduce){
    *,*::before,*::after{animation:none!important;transition:none!important;scroll-behavior:auto!important}
  }
</style>
</head>
<body class="rk-bg min-h-screen bg-[#FFFEFA] text-[#345452]">
@include('partials.global-nav')
<nav class="bg-[#FDEEF1] px-5 py-4">
  <div class="mx-auto flex max-w-6xl justify-between">
    <a href="/" class="text-xl font-extrabold text-[#E6748E]">Aphrodite</a>
    <a href="/kuis?new=1" class="text-sm font-bold">Ulangi kuis →</a>
  </div>
</nav>

<main class="mx-auto max-w-6xl px-4 pb-16 pt-10 sm:px-6 sm:pt-14">
  {{-- Judul hasil analisis --}}
  <header class="rk-in">
    <p class="text-xs font-extrabold uppercase tracking-[.25em] text-[#6BB1AD]">Hasil analisis</p>
    <h1 class="serif mt-2 text-3xl font-bold leading-tight text-[#E6748E] sm:text-[2.6rem]">Produk yang cocok untukmu</h1>
  </header>

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
      <div class="rk-in mt-4 flex flex-wrap gap-2" aria-label="Kriteria kamu">
        @foreach($labels as $label)
          <span class="rounded-full border border-[#6BB1AD]/25 bg-white px-3 py-1 text-xs font-semibold text-[#345452]">{{ $label }}</span>
        @endforeach
      </div>
    @endif

    {{-- Ringkasan kecocokan: Plan A / Plan B + catatan --}}
    <div class="rk-in rk-in-2 mt-7 space-y-3">
      @if($result['plan'] === 'A')
        <div class="flex items-start gap-3 rounded-2xl border border-[#6BB1AD]/25 bg-[#EEF8F8] px-4 py-3.5 text-sm sm:px-5" role="status">
          <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-[#6BB1AD] text-white" aria-hidden="true">
            <svg class="h-3.5 w-3.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 6.5 5 9l4.5-5"/></svg>
          </span>
          <div>
            <p class="font-bold">Semua kriteria terpenuhi</p>
            <p class="mt-0.5 text-[#657674]">{{ $result['total'] }} produk cocok dengan semua pilihanmu{{ $result['max_score'] === 0 ? ' (kuis dikosongkan, jadi semua produk ditampilkan)' : '' }}.</p>
          </div>
        </div>
      @elseif($result['plan'] === 'B')
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50/70 px-4 py-3.5 text-sm text-amber-900 sm:px-5" role="status">
          <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-amber-400 text-xs font-extrabold text-white" aria-hidden="true">!</span>
          <div>
            <p class="font-bold">Tidak ada produk yang memenuhi semua kriteria.</p>
            <p class="mt-0.5">Kami melonggarkan: <b>{{ implode(', ', $result['relaxed']) }}</b>. Berikut {{ $result['total'] }} produk paling mendekati.</p>
          </div>
        </div>
      @endif

      @if($result['teen_lock']['message'])
        <div class="flex items-start gap-3 rounded-2xl border border-[#E6748E]/15 bg-[#FDEEF1]/70 px-4 py-3.5 text-sm sm:px-5" role="note">
          <span aria-hidden="true">🔒</span>
          <p>{{ $result['teen_lock']['message'] }} Retinoid dapat membuat kulit remaja iritasi dan lebih sensitif terhadap matahari.</p>
        </div>
      @endif
      @foreach($result['notices'] as $notice)
        <div class="flex items-start gap-3 rounded-2xl border border-[#E6748E]/15 bg-[#FDEEF1]/70 px-4 py-3.5 text-sm sm:px-5" role="note"><span aria-hidden="true">⚠️</span><p>{{ $notice }}</p></div>
      @endforeach
    </div>

    @if($result['total'] === 0)
      <div class="rk-in rk-in-3 mt-8 rounded-3xl border border-[#E6748E]/10 bg-white p-8 text-center shadow-[0_12px_36px_rgba(52,84,82,.06)] sm:p-10">
        <h2 class="serif text-2xl font-bold">Belum ada produk yang cocok</h2>
        <p class="mt-2 text-sm text-[#657674]">Tidak ada produk untuk kelompok umur ini. Coba ubah atau kurangi kriteria kuis.</p>
        <a href="/kuis" class="mt-5 inline-flex rounded-xl bg-[#E6748E] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#d95f7b]">Ubah jawaban</a>
      </div>
    @else
      @php($brands = collect($result['products'])->pluck('brand')->filter()->unique()->sort()->values())
      {{-- Toolbar: filter brand + urutkan + jumlah produk --}}
      <div class="rk-in rk-in-3 relative z-30 mt-8 rounded-2xl border border-[#E6748E]/10 bg-white p-3 shadow-[0_8px_24px_rgba(52,84,82,.05)] sm:p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="rk-scroll -mx-1 flex gap-2 overflow-x-auto px-1 py-0.5 lg:flex-wrap" id="brand-chips" aria-label="Filter brand">
            <button type="button" data-brand="" class="chip shrink-0 rounded-full border border-[#6BB1AD] bg-[#B8E8EE]/40 px-3.5 py-1.5 text-xs font-bold transition hover:border-[#6BB1AD]">Semua brand</button>
            @foreach($brands as $brand)
              <button type="button" data-brand="{{ $brand }}" class="chip shrink-0 rounded-full border border-[#E6748E]/20 px-3.5 py-1.5 text-xs font-bold transition hover:border-[#6BB1AD]">{{ $brand }}</button>
            @endforeach
          </div>
          {{-- Dropdown kustom (putih) yang mengendalikan <select id="sort"> asli; logika urut tetap di select. --}}
          <div class="flex shrink-0 items-center gap-2 text-xs font-bold text-[#657674]">
            <span id="sort-label">Urutkan</span>
            <select id="sort" class="sr-only" tabindex="-1" aria-hidden="true">
              <option value="score">Skor kecocokan</option>
              <option value="price_asc">Harga termurah</option>
              <option value="price_desc">Harga termahal</option>
            </select>
            <div class="relative" id="sort-menu">
              <button type="button" id="sort-button" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="sort-label sort-button"
                      class="flex min-w-[170px] items-center justify-between gap-3 rounded-xl border border-[#E6748E]/20 bg-white px-3.5 py-2 text-sm font-semibold text-[#345452] transition hover:border-[#6BB1AD] focus:border-[#6BB1AD] focus:outline-none focus:ring-2 focus:ring-[#6BB1AD]/25">
                <span id="sort-current">Skor kecocokan</span>
                <svg class="h-4 w-4 text-[#6BB1AD] transition-transform" id="sort-caret" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 8 5 5 5-5"/></svg>
              </button>
              <ul id="sort-list" role="listbox" aria-labelledby="sort-label" tabindex="-1" hidden
                  class="absolute right-0 z-20 mt-2 w-full min-w-[190px] overflow-hidden rounded-2xl border border-[#E6748E]/10 bg-white p-1.5 shadow-[0_14px_34px_rgba(52,84,82,.12)]">
                <li role="option" data-value="score" class="cursor-pointer rounded-xl px-3 py-2 text-sm font-semibold text-[#345452] transition hover:bg-[#FDEEF1] focus:bg-[#FDEEF1] focus:outline-none">Skor kecocokan</li>
                <li role="option" data-value="price_asc" class="cursor-pointer rounded-xl px-3 py-2 text-sm font-semibold text-[#345452] transition hover:bg-[#FDEEF1] focus:bg-[#FDEEF1] focus:outline-none">Harga termurah</li>
                <li role="option" data-value="price_desc" class="cursor-pointer rounded-xl px-3 py-2 text-sm font-semibold text-[#345452] transition hover:bg-[#FDEEF1] focus:bg-[#FDEEF1] focus:outline-none">Harga termahal</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
      <p class="mt-4 text-sm text-[#657674]"><b class="text-[#345452]"><span id="visible-count">{{ $result['total'] }}</span> produk</b> ditampilkan dari ontologi Aphrodite.</p>

      <div id="grid" class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($result['products'] as $i => $product)
          <article class="card rk-fade group flex flex-col rounded-3xl border border-[#E6748E]/10 bg-white p-4 shadow-[0_6px_20px_rgba(52,84,82,.05)] transition duration-300 hover:-translate-y-1 hover:border-[#6BB1AD]/30 hover:shadow-[0_16px_36px_rgba(52,84,82,.10)] sm:p-5"
                   data-order="{{ $i }}" data-price="{{ $product['price'] ?? '' }}" data-brand="{{ $product['brand'] ?? '' }}">
            @include('user.partials.rekomendasi-photo', ['product' => $product])
            <div class="flex items-center justify-between gap-3">
              <p class="min-w-0 truncate text-[11px] font-bold uppercase tracking-wider text-[#6BB1AD]">{{ $product['brand'] ?? 'Brand tidak tersedia' }} · {{ $product['series'] ?? 'Tanpa seri' }}</p>
              @if($result['max_score'] > 0)
                <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-extrabold {{ $product['match_percent'] === 100 ? 'bg-[#B8E8EE]/50 text-[#2B5E5B]' : 'bg-amber-100 text-amber-900' }}"
                      title="Skor {{ $product['score'] }} dari {{ $product['max_score'] }}">{{ $product['match_percent'] }}% cocok</span>
              @endif
            </div>
            <h2 class="serif mt-2 line-clamp-2 min-h-[2.75rem] text-lg font-bold leading-snug" title="{{ $product['name'] }}">{{ $product['name'] }}</h2>
            <div class="mt-2 flex items-end justify-between gap-3">
              <p class="text-xs text-[#657674]">{{ $product['category'] ?? 'Kategori tidak tersedia' }}</p>
              <p class="shrink-0 text-base font-extrabold text-[#E6748E]">{{ $product['price'] ? 'Rp'.number_format($product['price'], 0, ',', '.') : 'Harga tidak tersedia' }}</p>
            </div>

            @if($product['reasons'])
              <div class="mt-4 border-t border-[#E6748E]/10 pt-3 text-xs">
                <p class="font-bold">Direkomendasikan karena:</p>
                <ul class="mt-1.5 space-y-1 text-[#657674]">@foreach($product['reasons'] as $reason)<li class="flex gap-1.5"><span class="text-[#6BB1AD]" aria-hidden="true">✓</span><span>{{ $reason }}</span></li>@endforeach</ul>
              </div>
            @endif
            @if($product['missed'])
              <p class="mt-2 text-xs text-amber-800">Tidak memenuhi: {{ implode(', ', $product['missed']) }}</p>
            @endif

            <div class="mt-auto grid grid-cols-2 gap-2 pt-5">
              <a href="{{ route('produk.detail', ['id' => $product['id']]) }}" class="inline-flex items-center justify-center rounded-xl bg-[#E6748E] px-3 py-2.5 text-xs font-bold text-white transition hover:bg-[#d95f7b] sm:text-sm">Lihat detail</a>
              <div>
                <a href="{{ route('produk.detail', ['id' => $product['id']]) }}{{ $result['criteria']['age'] ?? null ? '?age='.urlencode($result['criteria']['age']) : '' }}#lengkapi-rutinitas"
                   data-routine="{{ $product['id'] }}" class="routine inline-flex h-full w-full items-center justify-center rounded-xl border border-[#6BB1AD]/40 px-3 py-2.5 text-center text-xs font-bold transition hover:border-[#6BB1AD] hover:bg-[#B8E8EE]/20">Lengkapi rutinitas</a>
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
        'high' => ['label' => 'Hampir cocok', 'badge' => 'bg-[#B8E8EE]/60 text-[#2B5E5B]', 'bar' => 'bg-[#6BB1AD]', 'ring' => 'border-[#6BB1AD]/30'],
        'mid' => ['label' => 'Cukup cocok', 'badge' => 'bg-amber-100 text-amber-900', 'bar' => 'bg-amber-400', 'ring' => 'border-amber-200'],
        'low' => ['label' => 'Sebagian cocok', 'badge' => 'bg-[#FDEEF1] text-[#B04A63]', 'bar' => 'bg-[#E6748E]', 'ring' => 'border-[#E6748E]/20'],
      ])
      <section class="mt-16 border-t border-[#E6748E]/10 pt-10" aria-labelledby="partial-title">
        <div class="flex flex-wrap items-end justify-between gap-4">
          <div class="max-w-2xl">
            <p class="text-xs font-extrabold uppercase tracking-[.25em] text-[#6BB1AD]">Alternatif lain</p>
            <h2 id="partial-title" class="serif mt-1 text-2xl font-bold text-[#345452] sm:text-3xl">Cocok sebagian dengan pilihanmu</h2>
          </div>
          <div class="hidden gap-2 sm:flex">
            <button type="button" data-scroll="-1" aria-label="Geser ke kiri" class="grid h-10 w-10 place-items-center rounded-full border border-[#E6748E]/20 bg-white text-lg font-bold transition hover:border-[#6BB1AD] hover:bg-[#B8E8EE]/20">←</button>
            <button type="button" data-scroll="1" aria-label="Geser ke kanan" class="grid h-10 w-10 place-items-center rounded-full border border-[#E6748E]/20 bg-white text-lg font-bold transition hover:border-[#6BB1AD] hover:bg-[#B8E8EE]/20">→</button>
          </div>
        </div>

        <div id="partial-track" tabindex="0" aria-label="Daftar produk cocok sebagian, geser ke samping"
             class="-mx-4 mt-5 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth px-4 pb-6 pt-1 sm:-mx-6 sm:px-6 focus-visible:outline-2 focus-visible:outline-[#6BB1AD]">
          @foreach($result['partial'] as $product)
            @php($t = $tiers[$product['match_percent'] >= 75 ? 'high' : ($product['match_percent'] >= 50 ? 'mid' : 'low')])
            <article class="partial-card flex w-[80vw] max-w-[300px] shrink-0 snap-start flex-col rounded-3xl border {{ $t['ring'] }} bg-white p-4 shadow-[0_6px_20px_rgba(52,84,82,.05)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_16px_36px_rgba(52,84,82,.10)] sm:w-[290px] sm:p-5">
              @include('user.partials.rekomendasi-photo', ['product' => $product])
              <div class="flex items-center justify-between gap-3">
                <p class="min-w-0 truncate text-[11px] font-bold uppercase tracking-wider text-[#6BB1AD]">{{ $product['brand'] ?? 'Brand tidak tersedia' }}</p>
                <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-extrabold {{ $t['badge'] }}"
                      title="Skor {{ $product['score'] }} dari {{ $product['max_score'] }}">{{ $product['match_percent'] }}% · {{ $t['label'] }}</span>
              </div>
              <div class="mt-3 h-1 overflow-hidden rounded-full bg-[#345452]/10" role="img" aria-label="Kecocokan {{ $product['match_percent'] }} persen">
                <div class="h-full rounded-full {{ $t['bar'] }}" style="width: {{ $product['match_percent'] }}%"></div>
              </div>
              <h3 class="serif mt-3 line-clamp-2 min-h-[2.6rem] text-base font-bold leading-snug sm:text-lg" title="{{ $product['name'] }}">{{ $product['name'] }}</h3>
              <div class="mt-1.5 flex items-end justify-between gap-3">
                <p class="text-xs text-[#657674]">{{ $product['category'] ?? 'Kategori tidak tersedia' }}</p>
                <p class="shrink-0 font-extrabold text-[#E6748E]">{{ $product['price'] ? 'Rp'.number_format($product['price'], 0, ',', '.') : 'Harga tidak tersedia' }}</p>
              </div>

              <div class="mt-4 space-y-2 text-xs">
                <div class="rounded-2xl bg-[#B8E8EE]/20 p-3">
                  <p class="font-bold">Masuk karena:</p>
                  <ul class="mt-1 space-y-1 text-[#4E6664]">@foreach($product['reasons'] as $reason)<li>✓ {{ $reason }}</li>@endforeach</ul>
                </div>
                @if($product['missed_detail'])
                  <div class="rounded-2xl bg-[#FDEEF1]/70 p-3">
                    <p class="font-bold text-[#B04A63]">Belum memenuhi:</p>
                    <ul class="mt-1 space-y-1 text-[#7A3B4B]">@foreach($product['missed_detail'] as $miss)<li>✗ {{ $miss }}</li>@endforeach</ul>
                  </div>
                @endif
              </div>

              <a href="{{ route('produk.detail', ['id' => $product['id']]) }}" class="mt-auto inline-flex justify-center rounded-xl border border-[#E6748E]/40 px-4 py-2.5 text-sm font-bold text-[#E6748E] transition hover:bg-[#FDEEF1]">Lihat detail</a>
            </article>
          @endforeach
        </div>
      </section>
    @endif
  @endif

  <div class="mt-10 flex flex-wrap gap-3 border-t border-[#E6748E]/10 pt-8">
    <a href="/kuis?new=1" class="rounded-xl bg-[#E6748E] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#d95f7b]">Ulangi kuis</a>
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
<script>
// Tampilan dropdown "Urutkan": hanya mengganti nilai <select id="sort"> lalu memicu event change-nya.
(() => {
  const select = document.querySelector('#sort'), btn = document.querySelector('#sort-button'), list = document.querySelector('#sort-list');
  if (!select || !btn || !list) return;
  const items = [...list.querySelectorAll('[role=option]')];
  items.forEach(li => li.tabIndex = -1);
  const ACTIVE = ['bg-[#B8E8EE]/30', 'text-[#2B5E5B]'];
  const sync = () => items.forEach(li => {
    const on = li.dataset.value === select.value;
    li.setAttribute('aria-selected', on); ACTIVE.forEach(c => li.classList.toggle(c, on));
    if (on) document.querySelector('#sort-current').textContent = li.textContent;
  });
  const open = () => {
    list.hidden = false; btn.setAttribute('aria-expanded', 'true');
    document.querySelector('#sort-caret').classList.add('rotate-180');
    (items.find(li => li.dataset.value === select.value) || items[0]).focus();
  };
  const close = (focusBtn = false) => {
    list.hidden = true; btn.setAttribute('aria-expanded', 'false');
    document.querySelector('#sort-caret').classList.remove('rotate-180');
    if (focusBtn) btn.focus();
  };
  const choose = li => {
    if (select.value !== li.dataset.value) { select.value = li.dataset.value; select.dispatchEvent(new Event('change')); }
    sync(); close(true);
  };
  btn.addEventListener('click', () => list.hidden ? open() : close());
  btn.addEventListener('keydown', e => { if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); open(); } });
  items.forEach(li => li.addEventListener('click', () => choose(li)));
  list.addEventListener('keydown', e => {
    const i = items.indexOf(document.activeElement);
    if (e.key === 'ArrowDown') { e.preventDefault(); items[Math.min(i + 1, items.length - 1)].focus(); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); items[Math.max(i - 1, 0)].focus(); }
    else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); if (i >= 0) choose(items[i]); }
    else if (e.key === 'Escape' || e.key === 'Tab') close(e.key === 'Escape');
  });
  document.addEventListener('click', e => { if (!list.hidden && !document.querySelector('#sort-menu').contains(e.target)) close(); });
  sync();
})();
</script>
</body>
</html>
