@extends('layouts.app')

@section('title', 'Ontology Explorer')

@section('content')
<div class="container mx-auto py-6">
    <h1 class="text-2xl font-bold mb-4">Ontology Explorer</h1>

    {{-- A. Dashboard Statistik Ontology --}}
    <section class="mb-8">
        <h2 class="text-xl font-semibold mb-2">Statistik Ontology</h2>
        <table class="table-auto w-full border-collapse">
            <tbody>
                <tr><td class="border px-2 py-1">Total triple RDF</td><td class="border px-2 py-1">{{ $stats['triples'] }}</td></tr>
                <tr><td class="border px-2 py-1">Total produk</td><td class="border px-2 py-1">{{ $stats['products'] }}</td></tr>
                <tr><td class="border px-2 py-1">Total kelas ontology</td><td class="border px-2 py-1">{{ $stats['classes'] }}</td></tr>
                @foreach($stats['individuals'] as $class => $count)
                <tr><td class="border px-2 py-1">Individu {{ $class }}</td><td class="border px-2 py-1">{{ $count }}</td></tr>
                @endforeach
                <tr><td class="border px-2 py-1 font-semibold">Total individu (8 kelas utama)</td><td class="border px-2 py-1 font-semibold">{{ $stats['totalIndividuals'] }}</td></tr>
                <tr><td class="border px-2 py-1">Object properties</td><td class="border px-2 py-1">{{ $stats['objectProperties'] }}</td></tr>
                <tr><td class="border px-2 py-1">Datatype properties</td><td class="border px-2 py-1">{{ $stats['datatypeProperties'] }}</td></tr>
            </tbody>
        </table>
    </section>

    {{-- B. Relasi Antar Kelas (Domain–Range) --}}
    <section class="mb-8">
        <h2 class="text-xl font-semibold mb-3">Relasi Antar Kelas (Domain–Range)</h2>
        <p class="text-sm text-gray-600 mb-4">
            Daftar relasi (object property) yang menghubungkan kelas dalam ontology beserta batasan domain (kelas asal) dan range (kelas tujuan).
        </p>

        @php
            $formatIri = function($iri) {
                if (empty($iri)) {
                    return null;
                }
                if (str_contains($iri, '#')) {
                    return explode('#', $iri)[1];
                }
                return basename($iri);
            };
        @endphp

        <div class="overflow-x-auto shadow-sm rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 bg-white text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left font-semibold w-16">No.</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold">Nama Object Property</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold">Domain (kelas asal)</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold">Range (kelas tujuan)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($hierarchy as $index => $item)
                        @php
                            $propName = $formatIri($item['property'] ?? '');
                            $domainName = $formatIri($item['domain'] ?? '');
                            $rangeName = $formatIri($item['range'] ?? '');
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-4 py-3">
                                @if($propName)
                                    <span class="font-mono font-medium text-indigo-700">ex:{{ $propName }}</span>
                                    @if(!empty($item['property']))
                                        <span class="block text-xs text-gray-400 font-mono">{{ $item['property'] }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-400 italic">Tidak didefinisikan</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($domainName)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                        ex:{{ $domainName }}
                                    </span>
                                @else
                                    <span class="text-gray-400 italic">Tidak didefinisikan</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($rangeName)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        ex:{{ $rangeName }}
                                    </span>
                                @else
                                    <span class="text-gray-400 italic">Tidak didefinisikan</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500 italic">
                                Tidak ada data relasi object property yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- C. Interactive Knowledge Graph --}}
    <section class="mb-8">
        <h2 class="text-xl font-semibold mb-3">Graf Pengetahuan Interaktif</h2>
        <p class="text-sm text-gray-600 mb-3">
            Pilih satu produk untuk melihat produk itu beserta semua relasi langsungnya (merek, kategori, seri, kelompok usia, jenis kulit, keluhan, dan bahan), atau biarkan kosong untuk ringkasan individu dari 8 kelas utama.
        </p>
        <div class="mb-2">
            <label for="graph-product" class="block text-xs font-semibold text-gray-600 mb-1">Produk</label>
            <select id="graph-product" class="w-full md:w-2/3 border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <option value="">Ringkasan semua kelas (tanpa produk terpilih)</option>
                @foreach($graphProducts as $gp)
                    <option value="{{ $gp['id'] }}">{{ $gp['id'] }} — {{ $gp['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-wrap items-center gap-2 mb-3">
            <button id="loadGraph" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded text-sm font-medium transition-colors shadow-sm">Muat Graf</button>
            <button id="resetGraph" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1.5 rounded text-sm font-medium transition-colors shadow-sm">Reset Tampilan</button>
            <button id="clearGraph" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-sm font-medium transition-colors shadow-sm">Kosongkan Graf</button>
            <input id="graph-search" type="text" placeholder="Cari node…" class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <select id="graph-filter" class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <option value="">Semua Kelas</option>
                <option value="Product">Product</option>
                <option value="Brand">Brand</option>
                <option value="Category">Category</option>
                <option value="ProductSeries">ProductSeries</option>
                <option value="AgeGroup">AgeGroup</option>
                <option value="SkinType">SkinType</option>
                <option value="SkinConcern">SkinConcern</option>
                <option value="Ingredient">Ingredient</option>
            </select>
        </div>
        <div id="knowledge-graph" data-graph-url="{{ route('ontology.graph') }}" class="border border-gray-200 rounded-lg h-96 md:h-[520px] bg-white relative overflow-hidden shadow-inner flex items-center justify-center">
            <span class="text-gray-400 text-sm">Klik "Muat Graf" untuk memuat visualisasi Knowledge Graph.</span>
        </div>

        {{-- Informasi node yang diklik --}}
        <div id="graph-info" class="hidden mt-3 p-3 bg-white border border-indigo-200 rounded-lg text-sm" aria-live="polite">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Node terpilih</div>
            <div class="font-semibold text-gray-900" data-info="label"></div>
            <div class="text-xs text-gray-600 mt-1">Kelas: <span class="font-mono" data-info="class"></span> · Relasi tampil: <span data-info="degree"></span></div>
            <div class="text-xs text-gray-400 font-mono break-all mt-1" data-info="iri"></div>
            <div class="flex flex-wrap gap-2 mt-2">
                <a data-info="url" href="#" class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-md border border-indigo-200">Lihat detail individu &rarr;</a>
                <button type="button" data-info="focus" style="display: none;" class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-md border border-emerald-200">Tampilkan relasi produk ini</button>
            </div>
        </div>

        {{-- Legenda Kelas --}}
        <div class="mt-3 p-3 bg-gray-50 border border-gray-200 rounded-lg">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Legenda Kelas:</div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs">
                <div class="flex items-center space-x-1.5">
                    <span class="inline-block w-3.5 h-3.5 rounded-full shadow-sm" style="background-color: #2ca02c;"></span>
                    <span class="text-gray-700 font-medium">Product</span>
                </div>
                <div class="flex items-center space-x-1.5">
                    <span class="inline-block w-3.5 h-3.5 rounded-full shadow-sm" style="background-color: #1f77b4;"></span>
                    <span class="text-gray-700 font-medium">Brand</span>
                </div>
                <div class="flex items-center space-x-1.5">
                    <span class="inline-block w-3.5 h-3.5 rounded-full shadow-sm" style="background-color: #ff7f0e;"></span>
                    <span class="text-gray-700 font-medium">Category</span>
                </div>
                <div class="flex items-center space-x-1.5">
                    <span class="inline-block w-3.5 h-3.5 rounded-full shadow-sm" style="background-color: #d62728;"></span>
                    <span class="text-gray-700 font-medium">ProductSeries</span>
                </div>
                <div class="flex items-center space-x-1.5">
                    <span class="inline-block w-3.5 h-3.5 rounded-full shadow-sm" style="background-color: #9467bd;"></span>
                    <span class="text-gray-700 font-medium">AgeGroup</span>
                </div>
                <div class="flex items-center space-x-1.5">
                    <span class="inline-block w-3.5 h-3.5 rounded-full shadow-sm" style="background-color: #8c564b;"></span>
                    <span class="text-gray-700 font-medium">SkinType</span>
                </div>
                <div class="flex items-center space-x-1.5">
                    <span class="inline-block w-3.5 h-3.5 rounded-full shadow-sm" style="background-color: #e377c2;"></span>
                    <span class="text-gray-700 font-medium">SkinConcern</span>
                </div>
                <div class="flex items-center space-x-1.5">
                    <span class="inline-block w-3.5 h-3.5 rounded-full shadow-sm" style="background-color: #7f7f7f;"></span>
                    <span class="text-gray-700 font-medium">Ingredient</span>
                </div>
            </div>
        </div>
    </section>

    {{-- D. Class and Individual Explorer --}}
    <section class="mb-8">
        <div class="mb-3">
            <h2 class="text-xl font-semibold text-gray-800">Penjelajah Kelas & Individu</h2>
            <p class="text-sm text-gray-600">Pilih salah satu dari 8 kelas ontology utama untuk menjelajahi daftar individu dan detail propertinya secara terstruktur.</p>
        </div>

        @php
            $classDescriptions = [
                'Product' => ['label' => 'Product (Produk)', 'desc' => 'Daftar produk skincare dalam dataset', 'color' => '#2ca02c', 'bg' => 'bg-emerald-50 border-emerald-200 text-emerald-800'],
                'Brand' => ['label' => 'Brand (Merek)', 'desc' => 'Merek atau produsen produk kosmetik', 'color' => '#1f77b4', 'bg' => 'bg-blue-50 border-blue-200 text-blue-800'],
                'Category' => ['label' => 'Category (Kategori)', 'desc' => 'Kategori produk perawatan kulit', 'color' => '#ff7f0e', 'bg' => 'bg-amber-50 border-amber-200 text-amber-800'],
                'ProductSeries' => ['label' => 'ProductSeries (Seri)', 'desc' => 'Lini atau rangkaian seri produk', 'color' => '#d62728', 'bg' => 'bg-rose-50 border-rose-200 text-rose-800'],
                'AgeGroup' => ['label' => 'AgeGroup (Usia)', 'desc' => 'Target usia kesesuaian produk', 'color' => '#9467bd', 'bg' => 'bg-purple-50 border-purple-200 text-purple-800'],
                'SkinType' => ['label' => 'SkinType (Jenis Kulit)', 'desc' => 'Klasifikasi tipe kulit (Kering, Berminyak, dll)', 'color' => '#8c564b', 'bg' => 'bg-yellow-50 border-yellow-200 text-yellow-800'],
                'SkinConcern' => ['label' => 'SkinConcern (Masalah Kulit)', 'desc' => 'Target permasalahan kulit yang diatasi', 'color' => '#e377c2', 'bg' => 'bg-pink-50 border-pink-200 text-pink-800'],
                'Ingredient' => ['label' => 'Ingredient (Bahan Aktif)', 'desc' => 'Kandungan bahan aktif formulasi', 'color' => '#7f7f7f', 'bg' => 'bg-gray-100 border-gray-200 text-gray-800'],
            ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
            @foreach(['Product', 'Brand', 'Category', 'ProductSeries', 'AgeGroup', 'SkinType', 'SkinConcern', 'Ingredient'] as $cls)
                @php
                    $info = $classDescriptions[$cls];
                    $count = $stats['individuals'][$cls] ?? 0;
                @endphp
                <div class="border rounded-xl p-4 bg-white hover:shadow-md transition-shadow flex flex-col justify-between border-gray-200">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $info['bg'] }}">
                                <span class="w-2 h-2 rounded-full mr-1.5" style="background-color: {{ $info['color'] }}"></span>
                                {{ $cls }}
                            </span>
                            <span class="text-xs font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded-md">
                                {{ number_format($count) }} individu
                            </span>
                        </div>
                        <h3 class="font-semibold text-gray-900 text-base mb-1">{{ $info['label'] }}</h3>
                        <p class="text-xs text-gray-500 mb-4">{{ $info['desc'] }}</p>
                    </div>
                    <a href="{{ route('ontology.class', ['class' => $cls]) }}" class="inline-flex items-center justify-center w-full px-3 py-2 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors border border-indigo-200">
                        Jelajahi Individu &rarr;
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    {{-- E. SPARQL Playground --}}
    <section id="sparql-playground" class="mb-8">
        <h2 class="text-xl font-semibold mb-2">Playground SPARQL</h2>
        <p class="text-sm text-gray-600 mb-3">
            Hanya query <strong>SELECT</strong> dan <strong>ASK</strong> yang diterima, dijalankan pada dataset <span class="font-mono">aphrodite-clean</span>.
            Hasil maksimal {{ $playground['maxResults'] }} baris (query SELECT tanpa LIMIT otomatis diberi LIMIT {{ $playground['maxResults'] }}),
            batas waktu {{ $playground['timeout'] }} detik. Prefix <span class="font-mono">ex:</span> dan <span class="font-mono">rdfs:</span> sudah tersedia.
        </p>
        <form action="{{ route('ontology.sparql') }}" method="POST" class="space-y-2">
            @csrf
            <div>
                <label for="sparql-example" class="block text-xs font-semibold text-gray-600 mb-1">Contoh query</label>
                <select id="sparql-example" class="w-full md:w-2/3 border border-gray-300 rounded px-3 py-1.5 text-sm">
                    <option value="">— Pilih contoh untuk mengisi editor —</option>
                    @foreach($examples as $key => $example)
                        <option value="{{ $key }}" data-query="{{ $example['query'] }}">{{ $example['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <textarea id="sparql-query" name="query" class="w-full h-40 border p-2 font-mono text-sm" spellcheck="false" placeholder="SELECT ?s ?p ?o WHERE { ?s ?p ?o } LIMIT 10">{{ old('query') }}</textarea>
            <div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Jalankan Query</button>
            </div>
        </form>

        @if(session('sparqlError'))
            <div class="mt-3 p-3 bg-red-50 border border-red-200 text-red-700 rounded text-sm" role="alert">
                <span class="font-semibold">Query tidak dijalankan:</span> {{ session('sparqlError') }}
            </div>
        @endif

        @php $sparqlResult = session('sparqlResult'); @endphp
        @if(is_array($sparqlResult) && ($sparqlResult['type'] ?? null) === 'ask')
            <div class="mt-3 p-3 rounded border text-sm {{ $sparqlResult['boolean'] ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-gray-50 border-gray-200 text-gray-800' }}">
                Hasil ASK: <span class="font-mono font-semibold">{{ $sparqlResult['boolean'] ? 'true' : 'false' }}</span>
                — {{ $sparqlResult['boolean'] ? 'pola ditemukan di ontologi.' : 'pola tidak ditemukan di ontologi.' }}
            </div>
        @elseif(is_array($sparqlResult) && ($sparqlResult['type'] ?? null) === 'select')
            @php
                $vars = $sparqlResult['vars'];
                if ($vars === [] && ! empty($sparqlResult['rows'])) {
                    $vars = array_keys($sparqlResult['rows'][0]);
                }
            @endphp
            <div class="mt-3 text-xs text-gray-600">
                {{ count($sparqlResult['rows']) }} baris.
                @if($sparqlResult['limitApplied']) LIMIT {{ $playground['maxResults'] }} ditambahkan otomatis. @endif
                @if($sparqlResult['truncated']) Hasil dipotong menjadi {{ $playground['maxResults'] }} baris. @endif
            </div>
            <div class="mt-1 overflow-x-auto border border-gray-200 rounded-lg bg-white">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach($vars as $var)
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 font-mono">?{{ $var }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($sparqlResult['rows'] as $row)
                            <tr>
                                @foreach($vars as $var)
                                    <td class="px-3 py-1.5 align-top break-all">{{ $row[$var]['value'] ?? '' }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ max(count($vars), 1) }}" class="px-3 py-4 text-center text-gray-500 italic">Tidak ada hasil.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <script>
            (function () {
                var select = document.getElementById('sparql-example');
                var editor = document.getElementById('sparql-query');
                if (!select || !editor) return;
                select.addEventListener('change', function () {
                    var option = select.options[select.selectedIndex];
                    if (option && option.dataset.query) {
                        editor.value = option.dataset.query;
                        editor.focus();
                    }
                });
            })();
        </script>
    </section>

    {{-- F. Download Ontology --}}
    <section>
        <h2 class="text-xl font-semibold mb-2">Unduh Ontology</h2>
        <a href="{{ route('ontology.download') }}" class="bg-green-600 text-white px-4 py-2 rounded">Download skincare_ontology_clean.ttl</a>
    </section>
</div>
@endsection
