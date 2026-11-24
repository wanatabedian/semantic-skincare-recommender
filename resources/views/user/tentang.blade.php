@extends('layouts.app')

@section('title', 'Tentang & Tim — Aphrodite')

@section('content')
@php
    // Angka di bawah mengikuti ontology/skincare_ontology_clean.ttl versi saat ini
    // (diuji di tests/Feature/AboutPageTest.php supaya tidak berbeda dari data).
    $brands = ['Wardah', 'Emina', 'Kahf', 'LABORE', 'Crystallure', 'TAVI'];

    $steps = [
        ['title' => 'Pemodelan ontologi', 'body' => 'Domain skincare dimodelkan dalam OWL dan ditulis dalam format Turtle (RDF). Ontologi CLEAN memiliki 8 kelas utama: Product, Brand, Category, ProductSeries, AgeGroup, SkinType, SkinConcern, dan Ingredient, yang dihubungkan oleh 7 object property (mis. hasBrand, hasIngredient, treatsConcern) dan 4 datatype property (mis. productName, estimatedPrice).'],
        ['title' => 'Pembersihan & audit data', 'body' => 'Data produk diaudit dengan skrip Python (rdflib) untuk sepuluh jenis masalah kualitas data (A1–A10), seperti kolom tergeser, bahan yang tercampur klaim, dan label kosong. Perbaikan dicatat di changelog, dan aturan bentuk data didefinisikan dengan SHACL.'],
        ['title' => 'Penyimpanan di triple store', 'body' => 'Ontologi dimuat ke Apache Jena Fuseki, yang menyediakan endpoint SPARQL. Dataset aphrodite (ontologi v2) dipakai untuk rekomendasi, katalog, dan kuis; dataset aphrodite-clean dipakai untuk Ontology Explorer dan detail produk.'],
        ['title' => 'Query SPARQL dari aplikasi', 'body' => 'Aplikasi Laravel mengirim query SPARQL ke Fuseki melalui HTTP. Mesin rekomendasi memberi skor per kriteria (keluhan, jenis kulit, kategori, bahan, harga) dengan filter keras kelompok usia dan Teen Lock, yaitu aturan proyek yang menyembunyikan produk ber-retinoid untuk usia remaja.'],
    ];

    $stack = ['OWL & RDF (Turtle)', 'SPARQL 1.1', 'SHACL', 'Apache Jena Fuseki', 'Python + rdflib', 'Laravel 12 (PHP)', 'Blade & Tailwind CSS', 'Cytoscape.js'];

    $team = [
        ['name' => 'Indah Ayu Gemilang', 'role' => 'Recommendation Engine'],
        ['name' => 'Quinsha Ilmi Azzahra', 'role' => 'Knowledge Engineer'],
        ['name' => 'Keizya Azalea Azka', 'role' => 'Experience & Kuis'],
        ['name' => 'Jihan Fadhillah', 'role' => 'Produk & Rutinitas'],
        ['name' => 'Dian Indriani', 'role' => 'Eksplorasi Katalog'],
    ];

    $initials = fn (string $name) => collect(explode(' ', $name))->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

<style>
    /* Palet Aphrodite (sama dengan navbar), dibatasi ke halaman ini saja. */
    .about-page {
        --rose: #E6748E;
        --teal: #6BB1AD;
        --cream: #FDEEF1;
        --ink: #345452;
        --muted: #657674;
        font-family: "DM Sans", Arial, sans-serif;
        color: var(--ink);
    }
    .about-page h1, .about-page h2 { font-family: "Playfair Display", Georgia, serif; }
    .about-page .tone-rose { color: var(--rose); }
    .about-page .tone-teal { color: var(--teal); }
    .about-page .tone-muted { color: var(--muted); }
    .about-page .bg-tone-cream { background-color: var(--cream); }
    .about-page .bg-tone-rose { background-color: var(--rose); }
    .about-page .bg-tone-teal { background-color: var(--teal); }
    .about-page .card { background: #fff; border: 1px solid rgba(230, 116, 142, 0.18); border-radius: 1rem; }
    .about-page .chip { border: 1px solid rgba(107, 177, 173, 0.45); color: var(--ink); background: #fff; }
    .about-page a.link { color: var(--rose); text-decoration: underline; text-underline-offset: 2px; }
    .about-page a.link:hover { color: var(--teal); }
</style>

<div class="about-page px-4 pt-24 pb-12 sm:px-6">
    <div class="mx-auto max-w-5xl">

        {{-- Pembuka --}}
        <header class="bg-tone-cream rounded-2xl px-6 py-8 sm:px-10 sm:py-10 mb-8">
            <p class="text-xs font-bold uppercase tracking-wider tone-teal mb-2">Tentang & Tim</p>
            <h1 class="text-3xl sm:text-4xl font-bold tone-rose mb-3">Aphrodite</h1>
            <p class="text-base sm:text-lg max-w-3xl leading-relaxed">
                Aplikasi web rekomendasi skincare berbasis Semantic Web, dibuat oleh lima anggota tim dalam Tugas Besar Web Semantik.
            </p>
        </header>

        {{-- A. Tujuan --}}
        <section id="tujuan" class="card p-6 sm:p-8 mb-6" aria-labelledby="tujuan-title">
            <h2 id="tujuan-title" class="text-2xl font-bold mb-3">Tujuan Aphrodite</h2>
            <p class="leading-relaxed mb-3">
                Aphrodite membantu pengguna memahami kebutuhan kulitnya, yaitu jenis kulit, keluhan yang ingin diatasi, dan kelompok usia,
                lalu menemukan produk skincare yang relevan beserta alasan mengapa produk itu direkomendasikan.
            </p>
            <p class="leading-relaxed tone-muted">
                Pengetahuan tentang produk, bahan aktif, dan keterkaitannya disimpan sebagai ontologi, sehingga setiap rekomendasi dapat
                ditelusuri kembali ke data dan relasi yang mendasarinya, misalnya lewat
                <a href="{{ route('ontology.dashboard') }}" class="link">Ontology Explorer</a>.
            </p>
        </section>

        {{-- B. Metodologi --}}
        <section id="metodologi" class="card p-6 sm:p-8 mb-6" aria-labelledby="metodologi-title">
            <h2 id="metodologi-title" class="text-2xl font-bold mb-4">Metodologi</h2>
            <ol class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                @foreach($steps as $i => $step)
                    <li class="rounded-xl border border-gray-200 p-4">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="bg-tone-teal text-white text-sm font-bold rounded-full w-7 h-7 flex items-center justify-center flex-shrink-0">{{ $i + 1 }}</span>
                            <h3 class="font-bold">{{ $step['title'] }}</h3>
                        </div>
                        <p class="text-sm leading-relaxed tone-muted">{{ $step['body'] }}</p>
                    </li>
                @endforeach
            </ol>
            <h3 class="text-sm font-bold uppercase tracking-wider tone-muted mb-2">Teknologi yang digunakan</h3>
            <ul class="flex flex-wrap gap-2">
                @foreach($stack as $tech)
                    <li class="chip rounded-full px-3 py-1 text-sm">{{ $tech }}</li>
                @endforeach
            </ul>
        </section>

        {{-- C. Sumber Data --}}
        <section id="sumber-data" class="card p-6 sm:p-8 mb-6" aria-labelledby="sumber-title">
            <h2 id="sumber-title" class="text-2xl font-bold mb-3">Sumber Data</h2>
            <p class="leading-relaxed mb-5">
                Katalog berisi <strong>88 produk</strong> dari <strong>6 merek</strong>: {{ implode(', ', $brands) }}.
                Semua data dapat diperiksa langsung pada file ontologi yang dipakai aplikasi.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl border border-gray-200 p-4">
                    <h3 class="font-bold mb-2 tone-teal">Dapat diperiksa</h3>
                    <ul class="list-disc pl-5 space-y-2 text-sm leading-relaxed">
                        <li>
                            File ontologi <span class="font-mono text-xs">skincare_ontology_clean.ttl</span>
                            (<a href="{{ route('ontology.download') }}" class="link">unduh</a>), juga dapat dijelajahi lewat
                            <a href="{{ route('ontology.dashboard') }}" class="link">Ontology Explorer</a>.
                        </li>
                        <li>
                            Setiap produk menyimpan status verifikasi (<span class="font-mono text-xs">ex:verificationStatus</span>):
                            <strong>50 produk</strong> dicatat terverifikasi dari situs resmi merek dan <strong>38 produk</strong> dari retailer.
                        </li>
                        <li>Hasil audit kualitas data A1–A10 dan changelog perbaikan tersimpan sebagai laporan di repository proyek.</li>
                    </ul>
                </div>
                <div class="rounded-xl border border-gray-200 p-4">
                    <h3 class="font-bold mb-2 tone-rose">Keterbatasan Data</h3>
                    <ul class="list-disc pl-5 space-y-2 text-sm leading-relaxed">
                        <li>Status verifikasi adalah catatan tim; tautan halaman sumber per produk tidak disimpan di ontologi.</li>
                        <li>3 produk masih memiliki catatan bahwa detail kemasan atau daftar bahan lengkap perlu dicek.</li>
                        <li>Spreadsheet awal yang menjadi asal data tidak tersedia di repository.</li>
                        <li>Teen Lock adalah aturan proyek, bukan klaim medis atau rekomendasi resmi produsen.</li>
                    </ul>
                </div>
            </div>
            <p class="text-xs tone-muted mt-4">
                Informasi di Aphrodite bersifat edukatif dan bukan pengganti konsultasi dengan dokter atau tenaga kesehatan.
            </p>
        </section>

        {{-- D. Anggota Tim --}}
        <section id="tim" class="card p-6 sm:p-8" aria-labelledby="tim-title">
            <h2 id="tim-title" class="text-2xl font-bold mb-4">Anggota Tim</h2>
            <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($team as $i => $member)
                    <li class="flex items-center gap-4 rounded-xl border border-gray-200 p-4">
                        <span class="{{ $i % 2 === 0 ? 'bg-tone-rose' : 'bg-tone-teal' }} text-white font-bold rounded-full w-12 h-12 flex items-center justify-center flex-shrink-0" aria-hidden="true">{{ $initials($member['name']) }}</span>
                        <div>
                            <p class="font-bold leading-tight">{{ $member['name'] }}</p>
                            <p class="text-sm tone-muted">{{ $member['role'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

    </div>
</div>
@endsection
