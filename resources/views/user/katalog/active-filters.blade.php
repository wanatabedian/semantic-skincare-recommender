{{--
    Chip filter aktif (Orang 5). Setiap chip menghapus SATU filter; "Hapus semua" mereset katalog.
    Parameter: $chips (dari CatalogController::chips()), $resetUrl.
--}}
@if (!empty($chips))
    <div class="k-chips" aria-label="Filter aktif">
        <ul class="flex flex-wrap items-center gap-2">
            @foreach ($chips as $chip)
                <li class="k-chip-wrap" style="--i: {{ min($loop->index, 10) }}">
                    <a href="{{ $chip['url'] }}" class="k-chip" data-katalog-link
                       aria-label="Hapus filter {{ $chip['group'] }}: {{ $chip['label'] }}">
                        <span class="k-chip__group">{{ $chip['group'] }}</span>
                        <span class="k-chip__label">{{ $chip['label'] }}</span>
                        <span class="k-chip__x" aria-hidden="true">
                            <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 3l6 6M9 3 3 9" stroke-linecap="round"/></svg>
                        </span>
                    </a>
                </li>
            @endforeach
            <li>
                <a href="{{ $resetUrl }}" class="k-chips__reset" data-katalog-link>Hapus semua</a>
            </li>
        </ul>
    </div>
@endif
