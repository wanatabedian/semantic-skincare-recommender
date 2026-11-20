
@php
    /*
     * Seluruh pertanyaan & pilihan didefinisikan di sini, satu tempat saja.
     * 'value' adalah kode yang nanti dikirim ke Laravel Controller / dipetakan ke ontologi.
     * 'label' adalah teks yang dilihat pengguna.
     */
    $steps = [
        [
            'key'      => 'skin_type',
            'summary'  => 'Jenis kulit',
            'title'    => 'Bagaimana jenis kulit wajahmu?',
            'hint'     => 'Pilih satu yang paling menggambarkan kulitmu.',
            'type'     => 'single',
            'required' => true,
            'options'  => [
                ['value' => 'berminyak', 'label' => 'Berminyak'],
                ['value' => 'kering',    'label' => 'Kering'],
                ['value' => 'kombinasi', 'label' => 'Kombinasi'],
                ['value' => 'sensitif',  'label' => 'Sensitif'],
            ],
        ],
        [
            'key'      => 'product_category',
            'summary'  => 'Kategori produk',
            'title'    => 'Produk skincare apa yang ingin kamu cari?',
            'hint'     => 'Pilih satu kategori produk.',
            'type'     => 'single',
            'required' => true,
            'options'  => [
                ['value' => 'facewash',    'label' => 'Facewash',    'sub' => 'Sabun Cuci Muka'],
                ['value' => 'toner',       'label' => 'Toner'],
                ['value' => 'serum',       'label' => 'Serum'],
                ['value' => 'moisturizer', 'label' => 'Moisturizer', 'sub' => 'Pelembap'],
            ],
        ],
        [
            'key'      => 'active_ingredient',
            'summary'  => 'Bahan aktif',
            'title'    => 'Apakah kamu memiliki preferensi bahan aktif tertentu?',
            'hint'     => 'Opsional. Kamu bisa langsung lanjut tanpa memilih.',
            'type'     => 'single',
            'required' => false,
            'options'  => [
                ['value' => 'niacinamide',    'label' => 'Niacinamide'],
                ['value' => 'salicylic_acid', 'label' => 'Salicylic Acid'],
                ['value' => 'vitamin_c',      'label' => 'Vitamin C'],
                ['value' => 'retinol',        'label' => 'Retinol'],
                ['value' => 'tidak_ada',      'label' => 'Tidak Ada Preferensi'],
            ],
        ],
        [
            'key'      => 'concerns',
            'summary'  => 'Keluhan kulit',
            'title'    => 'Apa keluhan kulit yang ingin kamu atasi?',
            'hint'     => 'Pilih 1 atau 2 keluhan.',
            'type'     => 'multi',
            'required' => true,
            'max'      => 2,
            'options'  => [
                ['value' => 'jerawat',             'label' => 'Jerawat'],
                ['value' => 'komedo',              'label' => 'Komedo'],
                ['value' => 'bekas_jerawat',       'label' => 'Bekas Jerawat'],
                ['value' => 'kulit_kusam',         'label' => 'Kulit Kusam'],
                ['value' => 'flek_hitam',          'label' => 'Flek Hitam'],
                ['value' => 'pori_pori_besar',     'label' => 'Pori-Pori Besar'],
                ['value' => 'garis_halus_kerutan', 'label' => 'Garis Halus dan Kerutan'],
                ['value' => 'mata_panda',          'label' => 'Mata Panda'],
            ],
        ],
    ];
 
    // Kelas kartu pilihan (dipakai berulang). Warna: Cream #E3D6BF, Burgundy #79262A, Sage #898D6B, Olive #4A5140.
    $cardClass = 'group flex w-full items-center justify-between gap-4 rounded-2xl border border-[#4A5140]/20 bg-white/40 px-5 py-4 text-left text-[#4A5140] transition '
        . 'hover:border-[#B5728A]/70 hover:bg-white/60 '
        . 'aria-checked:border-[#79262A] aria-checked:bg-[#79262A] aria-checked:text-[#F6EDE1] '
        . 'aria-checked:hover:border-[#5F1D21] aria-checked:hover:bg-[#5F1D21] '
        . 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B5728A]';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kuis Analisis Kulit — Aphrodite</title>
 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
 
    @vite(['resources/css/app.css', 'resources/js/app.js'])
 
    <style>
        /* Transisi halus antar pertanyaan */
        @keyframes quiz-rise {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: none; }
        }
        .step-in { animation: quiz-rise .35s ease-out both; }
        @media (prefers-reduced-motion: reduce) {
            .step-in { animation: none; }
        }
    </style>
</head>
 
<body class="min-h-screen w-full overflow-x-hidden bg-[#E3D6BF] font-['Montserrat'] text-[#4A5140] antialiased">
 
    <h1 class="sr-only">Kuis Analisis Kulit Aphrodite</h1>
 
    <!-- HEADER: tombol kembali, logo (tengah), penanda tahap -->
    <header class="mx-auto grid max-w-3xl grid-cols-[1fr_auto_1fr] items-center px-6 pt-6">
        <div>
            <a href="{{ url('/') }}" data-back
               class="inline-flex items-center gap-2 rounded-full py-2 pr-3 text-sm font-medium text-[#79262A] transition hover:opacity-70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B5728A]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m6-6-6 6 6 6" />
                </svg>
                Kembali
            </a>
        </div>
 
        <a href="{{ url('/') }}" class="flex items-center justify-center gap-2" aria-label="Aphrodite, kembali ke beranda">
    <img src="{{ asset('images/logo-aphrodite.png') }}"
         alt="Logo Aphrodite"
         class="h-20 w-auto object-contain mt-4">

    <span class="font-['Cormorant_Garamond'] text-3xl font-semibold tracking-wide text-[#79262A] -ml-2">
        Aphrodite
    </span>
</a>
 
        <p id="step-label" class="text-right text-sm font-medium text-[#4A5140]/75" aria-live="polite"></p>
    </header>
 
    <!-- PROGRESS BAR -->
    <div class="mx-auto mt-5 max-w-md px-6">
        <div id="progress" class="h-1.5 w-full overflow-hidden rounded-full bg-[#898D6B]/30"
             role="progressbar" aria-label="Progres kuis" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
            <div id="progress-bar" class="h-full w-0 rounded-full bg-[#B5728A] transition-all duration-500 ease-out"></div>
        </div>
    </div>
 
    <main class="mx-auto max-w-3xl px-6 pb-20 pt-10 md:pt-14">
 
        <!-- PERTANYAAN (satu per tahap) -->
        @foreach ($steps as $i => $step)
            @php $isMulti = $step['type'] === 'multi'; @endphp
 
            <section data-step="{{ $i }}" class="step-in hidden" aria-labelledby="step-title-{{ $i }}">
                <h2 id="step-title-{{ $i }}" tabindex="-1"
                    class="mx-auto max-w-xl text-center font-['Cormorant_Garamond'] text-4xl font-medium leading-tight text-[#79262A] outline-none md:text-5xl">
                    {{ $step['title'] }}
                </h2>
                <p class="mt-3 text-center text-[#4A5140]/80">{{ $step['hint'] }}</p>
 
                <div role="{{ $isMulti ? 'group' : 'radiogroup' }}" aria-labelledby="step-title-{{ $i }}"
                     class="mt-10 grid gap-3 md:grid-cols-2">
                    @foreach ($step['options'] as $option)
                        <button type="button"
                                role="{{ $isMulti ? 'checkbox' : 'radio' }}"
                                aria-checked="false"
                                data-option
                                data-key="{{ $step['key'] }}"
                                data-value="{{ $option['value'] }}"
                                class="{{ $cardClass }} {{ $loop->last && $loop->count % 2 === 1 ? 'md:col-span-2' : '' }}">
                            <span class="block">
                                <span class="block text-[17px] font-medium">{{ $option['label'] }}</span>
                                @if (!empty($option['sub']))
                                    <span class="mt-0.5 block text-sm text-[#4A5140]/70 group-aria-checked:text-[#F6EDE1]/85">{{ $option['sub'] }}</span>
                                @endif
                            </span>
 
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center border border-[#4A5140]/40 transition group-aria-checked:border-[#F6EDE1] group-aria-checked:bg-[#F6EDE1] {{ $isMulti ? 'rounded-md' : 'rounded-full' }}"
                                  aria-hidden="true">
                                <svg class="h-3 w-3 text-[#79262A] opacity-0 transition group-aria-checked:opacity-100" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 6.5 5 9l4.5-5" />
                                </svg>
                            </span>
                        </button>
                    @endforeach
                </div>
 
                @if ($isMulti)
                    <div class="mt-6 rounded-xl border border-[#898D6B]/50 bg-[#898D6B]/20 px-4 py-3 text-sm">
                        <p>
                            Kamu hanya dapat memilih maksimal {{ $step['max'] }} keluhan.
                            <span class="font-semibold">(<span data-concern-count>0</span> dari {{ $step['max'] }} dipilih)</span>
                        </p>
                        <p id="limit-warning" role="alert" hidden class="mt-2 font-semibold text-[#79262A]">
                            Maksimal {{ $step['max'] }} keluhan. Hapus salah satu pilihan untuk memilih yang lain.
                        </p>
                    </div>
                @endif
            </section>
        @endforeach
 
        <!-- TOMBOL LANJUT -->
        <div id="actions" class="mt-10 flex justify-center">
            <button id="next-button" type="button" disabled
                    class="inline-flex w-full items-center justify-center gap-3 rounded-xl bg-[#79262A] px-10 py-4 text-[15px] font-semibold text-[#F6EDE1] transition hover:bg-[#5F1D21] disabled:cursor-not-allowed disabled:bg-[#4A5140]/15 disabled:text-[#4A5140]/45 sm:w-auto sm:min-w-56 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B5728A]">
                <span id="next-label">Lanjut</span>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
                </svg>
            </button>
        </div>
 
        <!-- RINGKASAN JAWABAN -->
        <section id="summary" class="step-in hidden" aria-labelledby="summary-title">
            <h2 id="summary-title" tabindex="-1"
                class="mx-auto max-w-xl text-center font-['Cormorant_Garamond'] text-4xl font-medium leading-tight text-[#79262A] outline-none md:text-5xl">
                Ringkasan jawabanmu
            </h2>
            <p class="mt-3 text-center text-[#4A5140]/80">Periksa kembali sebelum kami mencarikan rekomendasi.</p>
 
            <dl class="mt-10">
                @foreach ($steps as $i => $step)
                    <div class="grid grid-cols-[1fr_auto] items-start gap-x-4 gap-y-1 border-t border-[#4A5140]/20 py-5 {{ $loop->last ? 'border-b' : '' }} sm:grid-cols-[10rem_1fr_auto] sm:gap-x-6">
                        <dt class="col-span-2 text-sm font-medium text-[#4A5140]/75 sm:col-span-1">{{ $step['summary'] }}</dt>
                        <dd data-summary="{{ $step['key'] }}" class="text-[17px] font-medium text-[#4A5140]"></dd>
                        <dd>
                            <button type="button" data-edit="{{ $i }}"
                                    class="text-sm font-medium text-[#79262A] underline underline-offset-4 transition hover:opacity-70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B5728A]">
                                Ubah
                            </button>
                        </dd>
                    </div>
                @endforeach
            </dl>
 
            <div class="mt-10 flex flex-col items-center gap-4">
                <button id="submit-button" type="button"
                        class="inline-flex w-full items-center justify-center gap-3 rounded-xl bg-[#B5728A] px-10 py-4 text-[15px] font-semibold text-[#F6EDE1] transition hover:bg-[#A45F78] sm:w-auto sm:min-w-56 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B5728A]">
                    Dapatkan Rekomendasi
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
                    </svg>
                </button>
 
                <p id="submit-notice" role="status" hidden class="max-w-sm text-center text-sm text-[#4A5140]/80">
                    Jawabanmu sudah tersimpan. Proses rekomendasi akan terhubung pada tahap pengembangan berikutnya.
                </p>
            </div>
        </section>
    </main>
 
    <script>
        (() => {
            // ---------- Data & state ----------
            const steps = @json($steps);
            const total = steps.length; // 4 tahap pertanyaan; indeks ke-4 = halaman ringkasan
            const STORAGE_KEY = 'aphrodite-quiz-v1';
 
            const emptyAnswers = () =>
                Object.fromEntries(steps.map((s) => [s.key, s.type === 'multi' ? [] : null]));
 
            let state = { step: 0, answers: emptyAnswers() };
 
            const $ = (selector, root = document) => root.querySelector(selector);
            const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
            const stepByKey = (key) => steps.find((s) => s.key === key);
 
            // ---------- Aturan lanjut ----------
            function canProceed(index) {
                const s = steps[index];
                const answer = state.answers[s.key];
                if (s.type === 'multi') return answer.length >= 1 && answer.length <= s.max;
                return s.required ? answer !== null : true;
            }
 
            // ---------- Simpan sementara (selama sesi browser) ----------
            function saveState() {
                try {
                    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
                } catch (e) { /* abaikan jika storage tidak tersedia */ }
            }
 
            function loadState() {
                try {
                    const raw = sessionStorage.getItem(STORAGE_KEY);
                    if (!raw) return;
                    const saved = JSON.parse(raw);
 
                    steps.forEach((s) => {
                        const valid = s.options.map((o) => o.value);
                        const value = saved.answers ? saved.answers[s.key] : null;
                        if (s.type === 'multi') {
                            state.answers[s.key] = Array.isArray(value)
                                ? value.filter((v) => valid.includes(v)).slice(0, s.max)
                                : [];
                        } else {
                            state.answers[s.key] = valid.includes(value) ? value : null;
                        }
                    });
 
                    const n = Number(saved.step);
                    state.step = Number.isInteger(n) && n >= 0 && n <= total ? n : 0;
                    // Jangan melompat melewati tahap yang belum valid
                    for (let i = 0; i < state.step; i++) {
                        if (!canProceed(i)) { state.step = i; break; }
                    }
                } catch (e) { /* data rusak: mulai dari awal */ }
            }
 
            // ---------- Tampilan ----------
            function labelOf(s, value) {
                const option = s.options.find((o) => o.value === value);
                return option ? option.label : '';
            }
 
            function fillSummary() {
                steps.forEach((s) => {
                    const answer = state.answers[s.key];
                    let text;
                    if (s.type === 'multi') {
                        text = answer.map((v) => labelOf(s, v)).join(', ');
                    } else {
                        text = answer ? labelOf(s, answer) : 'Tidak ada preferensi';
                    }
                    $('[data-summary="' + s.key + '"]').textContent = text;
                });
            }
 
            function render() {
                const onSummary = state.step === total;
 
                $$('[data-step]').forEach((el) => {
                    el.classList.toggle('hidden', Number(el.dataset.step) !== state.step);
                });
                $('#summary').classList.toggle('hidden', !onSummary);
                $('#actions').classList.toggle('hidden', onSummary);
 
                // Progress bar: 20%, 40%, 60%, 80%, lalu 100% di ringkasan
                const percent = onSummary ? 100 : ((state.step + 1) / (total + 1)) * 100;
                $('#progress-bar').style.width = percent + '%';
                $('#progress').setAttribute('aria-valuenow', String(Math.round(percent)));
                $('#step-label').textContent = onSummary ? 'Ringkasan' : (state.step + 1) + ' / ' + total;
 
                // Status kartu pilihan
                $$('[data-option]').forEach((button) => {
                    const answer = state.answers[button.dataset.key];
                    const selected = Array.isArray(answer)
                        ? answer.includes(button.dataset.value)
                        : answer === button.dataset.value;
                    button.setAttribute('aria-checked', String(selected));
                });
 
                // Penghitung keluhan
                const counter = $('[data-concern-count]');
                if (counter) counter.textContent = String(state.answers.concerns.length);
 
                // Tombol Lanjut
                if (!onSummary) {
                    $('#next-button').disabled = !canProceed(state.step);
                    $('#next-label').textContent = state.step === total - 1 ? 'Lihat Ringkasan' : 'Lanjut';
                }
 
                if (onSummary) fillSummary();
            }
 
            // ---------- Navigasi ----------
            function goTo(index) {
                state.step = Math.max(0, Math.min(index, total));
                saveState();
                render();
                window.scrollTo({ top: 0, behavior: 'smooth' });
 
                const heading = state.step === total ? $('#summary-title') : $('#step-title-' + state.step);
                if (heading) heading.focus({ preventScroll: true });
            }
 
            function goNext() {
                if (state.step < total && canProceed(state.step)) goTo(state.step + 1);
            }
 
            // ---------- Memilih jawaban ----------
            let warningTimer;
 
            function showLimitWarning() {
                const warning = $('#limit-warning');
                if (!warning) return;
                warning.hidden = false;
                clearTimeout(warningTimer);
                warningTimer = setTimeout(() => { warning.hidden = true; }, 3500);
            }
 
            function hideLimitWarning() {
                const warning = $('#limit-warning');
                if (warning) warning.hidden = true;
            }
 
            function selectOption(key, value) {
                const s = stepByKey(key);
 
                if (s.type === 'multi') {
                    const list = state.answers[key];
                    const index = list.indexOf(value);
                    if (index > -1) {
                        list.splice(index, 1);
                    } else if (list.length >= s.max) {
                        showLimitWarning();
                        return;
                    } else {
                        list.push(value);
                    }
                } else {
                    // Tahap opsional: klik pilihan yang sama lagi untuk membatalkan
                    state.answers[key] = (!s.required && state.answers[key] === value) ? null : value;
                }
 
                hideLimitWarning();
                saveState();
                render();
            }
 
            // ---------- Data untuk Laravel (tahap berikutnya) ----------
            function buildPayload() {
                const a = state.answers;
                return {
                    skin_type: a.skin_type,
                    product_category: a.product_category,
                    active_ingredient: a.active_ingredient && a.active_ingredient !== 'tidak_ada' ? a.active_ingredient : null,
                    concerns: [...a.concerns],
                };
            }
 
            function submitQuiz() {
                const payload = buildPayload();
                console.log('Aphrodite quiz payload:', payload);
 
                /*
                 * TODO (tahap backend): kirim ke Laravel Controller, contohnya:
                 *
                 * fetch('/kuis/rekomendasi', {
                 *     method: 'POST',
                 *     headers: {
                 *         'Content-Type': 'application/json',
                 *         'Accept': 'application/json',
                 *         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                 *     },
                 *     body: JSON.stringify(payload),
                 * });
                 */
 
                $('#submit-notice').hidden = false;
            }
 
            window.aphroditeQuiz = { getPayload: buildPayload };
 
            // ---------- Event ----------
            document.addEventListener('click', (event) => {
                const option = event.target.closest('[data-option]');
                if (option) return selectOption(option.dataset.key, option.dataset.value);
 
                const back = event.target.closest('[data-back]');
                if (back) {
                    if (state.step > 0) {
                        event.preventDefault();
                        goTo(state.step - 1);
                    }
                    return; // di tahap 1, link mengarah ke beranda
                }
 
                if (event.target.closest('#next-button')) return goNext();
 
                const edit = event.target.closest('[data-edit]');
                if (edit) return goTo(Number(edit.dataset.edit));
 
                if (event.target.closest('#submit-button')) return submitQuiz();
            });
 
            // ---------- Mulai ----------
            loadState();
            render();
        })();
    </script>
</body>
</html>