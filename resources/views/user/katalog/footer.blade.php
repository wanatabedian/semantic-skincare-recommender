{{-- Footer katalog: salinan gaya footer landing (belum ada footer global bersama). --}}
<footer class="border-t border-rose/10 bg-cream px-6 py-12 text-rose">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 text-xs font-bold text-muted md:flex-row">
        {{-- Brand disamakan dengan navbar & footer landing: logo Aphrodite + nama. --}}
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-xl font-extrabold text-[#345452]">
            <img src="{{ asset('images/logo/logo-aphrodite.png') }}" alt="" class="h-[42px] w-[42px] object-contain">
            <span>Aphrodite</span>
        </a>
        <p>© {{ date('Y') }} Aphrodite Botanical &amp; Derma Care. Semua hak dilindungi.</p>
    </div>
</footer>
