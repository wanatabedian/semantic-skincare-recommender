{{--
    Isi <head> bersama halaman Orang 5 (katalog & bahan): meta, font, Tailwind CDN + palet Aphrodite.
    Parameter: $title, $description, $styles (daftar file CSS di public/css, default ['katalog']).
--}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FDEEF1">
<title>{{ $title }} — {{ config('app.name', 'Skinova') }}</title>
<meta name="description" content="{{ $description }}">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600&display=swap" rel="stylesheet">

<script src="https://cdn.tailwindcss.com"></script>
<script>
    // Palet dan font yang sama dengan landing page Aphrodite.
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['"DM Sans"', 'Arial', 'sans-serif'],
                    serif: ['"Playfair Display"', 'Georgia', 'serif'],
                },
                colors: {
                    rose: '#E6748E',
                    teal: '#6BB1AD',
                    cream: '#FDEEF1',
                    aqua: '#B8E8EE',
                    blush: '#E5A9A9',
                    ink: '#345452',
                    muted: '#657674',
                    warm: '#FFFEFA',
                },
            },
        },
    };
</script>
@foreach ($styles ?? ['katalog'] as $style)
    <link rel="stylesheet" href="{{ asset('css/'.$style.'.css') }}">
@endforeach
