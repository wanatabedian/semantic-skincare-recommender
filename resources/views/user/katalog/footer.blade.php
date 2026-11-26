{{-- Footer katalog: salinan gaya footer landing (belum ada footer global bersama). --}}
<footer class="border-t border-rose/10 bg-cream px-6 py-12 text-rose">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 text-xs font-bold text-muted md:flex-row">
        <div class="flex items-center gap-2 text-base font-extrabold text-rose">
            <span class="grid h-7 w-7 place-items-center rounded-lg bg-teal text-sm text-white" aria-hidden="true">✦</span>
            <span>{{ config('app.name', 'Aphrodite') }}</span>
        </div>
        <p>© {{ date('Y') }} Aphrodite Botanical &amp; Derma Care. Semua hak dilindungi.</p>
    </div>
</footer>
