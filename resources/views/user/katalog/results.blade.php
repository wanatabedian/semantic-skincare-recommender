{{--
    Area hasil katalog (Orang 5): chip filter aktif + grid + pager / empty state / error state.
    Isi elemen #katalog-results ini yang ditukar oleh katalog.js setiap filter/sort/halaman berubah.
    Parameter: $products, $error, $chips, $hasFilters, $search, $resetUrl, $pagination.
--}}
@include('user.katalog.active-filters')

@if ($error)
    <div class="mt-8">
        @include('user.katalog.empty-state', [
            'tone' => 'alert',
            'title' => 'Data produk belum dapat dimuat',
            'message' => $error,
            'actions' => [
                ['label' => 'Muat ulang', 'href' => request()->fullUrl(), 'primary' => true],
                ['label' => 'Ke beranda', 'href' => url('/')],
            ],
        ])
    </div>
@elseif (count($products) > 0)
    <div id="katalog-grid" class="k-grid mt-8">
        @foreach ($products as $product)
            @include('user.katalog.product-card', ['product' => $product, 'index' => $loop->index])
        @endforeach
    </div>

    @include('user.katalog.pagination')
@elseif ($hasFilters)
    <div class="mt-8">
        @include('user.katalog.empty-state', [
            'title' => 'Belum ada produk yang cocok',
            'message' => $search !== ''
                ? 'Tidak ada produk dengan nama memuat “'.$search.'” untuk kombinasi filter ini. Coba kata kunci lain atau longgarkan beberapa filter.'
                : 'Kombinasi filter ini belum memiliki produk di ontologi. Coba hapus salah satu filter di atas.',
            'actions' => [
                ['label' => 'Reset semua filter', 'href' => $resetUrl, 'primary' => true, 'katalog' => true],
            ],
        ])
    </div>
@else
    <div class="mt-8">
        @include('user.katalog.empty-state', [
            'title' => 'Belum ada produk di ontologi',
            'message' => 'Dataset Aphrodite yang dimuat di Fuseki belum berisi produk. Sambil menunggu, cari tahu produk yang cocok lewat kuis analisis kulit.',
            'actions' => [
                ['label' => 'Mulai kuis →', 'href' => route('kuis', ['new' => 1]), 'primary' => true],
                ['label' => 'Jelajahi katalog', 'href' => route('katalog')],
            ],
        ])
    </div>
@endif
