{{--
    Foto produk untuk kartu hasil rekomendasi (utama & alternatif).
    Parameter: $product (dari RecommendationEngine), $class (opsional, tambahan kelas wadah).
    Bila foto tidak ada / gagal dimuat, tampil inisial brand + "Foto belum tersedia".
--}}
@php($initial = mb_strtoupper(mb_substr($product['brand'] ?? $product['name'], 0, 1)))
<a href="{{ route('produk.detail', ['id' => $product['id']]) }}" tabindex="-1" aria-hidden="true"
   class="relative mb-4 block aspect-[4/3] overflow-hidden rounded-2xl bg-gradient-to-br from-[#FDEEF1] to-[#B8E8EE]/40 {{ $class ?? '' }}">
  @if($product['image'])
    <img src="{{ $product['image'] }}" alt="Foto {{ $product['name'] }}" loading="lazy" decoding="async"
         class="h-full w-full object-contain p-3 transition duration-300 hover:scale-105"
         onerror="const fallback = this.nextElementSibling; fallback.hidden = false; fallback.style.display = ''; this.remove();">
  @endif
  {{-- Kelas `flex` mengalahkan atribut `hidden` di Tailwind, jadi fallback disembunyikan lewat style inline saat foto ada. --}}
  <div class="absolute inset-0 flex flex-col items-center justify-center gap-1 text-[#E6748E]/70" @if($product['image']) hidden style="display:none" @endif>
    <span class="serif text-4xl font-bold">{{ $initial }}</span>
    <span class="text-[11px] font-bold uppercase tracking-widest text-[#657674]">Foto belum tersedia</span>
  </div>
</a>
