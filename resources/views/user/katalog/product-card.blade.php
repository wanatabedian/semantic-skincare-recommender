{{--
    Product card katalog (Orang 5). Belum ada komponen card bersama dari Orang 2,
    jadi card ini khusus halaman katalog.
    Parameter: $product (dari CatalogController::products()).
--}}
@php
    $tag = $product['detail_url'] ? 'a' : 'div';
    $skins = array_slice($product['skin_types'], 0, 2);
    $concerns = array_slice($product['concerns'], 0, 2);
    $initial = mb_strtoupper(mb_substr($product['brand'] ?? $product['name'], 0, 1));
@endphp

<article class="k-card" data-product-id="{{ $product['id'] }}">
    <{{ $tag }} @if ($product['detail_url']) href="{{ $product['detail_url'] }}" @endif class="k-card__link">
        <div class="k-card__media">
            @if ($product['image'])
                <img src="{{ $product['image'] }}"
                     alt="Foto {{ $product['name'] }}"
                     loading="lazy" decoding="async"
                     class="k-card__img"
                     onerror="this.nextElementSibling.hidden = false; this.remove();">
            @endif

            {{-- Fallback elegan bila foto belum tersedia --}}
            <div class="k-card__fallback" @if ($product['image']) hidden @endif>
                <span class="k-card__initial" aria-hidden="true">{{ $initial }}</span>
                <svg class="k-card__bottle" viewBox="0 0 40 64" fill="none" aria-hidden="true">
                    <rect x="6" y="22" width="28" height="40" rx="9" stroke="currentColor" stroke-width="1.4"/>
                    <rect x="13" y="10" width="14" height="13" rx="3" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M20 10V2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                </svg>
                <span class="k-card__note">Foto belum tersedia</span>
            </div>

            @if ($product['category'])
                <span class="k-card__badge">{{ $product['category'] }}</span>
            @endif
        </div>

        <div class="k-card__body">
            <p class="k-card__brand">{{ $product['brand'] ?? 'Brand belum tercatat' }}</p>
            <h3 class="k-card__name">{{ $product['name'] }}</h3>

            @if ($skins || $concerns)
                <ul class="k-card__tags" aria-label="Kecocokan">
                    @foreach ($skins as $skin)
                        <li class="k-tag k-tag--skin">{{ $skin }}</li>
                    @endforeach
                    @foreach ($concerns as $concern)
                        <li class="k-tag">{{ $concern }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="k-card__foot">
                @if ($product['price'] !== null)
                    <p class="k-card__price">
                        <span class="sr-only">Estimasi harga </span>Rp{{ number_format($product['price'], 0, ',', '.') }}
                        <span class="k-card__est" aria-hidden="true">estimasi</span>
                    </p>
                @else
                    <p class="k-card__price k-card__price--none">Harga belum tercatat</p>
                @endif

                @if ($product['detail_url'])
                    <span class="k-card__cta" aria-hidden="true">Detail →</span>
                @endif
            </div>
        </div>
    </{{ $tag }}>
</article>
