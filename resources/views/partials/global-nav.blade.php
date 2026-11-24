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
    .aph-nav__inner{max-width:1440px;margin:auto;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;gap:20px}
    .aph-nav__brand{display:flex;align-items:center;gap:10px;color:#345452;text-decoration:none;font-weight:800;font-size:20px;white-space:nowrap}
    .aph-nav__brand img{width:42px;height:42px;object-fit:contain}
    .aph-nav__links{display:flex;align-items:center;justify-content:flex-end;gap:clamp(10px,2vw,28px);flex-wrap:wrap}
    .aph-nav__links a{color:#657674;text-decoration:none;font:700 11px/1.3 Arial,sans-serif;letter-spacing:.07em;text-transform:uppercase}
    .aph-nav__links a:hover,.aph-nav__links a[aria-current=page]{color:#E6748E}
    .aph-nav__links a.aph-nav__quiz{padding:11px 16px;border-radius:12px;background:#E6748E;color:#fff;letter-spacing:.02em}
    .aph-nav__links a.aph-nav__quiz:hover{background:#6BB1AD;color:#fff}
    .aph-nav~nav{display:none}
    @media(max-width:760px){.aph-nav__inner{align-items:flex-start;flex-direction:column;gap:8px;padding:9px 16px}.aph-nav__brand img{width:34px;height:34px}.aph-nav__brand{font-size:18px}.aph-nav__links{width:100%;justify-content:flex-start;gap:8px 16px}.aph-nav__links a{font-size:10px}.aph-nav__links a.aph-nav__quiz{padding:8px 11px}}
</style>
<nav class="aph-nav" aria-label="Navigasi utama">
    <div class="aph-nav__inner">
        <a class="aph-nav__brand" href="{{ route('home') }}">
            <img src="{{ asset('images/logo/logo-aphrodite.png') }}" alt="">
            <span>Aphrodite</span>
        </a>
        <div class="aph-nav__links">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}" @if (request()->routeIs($link['pattern'])) aria-current="page" @endif>{{ $link['label'] }}</a>
            @endforeach
            <a class="aph-nav__quiz" href="{{ route('kuis', ['new' => 1]) }}">Mulai Kuis →</a>
        </div>
    </div>
</nav>
