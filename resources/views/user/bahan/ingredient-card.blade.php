{{--
    Card bahan aktif di direktori /bahan (Orang 5).
    Parameter: $ingredient (IngredientController::ingredients()), $max (jumlah produk terbanyak, untuk skala bar), $index.
--}}
@php($share = $max > 0 ? max(6, round($ingredient['products'] / $max * 100)) : 0)
<li class="b-card" data-ingredient="{{ $ingredient['id'] }}" data-search="{{ $ingredient['search'] }}"
    style="--i: {{ min($index, 8) }}" @if (! $ingredient['match']) hidden @endif>
    <a href="{{ $ingredient['url'] }}" class="b-card__link">
        <span class="b-card__mark" aria-hidden="true">{{ mb_substr($ingredient['name'], 0, 1) }}</span>
        <span class="b-card__body">
            <span class="b-card__name">{{ $ingredient['name'] }}</span>
            <span class="b-card__meta">
                <span class="b-card__count">
                    Dipakai di <strong class="tabular-nums">{{ $ingredient['products'] }}</strong> produk
                </span>
                <span class="b-card__bar" aria-hidden="true"><span style="width: {{ $share }}%"></span></span>
            </span>
        </span>
        <span class="b-card__arrow" aria-hidden="true">→</span>
    </a>
</li>
