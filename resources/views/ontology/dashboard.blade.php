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
        <div id="knowledge-graph" class="border border-gray-200 rounded-lg h-96 md:h-[520px] bg-white relative overflow-hidden shadow-inner flex items-center justify-center">
            <span class="text-gray-400 text-sm">Klik "Muat Graf" untuk memuat visualisasi Knowledge Graph.</span>
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
    <section class="mb-8">
        <h2 class="text-xl font-semibold mb-2">Playground SPARQL</h2>
        <form action="{{ route('ontology.sparql') }}" method="POST" class="space-y-2">
            @csrf
            <textarea name="query" class="w-full h-32 border p-2" placeholder="Masukkan query SELECT Anda di sini"></textarea>
            <div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Jalankan Query</button>
            </div>
        </form>
        @if(session('sparqlResult'))
            <pre class="bg-gray-100 p-2 mt-2">{{ json_encode(session('sparqlResult'), JSON_PRETTY_PRINT) }}</pre>
        @endif
        @if(session('sparqlError'))
            <div class="text-red-600 mt-2">Error: {{ session('sparqlError') }}</div>
        @endif
    </section>

    {{-- F. Download Ontology --}}
    <section>
        <h2 class="text-xl font-semibold mb-2">Unduh Ontology</h2>
        <a href="{{ route('ontology.download') }}" class="bg-green-600 text-white px-4 py-2 rounded">Download skincare_ontology_clean.ttl</a>
    </section>
</div>
@endsection
