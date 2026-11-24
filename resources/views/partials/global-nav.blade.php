@php
    $links = [
        ['label' => 'Beranda', 'route' => 'home', 'pattern' => 'home'],
        ['label' => 'Katalog', 'route' => 'katalog', 'pattern' => 'katalog'],
        ['label' => 'Kandungan', 'route' => 'bahan', 'pattern' => 'bahan*'],
        ['label' => 'Eksplorasi Ontologi', 'route' => 'ontology.dashboard', 'pattern' => 'ontology.*'],
        ['label' => 'Tentang', 'route' => 'tentang', 'pattern' => 'tentang'],
    ];
@endphp
<style>
    .aph-nav{position:sticky;top:0;z-index:50;background:rgba(253,238,241,.94);border-bottom:1px solid rgba(230,116,142,.16);backdrop-filter:blur(14px)}
    .aph-nav__inner{position:relative;max-width:1440px;margin:auto;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;gap:20px}
    .aph-nav__brand{display:flex;align-items:center;gap:10px;color:#345452;text-decoration:none;font-weight:800;font-size:20px;white-space:nowrap}
    .aph-nav__brand img{width:42px;height:42px;object-fit:contain}
    .aph-nav__links{display:flex;align-items:center;justify-content:flex-end;gap:clamp(10px,2vw,28px);flex-wrap:wrap}
    .aph-nav__links a{color:#657674;text-decoration:none;font:700 11px/1.3 Arial,sans-serif;letter-spacing:.07em;text-transform:uppercase}
    .aph-nav__links a:hover,.aph-nav__links a[aria-current=page]{color:#E6748E}
    .aph-nav__links a.aph-nav__quiz{padding:11px 16px;border-radius:12px;background:#E6748E;color:#fff;letter-spacing:.02em}
    .aph-nav__links a.aph-nav__quiz:hover{background:#6BB1AD;color:#fff}
    .aph-nav__toggle{display:none}
    .aph-nav~nav{display:none}
    @media(max-width:760px){
        .aph-nav__inner{align-items:center;flex-wrap:wrap;gap:0;padding:8px 16px}
        .aph-nav__brand img{width:34px;height:34px}
        .aph-nav__brand{font-size:18px}
        .aph-nav__toggle{width:42px;height:42px;display:grid;place-content:center;gap:5px;border:1px solid rgba(230,116,142,.22);border-radius:12px;background:#fff;color:#345452;cursor:pointer}
        .aph-nav__toggle:focus-visible,.aph-nav__links a:focus-visible{outline:3px solid #6BB1AD;outline-offset:3px}
        .aph-nav__toggle span{width:19px;height:2px;border-radius:2px;background:currentColor;transition:transform .2s ease,opacity .2s ease}
        .aph-nav__toggle[aria-expanded=true] span:first-child{transform:translateY(7px) rotate(45deg)}
        .aph-nav__toggle[aria-expanded=true] span:nth-child(2){opacity:0}
        .aph-nav__toggle[aria-expanded=true] span:last-child{transform:translateY(-7px) rotate(-45deg)}
        .aph-nav__links{display:none;width:calc(100% + 32px);margin:8px -16px -8px;padding:10px 16px 16px;flex-direction:column;align-items:stretch;justify-content:flex-start;gap:2px;background:rgba(253,238,241,.98);border-top:1px solid rgba(230,116,142,.14);box-sizing:border-box}
        .aph-nav__links.is-open{display:flex}
        .aph-nav__links a{display:block;padding:12px 10px;font-size:12px;line-height:1.4}
        .aph-nav__links a.aph-nav__quiz{margin-top:6px;padding:12px 14px;text-align:center}
    }
    @media(prefers-reduced-motion:reduce){.aph-nav__toggle span{transition:none}}
</style>
<nav class="aph-nav" aria-label="Navigasi utama">
    <div class="aph-nav__inner">
        <a class="aph-nav__brand" href="{{ route('home') }}">
            <img src="{{ asset('images/logo/logo-aphrodite.png') }}" alt="">
            <span>Aphrodite</span>
        </a>
        <button class="aph-nav__toggle" type="button" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="aph-nav-links">
            <span></span><span></span><span></span>
        </button>
        <div class="aph-nav__links" id="aph-nav-links">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}" @if (request()->routeIs($link['pattern'])) aria-current="page" @endif>{{ $link['label'] }}</a>
            @endforeach
            <a class="aph-nav__quiz" href="{{ route('kuis', ['new' => 1]) }}">Mulai Kuis →</a>
        </div>
    </div>
</nav>
<script>
    (() => {
        const nav = document.querySelector('.aph-nav');
        if (!nav) return;

        const toggle = nav.querySelector('.aph-nav__toggle');
        const menu = nav.querySelector('.aph-nav__links');
        const closeMenu = () => {
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Buka menu navigasi');
            menu.classList.remove('is-open');
        };

        toggle.addEventListener('click', () => {
            const isOpen = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!isOpen));
            toggle.setAttribute('aria-label', isOpen ? 'Buka menu navigasi' : 'Tutup menu navigasi');
            menu.classList.toggle('is-open', !isOpen);
        });
        menu.addEventListener('click', (event) => {
            if (event.target.closest('a')) closeMenu();
        });
        nav.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMenu();
                toggle.focus();
            }
        });
    })();
</script>
