{{--
    Panel filter katalog (Orang 5). Opsi & jumlah berasal dari ontologi (CatalogController::facets()).
    Ditampilkan sebagai deretan logo brand di atas katalog (bukan sidebar).
    Form GET biasa: tetap berfungsi tanpa JavaScript; katalog.js memperbaruinya tanpa muat ulang.
    Kelas k-option / k-option__count / k-check dipertahankan karena dipakai katalog.js (syncForm).
    Parameter: $facets, $error, $resetUrl.
--}}
<form id="katalog-form" action="{{ route('katalog') }}" method="GET" data-katalog-form>
    {{-- Filter dari URL yang tidak punya panel (mis. kategori dari landing page) tetap ikut terkirim. --}}
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
            <div class="k-brands" data-group="{{ $key }}">
                <div class="k-brands__head">
                    <h2 id="katalog-filter-title" class="font-serif text-2xl font-semibold text-ink">
                        Saring {{ strtolower($facet['title']) }}
                        <span id="katalog-group-meta-{{ $key }}" class="k-group__meta" data-katalog-swap>
                            @if ($selected > 0)
                                <span class="k-group__badge">{{ $selected }}<span class="sr-only"> dipilih</span></span>
                            @endif
                        </span>
                    </h2>
                    <p class="text-xs text-muted">Pilih satu atau beberapa. Angka menunjukkan jumlah produk yang cocok.</p>
                </div>

                <fieldset class="k-brands__track">
                    <legend class="sr-only">{{ $facet['title'] }}</legend>
                    @forelse ($facet['options'] as $option)
                        @php($empty = $option['count'] === 0 && ! $option['selected'])
                        @php($logo = $key === 'brand' ? \App\Http\Controllers\CatalogController::brandLogo($option['label']) : null)
                        <label class="k-option k-brand {{ $option['selected'] ? 'is-selected' : '' }} {{ $empty ? 'is-empty' : '' }}">
                            <input type="checkbox" class="k-check" name="{{ $key }}[]" value="{{ $option['value'] }}"
                                   @checked($option['selected']) @disabled($empty)>
                            <span class="k-brand__logo" aria-hidden="true">
                                @if ($logo)
                                    <img src="{{ $logo }}" alt="" loading="lazy" decoding="async">
                                @else
                                    <span class="k-brand__initial">{{ mb_strtoupper(mb_substr($option['label'], 0, 1)) }}</span>
                                @endif
                            </span>
                            <span class="k-option__label">{{ $option['label'] }}</span>
                            <span class="k-option__count"><span class="sr-only">, </span>{{ $option['count'] }}<span class="sr-only"> produk</span></span>
                        </label>
                    @empty
                        <p class="k-filter__note">Belum ada data pada kelompok ini.</p>
                    @endforelse
                </fieldset>
            </div>
        @endforeach
    @endif

    {{-- Tanpa JavaScript pilihan diterapkan lewat tombol ini; dengan JavaScript filter langsung berlaku. --}}
    <div class="k-filter__actions k-brands__actions">
        <button type="submit" class="k-btn k-filter__apply rounded-xl bg-ink px-4 py-3 text-xs font-black uppercase tracking-wider text-white hover:bg-teal">
            Terapkan
        </button>
        <a href="{{ $resetUrl }}" data-katalog-link
           class="k-btn rounded-xl border border-ink/15 px-4 py-3 text-center text-xs font-black uppercase tracking-wider text-muted hover:border-rose/40 hover:text-rose">
            Reset
        </a>
    </div>
</form>
