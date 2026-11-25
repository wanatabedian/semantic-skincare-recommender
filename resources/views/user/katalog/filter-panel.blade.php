{{--
    Panel filter katalog (Orang 5). Opsi & jumlah berasal dari ontologi (CatalogController::facets()).
    Form GET biasa: tetap berfungsi tanpa JavaScript; katalog.js memperbaruinya tanpa muat ulang.
    Parameter: $facets, $error, $resetUrl.
--}}
<form id="katalog-form" action="{{ route('katalog') }}" method="GET" class="mt-6" data-katalog-form>
    {{-- Filter dari URL yang tidak punya panel di sidebar (mis. kategori dari landing page) tetap ikut terkirim. --}}
    @foreach ([...array_keys(\App\Http\Controllers\CatalogController::GROUPS), \App\Http\Controllers\CatalogController::PRICE_GROUP] as $hiddenKey)
        @continue(in_array($hiddenKey, \App\Http\Controllers\CatalogController::SIDEBAR_GROUPS, true))
        @foreach ($state[$hiddenKey] ?? [] as $hiddenValue)
            <input type="hidden" name="{{ $hiddenKey }}[]" value="{{ $hiddenValue }}">
        @endforeach
    @endforeach

    @if ($error)
        <p class="k-filter__note">Opsi filter belum dapat dimuat karena data ontologi sedang tidak tersedia.</p>
    @else
        @foreach ($facets as $key => $facet)
            @php($selected = collect($facet['options'])->where('selected', true)->count())
            <details class="k-group" data-group="{{ $key }}" @if ($loop->index < 2 || $selected > 0) open @endif>
                <summary>
                    <span>{{ $facet['title'] }}</span>
                    <span id="katalog-group-meta-{{ $key }}" class="k-group__meta" data-katalog-swap>
                        @if ($selected > 0)
                            <span class="k-group__badge">{{ $selected }}<span class="sr-only"> dipilih</span></span>
                        @endif
                    </span>
                </summary>

                <fieldset class="k-group__body">
                    <legend class="sr-only">{{ $facet['title'] }}</legend>
                    @forelse ($facet['options'] as $option)
                        @php($empty = $option['count'] === 0 && ! $option['selected'])
                        <label class="k-option {{ $option['selected'] ? 'is-selected' : '' }} {{ $empty ? 'is-empty' : '' }}">
                            <input type="checkbox" class="k-check" name="{{ $key }}[]" value="{{ $option['value'] }}"
                                   @checked($option['selected']) @disabled($empty)>
                            <span class="k-option__label">{{ $option['label'] }}</span>
                            <span class="k-option__count"><span class="sr-only">, </span>{{ $option['count'] }}<span class="sr-only"> produk</span></span>
                        </label>
                    @empty
                        <p class="k-filter__note">Belum ada data pada kelompok ini.</p>
                    @endforelse
                </fieldset>
            </details>
        @endforeach
    @endif

    <div class="k-filter__actions">
        <button type="submit" class="k-btn k-filter__apply flex-1 rounded-xl bg-ink px-4 py-3 text-xs font-black uppercase tracking-wider text-white hover:bg-teal">
            Terapkan
        </button>
        <a href="{{ $resetUrl }}" data-katalog-link
           class="k-btn rounded-xl border border-ink/15 px-4 py-3 text-center text-xs font-black uppercase tracking-wider text-muted hover:border-rose/40 hover:text-rose">
            Reset
        </a>
    </div>
</form>
