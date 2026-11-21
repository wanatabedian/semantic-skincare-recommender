{{--
    Empty state katalog.
    Parameter: $title, $message, $tone ('soft' | 'alert'), $actions (array of ['label', 'href', 'primary']).
--}}
@php($tone = $tone ?? 'soft')
<div class="k-empty {{ $tone === 'alert' ? 'k-empty--alert' : '' }}" role="status">
    <svg class="k-empty__art" viewBox="0 0 160 140" fill="none" aria-hidden="true">
        <circle cx="80" cy="70" r="62" stroke="currentColor" stroke-opacity=".18" stroke-dasharray="3 7"/>
        <path d="M24 112h112" stroke="currentColor" stroke-opacity=".35" stroke-linecap="round"/>
        {{-- toples krim kosong --}}
        <rect x="40" y="78" width="38" height="34" rx="9" stroke="currentColor" stroke-width="1.6"/>
        <rect x="37" y="70" width="44" height="10" rx="4" stroke="currentColor" stroke-width="1.6"/>
        {{-- botol serum dengan pipet --}}
        <path d="M95 112V72a8 8 0 0 1 8-8h4a8 8 0 0 1 8 8v40" stroke="currentColor" stroke-width="1.6"/>
        <rect x="100" y="50" width="10" height="14" rx="3" stroke="currentColor" stroke-width="1.6"/>
        <path d="M105 50V34" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        <path class="k-empty__drop" d="M105 18c3.5 4.6 5 7.6 5 10a5 5 0 0 1-10 0c0-2.4 1.5-5.4 5-10Z" fill="currentColor" fill-opacity=".55"/>
    </svg>

    <h3 class="font-serif text-2xl font-bold text-ink md:text-3xl">{{ $title }}</h3>
    <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-muted">{{ $message }}</p>

    @if (!empty($actions))
        <div class="mt-7 flex flex-wrap justify-center gap-3">
            @foreach ($actions as $action)
                <a href="{{ $action['href'] }}"
                   class="k-btn rounded-xl px-5 py-3 text-xs font-black uppercase tracking-wider {{ !empty($action['primary']) ? 'bg-rose text-white shadow-md hover:bg-teal' : 'border border-teal/40 text-ink hover:bg-aqua/30' }}">
                    {{ $action['label'] }}
                </a>
            @endforeach
        </div>
    @endif
</div>
