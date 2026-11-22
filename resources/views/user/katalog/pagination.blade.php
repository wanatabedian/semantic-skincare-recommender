{{--
    Pager katalog (Orang 5). Hanya dirender bila hasil > 12 produk.
    Setiap link membawa search, semua filter, dan sorting aktif (CatalogController::pagination()).
    Parameter: $pagination = ['page', 'last', 'items' => [[page, url, current] | [gap]], 'prev_url', 'next_url'].
--}}
@if ($pagination)
    <nav class="k-pager mt-12" aria-label="Halaman katalog">
        @if ($pagination['prev_url'])
            <a href="{{ $pagination['prev_url'] }}" class="k-pager__btn" rel="prev" data-katalog-link>
                <span class="k-pager__arrow" aria-hidden="true">←</span><span class="k-pager__label">Sebelumnya</span>
                <span class="sr-only">(halaman {{ $pagination['page'] - 1 }})</span>
            </a>
        @else
            <span class="k-pager__btn" aria-disabled="true">
                <span class="k-pager__arrow" aria-hidden="true">←</span><span class="k-pager__label">Sebelumnya</span>
            </span>
        @endif

        <ol class="k-pager__list">
            @foreach ($pagination['items'] as $item)
                @if (!empty($item['gap']))
                    <li class="k-pager__gap" aria-hidden="true">…</li>
                @elseif ($item['current'])
                    <li><span class="k-pager__num is-current" aria-current="page"><span class="sr-only">Halaman </span>{{ $item['page'] }}</span></li>
                @else
                    <li><a href="{{ $item['url'] }}" class="k-pager__num" data-katalog-link><span class="sr-only">Halaman </span>{{ $item['page'] }}</a></li>
                @endif
            @endforeach
        </ol>

        @if ($pagination['next_url'])
            <a href="{{ $pagination['next_url'] }}" class="k-pager__btn" rel="next" data-katalog-link>
                <span class="k-pager__label">Berikutnya</span><span class="k-pager__arrow" aria-hidden="true">→</span>
                <span class="sr-only">(halaman {{ $pagination['page'] + 1 }})</span>
            </a>
        @else
            <span class="k-pager__btn" aria-disabled="true">
                <span class="k-pager__label">Berikutnya</span><span class="k-pager__arrow" aria-hidden="true">→</span>
            </span>
        @endif
    </nav>
@endif
