<!-- Partial navigation reused from katalog navbar, adapted for global use -->
<nav class="fixed top-0 left-0 z-50 flex w-full items-center justify-between border-b border-rose/10 bg-cream/85 px-4 py-4 backdrop-blur-md sm:px-6 md:px-16" aria-label="Navigasi utama">
    <a href="{{ url('/') }}" class="flex items-center gap-3 text-xl font-extrabold tracking-tight text-rose no-underline">
        <span class="grid h-9 w-9 place-items-center rounded-xl bg-teal text-lg text-white shadow-sm" aria-hidden="true">✦</span>
        <span>{{ config('app.name', 'Skinova') }}</span>
    </a>

    <div class="hidden items-center gap-7 text-xs font-bold uppercase tracking-wider text-muted lg:flex">
        <a href="{{ route('bahan') }}" class="transition hover:text-rose">Kandungan</a>
        <a href="{{ route('ontology.dashboard') }}" class="transition hover:text-rose">Eksplorasi Ontologi</a>
        <a href="{{ route('katalog') }}" class="text-rose" aria-current="page">Katalog</a>
        <a href="{{ route('tentang') }}" class="transition hover:text-rose">Tentang</a>
        <a href="{{ route('kuis', ['new' => 1]) }}" class="k-btn rounded-xl bg-rose px-5 py-2.5 text-xs font-black text-white shadow-md hover:bg-teal">Mulai Kuis →</a>
    </div>

    <div class="flex items-center gap-3 lg:hidden">
        <a href="{{ route('tentang') }}" class="text-[10px] font-black uppercase text-ink">Tentang</a>
        <a href="{{ route('bahan') }}" class="text-[10px] font-black uppercase text-ink">Bahan</a>
        <a href="{{ route('kuis', ['new' => 1]) }}" class="rounded-xl bg-rose px-3 py-2 text-[10px] font-black uppercase text-white">Kuis</a>
    </div>
</nav>
