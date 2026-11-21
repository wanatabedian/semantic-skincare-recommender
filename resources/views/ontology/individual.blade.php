@extends('layouts.app')

@section('title', 'Detail Individu: ' . $label)

@section('content')
<div class="container mx-auto py-8 px-4 max-w-7xl">
    {{-- Breadcrumb Navigation --}}
    <nav class="flex text-sm text-gray-500 mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-2">
            <li class="inline-flex items-center">
                <a href="{{ route('ontology.dashboard') }}" class="text-indigo-600 hover:text-indigo-800 transition-colors">
                    Dashboard Ontology
                </a>
            </li>
            @if($mainClass)
                <li>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ route('ontology.class', ['class' => $mainClass]) }}" class="text-indigo-600 hover:text-indigo-800 transition-colors">
                            Kelas: {{ $mainClass }}
                        </a>
                    </div>
                </li>
            @endif
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-700 font-medium truncate max-w-xs md:max-w-md">{{ $label }}</span>
                </div>
            </li>
        </ol>
    </nav>

    {{-- Main Header Card --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm mb-6">
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    @foreach($types as $type)
                        @if(array_key_exists($type, \App\Http\Controllers\OntologyController::ALLOWED_CLASSES))
                            <a href="{{ route('ontology.class', ['class' => $type]) }}" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 transition-colors">
                                Kelas: {{ $type }}
                            </a>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                {{ $type }}
                            </span>
                        @endif
                    @endforeach
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">{{ $label }}</h1>
                <div class="flex items-center text-xs text-gray-500 font-mono bg-gray-50 p-2 rounded-lg border border-gray-200 max-w-3xl break-all">
                    <span class="font-semibold text-gray-400 mr-2 select-none">IRI:</span>
                    <span class="select-all">{{ $iri }}</span>
                </div>
                @if($description)
                    <p class="mt-3 text-sm text-gray-600 bg-amber-50 border-l-4 border-amber-400 p-3 rounded-r-lg">
                        <strong>Keterangan:</strong> {{ $description }}
                    </p>
                @endif
            </div>

            <div class="flex flex-wrap gap-2">
                @if($mainClass)
                    <a href="{{ route('ontology.class', ['class' => $mainClass]) }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm">
                        &larr; Daftar {{ $mainClass }}
                    </a>
                @endif
                <a href="{{ route('ontology.dashboard') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors shadow-sm">
                    Dashboard
                </a>
            </div>
        </div>
    </div>

    {{-- KHUSUS INDIVIDU PRODUCT: Kartu Informasi Terstruktur --}}
    @if($isProduct)
        <div class="bg-white border border-emerald-200 rounded-xl p-6 shadow-sm mb-6">
            <div class="flex items-center space-x-2 border-b border-gray-100 pb-3 mb-4">
                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                <h2 class="text-lg font-bold text-gray-900">Informasi Terstruktur Produk</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-6 text-sm">
                {{-- 1. Nama Produk --}}
                <div class="border-b border-gray-100 pb-2">
                    <span class="text-xs font-semibold text-gray-500 block uppercase tracking-wider">Nama Produk</span>
                    <span class="font-medium text-gray-900">{{ $productData['productName'] ?? 'Tidak tersedia' }}</span>
                </div>

                {{-- 2. Brand --}}
                <div class="border-b border-gray-100 pb-2">
                    <span class="text-xs font-semibold text-gray-500 block uppercase tracking-wider">Brand</span>
                    @if(!empty($productData['brand']))
                        <a href="{{ route('ontology.individual', ['iri' => urlencode($productData['brand']['iri'])]) }}" class="font-medium text-indigo-600 hover:text-indigo-800 underline">
                            {{ $productData['brand']['label'] }}
                        </a>
                    @else
                        <span class="text-gray-400 italic">Tidak tersedia</span>
                    @endif
                </div>

                {{-- 3. Kategori --}}
                <div class="border-b border-gray-100 pb-2">
                    <span class="text-xs font-semibold text-gray-500 block uppercase tracking-wider">Kategori</span>
                    @if(!empty($productData['category']))
                        <a href="{{ route('ontology.individual', ['iri' => urlencode($productData['category']['iri'])]) }}" class="font-medium text-indigo-600 hover:text-indigo-800 underline">
                            {{ $productData['category']['label'] }}
                        </a>
                    @else
                        <span class="text-gray-400 italic">Tidak tersedia</span>
                    @endif
                </div>

                {{-- 4. Product Series --}}
                <div class="border-b border-gray-100 pb-2">
                    <span class="text-xs font-semibold text-gray-500 block uppercase tracking-wider">Product Series</span>
                    @if(!empty($productData['series']))
                        <a href="{{ route('ontology.individual', ['iri' => urlencode($productData['series']['iri'])]) }}" class="font-medium text-indigo-600 hover:text-indigo-800 underline">
                            {{ $productData['series']['label'] }}
                        </a>
                    @else
                        <span class="text-gray-400 italic">Tidak tersedia</span>
                    @endif
                </div>

                {{-- 5. Kelompok Usia (Age Group) --}}
                <div class="border-b border-gray-100 pb-2">
                    <span class="text-xs font-semibold text-gray-500 block uppercase tracking-wider mb-1">Target Kelompok Usia</span>
                    @if(!empty($productData['ageGroups']) && count($productData['ageGroups']) > 0)
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($productData['ageGroups'] as $ag)
                                <a href="{{ route('ontology.individual', ['iri' => urlencode($ag['iri'])]) }}" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100">
                                    {{ $ag['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <span class="text-gray-400 italic">Tidak tersedia</span>
                    @endif
                </div>

                {{-- 6. Jenis Kulit (Skin Type) --}}
                <div class="border-b border-gray-100 pb-2">
                    <span class="text-xs font-semibold text-gray-500 block uppercase tracking-wider mb-1">Kesesuaian Jenis Kulit</span>
                    @if(!empty($productData['skinTypes']) && count($productData['skinTypes']) > 0)
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($productData['skinTypes'] as $st)
                                <a href="{{ route('ontology.individual', ['iri' => urlencode($st['iri'])]) }}" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-50 text-yellow-800 border border-yellow-200 hover:bg-yellow-100">
                                    {{ $st['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <span class="text-gray-400 italic">Tidak tersedia</span>
                    @endif
                </div>

                {{-- 7. Masalah Kulit (Skin Concern) --}}
                <div class="border-b border-gray-100 pb-2">
                    <span class="text-xs font-semibold text-gray-500 block uppercase tracking-wider mb-1">Masalah Kulit Ditargetkan</span>
                    @if(!empty($productData['concerns']) && count($productData['concerns']) > 0)
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($productData['concerns'] as $sc)
                                <a href="{{ route('ontology.individual', ['iri' => urlencode($sc['iri'])]) }}" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-pink-50 text-pink-700 border border-pink-200 hover:bg-pink-100">
                                    {{ $sc['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <span class="text-gray-400 italic">Tidak tersedia</span>
                    @endif
                </div>

                {{-- 8. Ingredients (Bahan Aktif) --}}
                <div class="border-b border-gray-100 pb-2">
                    <span class="text-xs font-semibold text-gray-500 block uppercase tracking-wider mb-1">Bahan Aktif Utama</span>
                    @if(!empty($productData['ingredients']) && count($productData['ingredients']) > 0)
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($productData['ingredients'] as $ing)
                                <a href="{{ route('ontology.individual', ['iri' => urlencode($ing['iri'])]) }}" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200 hover:bg-gray-200">
                                    {{ $ing['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <span class="text-gray-400 italic">Tidak tersedia</span>
                    @endif
                </div>

                {{-- 9. Perkiraan Harga --}}
                <div class="border-b border-gray-100 pb-2">
                    <span class="text-xs font-semibold text-gray-500 block uppercase tracking-wider">Perkiraan Harga</span>
                    @if(!empty($productData['price']))
                        <span class="font-bold text-emerald-700 text-base">
                            Rp {{ number_format($productData['price'], 0, ',', '.') }}
                        </span>
                    @else
                        <span class="text-gray-400 italic">Tidak tersedia</span>
                    @endif
                </div>

                {{-- 10. Status Verifikasi & ID Sumber --}}
                <div class="border-b border-gray-100 pb-2">
                    <span class="text-xs font-semibold text-gray-500 block uppercase tracking-wider">Status Verifikasi</span>
                    @if(!empty($productData['verificationStatus']))
                        <span class="inline-flex items-center text-xs font-medium text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                            {{ $productData['verificationStatus'] }}
                        </span>
                    @else
                        <span class="text-gray-400 italic">Tidak tersedia</span>
                    @endif
                    @if(!empty($productData['sourceProductId']))
                        <span class="block text-xs text-gray-400 mt-1 font-mono">ID: {{ $productData['sourceProductId'] }}</span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Object Properties (Relasi ke Individu Lain) --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900">Object Properties (Relasi ke Individu Lain)</h2>
                <p class="text-xs text-gray-500">Relasi yang menghubungkan individu ini ke individu lain dalam graf pengetahuan.</p>
            </div>
            <span class="text-xs font-semibold bg-indigo-100 text-indigo-800 px-2.5 py-0.5 rounded-full">
                {{ count($objectProperties) }} Relasi
            </span>
        </div>

        @if(count($objectProperties) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left font-semibold">Properti Relasi</th>
                            <th scope="col" class="px-6 py-3 text-left font-semibold">Target Individu</th>
                            <th scope="col" class="px-6 py-3 text-left font-semibold">Label Target</th>
                            <th scope="col" class="px-6 py-3 text-center font-semibold w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($objectProperties as $op)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-3">
                                    <span class="font-mono text-xs font-semibold text-indigo-700">ex:{{ $op['propertyLabel'] }}</span>
                                    <span class="block text-xs text-gray-400 font-mono">{{ $op['property'] }}</span>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="font-mono text-xs text-gray-600 break-all select-all">{{ $op['targetIri'] }}</span>
                                </td>
                                <td class="px-6 py-3 font-medium text-gray-900">
                                    {{ $op['targetLabel'] }}
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <a href="{{ route('ontology.individual', ['iri' => urlencode($op['targetIri'])]) }}" class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-md transition-colors border border-indigo-200">
                                        Buka &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6 text-center text-sm text-gray-500 italic">
                Tidak ada properti relasi objek keluar yang terdefinisi untuk individu ini.
            </div>
        @endif
    </div>

    {{-- Datatype Properties (Properti Nilai Literal) --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900">Datatype Properties (Nilai Literal)</h2>
                <p class="text-xs text-gray-500">Nilai atribut data (teks, angka, status) yang melekat langsung pada individu.</p>
            </div>
            <span class="text-xs font-semibold bg-gray-200 text-gray-800 px-2.5 py-0.5 rounded-full">
                {{ count($datatypeProperties) }} Atribut
            </span>
        </div>

        @if(count($datatypeProperties) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left font-semibold w-1/3">Nama Properti</th>
                            <th scope="col" class="px-6 py-3 text-left font-semibold">Nilai Literal</th>
                            <th scope="col" class="px-6 py-3 text-left font-semibold w-40">Tipe Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($datatypeProperties as $dp)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-3">
                                    <span class="font-mono text-xs font-semibold text-gray-800">ex:{{ $dp['propertyLabel'] }}</span>
                                    <span class="block text-xs text-gray-400 font-mono">{{ $dp['property'] }}</span>
                                </td>
                                <td class="px-6 py-3 text-gray-900 font-medium break-all">
                                    {{ $dp['value'] }}
                                </td>
                                <td class="px-6 py-3 text-xs font-mono text-gray-500">
                                    {{ $dp['datatype'] ? basename($dp['datatype']) : 'string' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6 text-center text-sm text-gray-500 italic">
                Tidak ada datatype properties (literal) untuk individu ini.
            </div>
        @endif
    </div>

    {{-- Raw Triples Collapsible for Transparency --}}
    <details class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 mb-6">
        <summary class="text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:text-gray-700">
            Lihat Raw Triples RDF ({{ count($triples) }} triple)
        </summary>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-xs font-mono divide-y divide-gray-200">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 text-left">Predicate (?p)</th>
                        <th class="px-3 py-2 text-left">Object (?o)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($triples as $t)
                        <tr>
                            <td class="px-3 py-2 text-indigo-700">{{ $t['p']['value'] ?? '' }}</td>
                            <td class="px-3 py-2 text-gray-800 break-all">{{ $t['o']['value'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</div>
@endsection
