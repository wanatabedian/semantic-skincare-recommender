@extends('layouts.app')

@section('title', 'Kelas: ' . ($classMeta['label'] ?? $class))

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
            <li>
                <div class="flex items-center">
                    <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-400">Kelas</span>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-700 font-medium">{{ $class }}</span>
                </div>
            </li>
        </ol>
    </nav>

    {{-- Class Header Card --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold" style="background-color: {{ ($classMeta['color'] ?? '#6366f1') . '20' }}; color: {{ $classMeta['color'] ?? '#6366f1' }}; border: 1px solid {{ ($classMeta['color'] ?? '#6366f1') . '40' }};">
                        <span class="w-2 h-2 rounded-full mr-2" style="background-color: {{ $classMeta['color'] ?? '#6366f1' }};"></span>
                        {{ $class }}
                    </span>
                    <span class="text-xs text-gray-500 font-medium bg-gray-100 px-2.5 py-0.5 rounded-full">
                        {{ number_format($individuals->total()) }} Individu Terdaftar
                    </span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $classMeta['label'] ?? $class }}</h1>
                <p class="text-sm text-gray-600 mt-1">{{ $classMeta['description'] ?? 'Daftar individu yang terklasifikasi dalam kelas ini pada dataset aphrodite-clean.' }}</p>
            </div>
            <div>
                <a href="{{ route('ontology.dashboard') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm">
                    &larr; Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>

    {{-- Filter & Search Form --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-6">
        <form method="GET" action="{{ route('ontology.class', ['class' => $class]) }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Cari individu berdasarkan nama, label, atau IRI..."
                    class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                />
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded-lg text-sm transition-colors shadow-sm">
                    Cari
                </button>
                @if(!empty($search))
                    <a href="{{ route('ontology.class', ['class' => $class]) }}" class="w-full sm:w-auto bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-4 py-2 rounded-lg text-sm transition-colors text-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        @if(!empty($search))
            <div class="mt-3 text-xs text-gray-500 flex items-center">
                <span>Hasil pencarian untuk kata kunci <strong class="text-gray-800">"{{ $search }}"</strong>: ditemukan <strong>{{ $individuals->total() }}</strong> individu.</span>
            </div>
        @endif
    </div>

    {{-- Individuals Table Card --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mb-6">
        @if($individuals->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-semibold w-16">No.</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold">Nama / Label Individu</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold">IRI Lengkap</th>
                            <th scope="col" class="px-4 py-3 text-left font-semibold">Deskripsi / Komentar</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($individuals as $index => $row)
                            @php
                                $iri = $row['instance']['value'] ?? '';
                                $label = $row['label']['value'] ?? ($row['prodName']['value'] ?? ($row['rdfsLabel']['value'] ?? $iri));
                                $comment = $row['comment']['value'] ?? null;
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-gray-500 font-mono text-xs">
                                    {{ ($individuals->currentPage() - 1) * $individuals->perPage() + $index + 1 }}
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('ontology.individual', ['iri' => urlencode($iri)]) }}" class="font-medium text-indigo-700 hover:text-indigo-900 transition-colors">
                                        {{ $label }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs text-gray-500 break-all select-all">{{ $iri }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600">
                                    {{ $comment ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('ontology.individual', ['iri' => urlencode($iri)]) }}" class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-md transition-colors border border-indigo-200">
                                        Detail &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination Footer --}}
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 sm:flex sm:items-center sm:justify-between">
                <div class="text-xs text-gray-500 mb-2 sm:mb-0">
                    Menampilkan <strong>{{ $individuals->firstItem() ?? 0 }}</strong> - <strong>{{ $individuals->lastItem() ?? 0 }}</strong> dari <strong>{{ $individuals->total() }}</strong> individu
                </div>
                <div>
                    {{ $individuals->links() }}
                </div>
            </div>
        @else
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-base font-semibold text-gray-900 mb-1">Tidak Ada Individu Ditemukan</h3>
                <p class="text-sm text-gray-500 max-w-sm mx-auto mb-4">
                    @if(!empty($search))
                        Tidak ada data individu kelas {{ $class }} yang cocok dengan kata kunci pencarian "{{ $search }}".
                    @else
                        Belum ada individu yang terdaftar untuk kelas {{ $class }} dalam ontology.
                    @endif
                </p>
                @if(!empty($search))
                    <a href="{{ route('ontology.class', ['class' => $class]) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors border border-indigo-200">
                        Hapus Filter Pencarian
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
