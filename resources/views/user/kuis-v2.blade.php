<!doctype html>
<html lang="id">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/logo/logo-aphrodite.png') }}">
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>Kuis Analisis Kulit — Aphrodite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
      body{font-family:'DM Sans',sans-serif}.serif{font-family:'Playfair Display',serif}
      .quiz-bg{background:linear-gradient(180deg,#FFFEFA 0%,#EEF8F3 55%,#E4F4F8 100%)}
      #layout[data-view="summary"]{grid-template-columns:minmax(0,1fr)}
      @keyframes q-in{from{opacity:0;transform:translateX(var(--dx,18px))}to{opacity:1;transform:none}}
      .q-enter{animation:q-in .35s cubic-bezier(.2,.7,.3,1) both}
      @media (prefers-reduced-motion: reduce){
        *,*::before,*::after{animation:none!important;transition:none!important;scroll-behavior:auto!important}
      }
    </style>
</head>
<body class="quiz-bg min-h-screen bg-[#FFFEFA] text-[#345452]">
@include('partials.global-nav')
<nav class="border-b border-[#E6748E]/10 bg-[#FDEEF1] px-5 py-4"><div class="mx-auto flex max-w-5xl items-center justify-between"><a href="/" class="text-xl font-extrabold text-[#E6748E]">Aphrodite</a><a href="/" class="text-sm font-bold text-[#657674] hover:text-[#E6748E]">← Beranda</a></div></nav>
<main class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:py-14">
    <header class="mb-8 text-center lg:mb-10">
        <p class="mb-3 text-xs font-extrabold uppercase tracking-[.25em] text-[#6BB1AD]">Analisis personal</p>
        <h1 class="serif mb-2 text-4xl font-bold text-[#E6748E] sm:text-5xl">Kenali kebutuhan kulitmu</h1>
        <p class="text-sm text-[#657674]">Jawab yang kamu tahu. Semua pertanyaan boleh dilewati.</p>
    </header>

    <div id="layout" class="grid gap-5 lg:grid-cols-[270px_minmax(0,1fr)] lg:items-start lg:gap-7">
        <aside id="steps-panel" class="hidden lg:sticky lg:top-6" aria-label="Tahapan kuis">
            <div class="hidden rounded-[24px] border border-[#E6748E]/10 bg-white/85 p-5 shadow-[0_14px_40px_rgba(52,84,82,.07)] backdrop-blur lg:block">
                <p class="text-xs font-extrabold uppercase tracking-[.2em] text-[#6BB1AD]">Tahapan</p>
                <p class="serif mt-1 text-xl font-bold"><span id="steps-count">0 dari 8</span> <span class="font-sans text-xs font-semibold text-[#657674]">terjawab</span></p>
                <ol id="steps" class="mt-4 space-y-1"></ol>
            </div>
            <ol id="steps-mobile" class="flex gap-2 overflow-x-auto pb-1 lg:hidden"></ol>
        </aside>

        <section class="rounded-[28px] border border-[#E6748E]/10 bg-white p-5 shadow-[0_18px_50px_rgba(52,84,82,.08)] sm:p-9" id="quiz" data-options="{{ route('api.quiz-options') }}" data-submit="{{ route('api.quiz.analyze') }}">
            <div id="progress-wrap" class="mb-8"><div class="mb-2 flex justify-between text-xs font-bold"><span id="step-label">Tahap 1 dari 8</span><span id="progress-label" class="text-[#6BB1AD]">13%</span></div><div class="h-2 overflow-hidden rounded-full bg-[#FDEEF1]"><div id="progress" class="h-full rounded-full bg-gradient-to-r from-[#6BB1AD] to-[#8FCFCB] transition-[width] duration-500 ease-out" style="width:12.5%"></div></div></div>
            <div id="loading" class="py-12 text-center text-sm text-[#657674]">Memuat pilihan dari katalog ontologi…</div>
            <div id="error" class="hidden rounded-2xl bg-red-50 p-4 text-sm text-red-700"></div>
            <div id="stage" class="hidden"></div>
            <p id="match-count" class="mt-6 hidden text-sm font-bold text-[#6BB1AD]" aria-live="polite"></p>
            <div id="feedback" class="mt-2 min-h-5 text-sm font-semibold text-[#E6748E]" role="status"></div>
            <div class="mt-6 flex flex-col-reverse gap-2 border-t border-[#E6748E]/10 pt-5 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                <button id="back" class="w-full rounded-xl border border-[#6BB1AD]/30 bg-white px-5 py-3 text-sm font-bold text-[#345452] transition hover:border-[#6BB1AD] disabled:opacity-40 sm:w-auto">Kembali</button>
                <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                    <button id="skip" class="rounded-xl px-4 py-3 text-sm font-bold text-[#657674] transition hover:bg-[#FDEEF1]">Lewati</button>
                    <button id="next" class="rounded-xl bg-[#E6748E] px-5 py-3 text-sm font-bold text-white transition hover:-translate-y-px hover:bg-[#d95f7b]">Selanjutnya</button>
                    <button id="analyze" class="col-span-2 hidden rounded-xl bg-[#345452] px-5 py-3 text-sm font-bold text-white transition hover:-translate-y-px hover:bg-[#263f3e] disabled:translate-y-0 disabled:cursor-not-allowed disabled:opacity-40">Analisis Sekarang →</button></div>
            </div>
        </section>
    </div>
    <p class="mt-6 text-center text-xs text-[#657674]">Pilihan kuis dimuat dari ontologi Aphrodite. Opsi yang tidak memiliki produk untuk kombinasi pilihanmu otomatis dinonaktifkan.</p>
</main>
<script>
(() => {
  const root = document.querySelector('#quiz'), stage = document.querySelector('#stage'), feedback = document.querySelector('#feedback');
  const $ = id => document.getElementById(id);
  const encode = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

  const STEPS = [
    {key:'age',        title:'Kelompok umur',  hint:'Pilih kelompok yang paling sesuai.',                 opt:'age_groups',  type:'single'},
    {key:'skin',       title:'Jenis kulit',    hint:'Pilih tipe kulit yang tercatat pada data produk.',   opt:'skin_types',  type:'single'},
    {key:'category',   title:'Kategori produk', hint:'Kategori dimuat dari ontologi.',                    opt:'categories',  type:'single'},
    {key:'concern',    title:'Keluhan kulit',  hint:'Pilih hingga dua keluhan.',                          opt:'concerns',    type:'multi', max:2},
    {key:'ingredient', title:'Bahan aktif',    hint:'Kamu dapat memilih satu preferensi kandungan.',      opt:'ingredients', type:'single'},
    {key:'avoid',      title:'Bahan yang ingin dihindari', hint:'Produk yang mengandung bahan ini tidak akan direkomendasikan. Boleh lebih dari satu.', opt:'ingredients', type:'multi'},
    {key:'brand',      title:'Brand favorit',  hint:'Boleh lebih dari satu. Lewati kalau brand apa pun tidak masalah.', opt:'brands', type:'multi'},
    {key:'price',      title:'Rentang harga',  hint:'Pilih rentang atau lewati untuk semua harga.',       type:'price'},
  ];
  const LAST = STEPS.length - 1;
  const MULTI = ['concern', 'avoid', 'brand'];
  const PRICE_PRESETS = [['','49999','Di bawah Rp50.000'],['50000','99999','Rp50.000 – Rp99.999'],['100000','149999','Rp100.000 – Rp149.999'],['150000','','Rp150.000 ke atas']];
  const defaults = () => ({age:'',skin:'',concern:[],category:'',ingredient:'',avoid:[],brand:[],price_min:'',price_max:''});
  const DRAFT = 'aphrodite-quiz-draft';

  let current = 0, busy = false, options = null, avail = null, reviewed = false;
  if (new URLSearchParams(location.search).get('new') === '1') localStorage.removeItem(DRAFT);
  let answers = defaults();
  try { answers = {...answers, ...JSON.parse(localStorage.getItem(DRAFT) || '{}')}; } catch {}
  for (const k of MULTI) if (!Array.isArray(answers[k])) answers[k] = [];
  const save = () => { try { localStorage.setItem(DRAFT, JSON.stringify(answers)); } catch {} };

  const toInt = v => (v === '' || v === null || v === undefined || !Number.isFinite(Number(v))) ? null : Math.trunc(Number(v));
  function fits(p, a) {
    if (a.age) {
      if (!p.age.includes(a.age)) return false;
      if (a.age === avail.teen_age && p.teen_locked) return false;
    }
    if (a.skin && !(p.skin.includes(a.skin) || p.skin.some(s => avail.all_skin_types.includes(s)))) return false;
    if (a.category && !p.category.includes(a.category)) return false;
    for (const c of a.concern) if (!p.concern.includes(c)) return false;
    if (a.ingredient && !p.ingredient.includes(a.ingredient)) return false;
    if (a.avoid.some(x => p.ingredient.includes(x))) return false;
    if (a.brand.length && !a.brand.some(b => (p.brand || []).includes(b))) return false;
    const min = toInt(a.price_min), max = toInt(a.price_max);
    if (min !== null || max !== null) {
      if (p.price === null) return false;
      if (min !== null && p.price < min) return false;
      if (max !== null && p.price > max) return false;
    }
    return true;
  }
  const clone = a => ({...a, concern: [...a.concern], avoid: [...a.avoid], brand: [...a.brand]});
  const anyFit = a => avail.products.some(p => fits(p, a));
  const countFit = a => avail.products.filter(p => fits(p, a)).length;

  function trialWith(key, value) {
    const t = clone(answers);
    if (key === 'brand') t.brand = [value];
    else if (MULTI.includes(key)) { if (!t[key].includes(value)) t[key].push(value); }
    else t[key] = value;
    return t;
  }
  const optionAvailable = (key, value) => anyFit(trialWith(key, value));
  const presetAvailable = (min, max) => anyFit({...clone(answers), price_min:min, price_max:max});
  function disabledReason(key, value) {
    if (key === 'ingredient' && answers.age === avail.teen_age && avail.teen_locked_ingredients.includes(value))
      return 'Tidak disarankan untuk usia remaja (Teen Lock)';
    if (key === 'ingredient' && answers.avoid.includes(value)) return 'Sudah kamu pilih sebagai bahan yang dihindari';
    if (key === 'avoid' && answers.ingredient === value) return 'Sudah kamu pilih sebagai bahan yang dicari';
    if (key === 'avoid') return 'Semua produk yang cocok mengandung bahan ini';
    return 'Tidak ada produk untuk kombinasi pilihanmu';
  }
  const labelOf = (key, value) => {
    const step = STEPS.find(s => s.key === key);
    return (options[step.opt].find(o => o.value === value) || {label: value}).label;
  };

  function sanitize(changedKey = null) {
    const order = STEPS.map(s => s.key);
    if (changedKey) order.splice(order.indexOf(changedKey), 1), order.unshift(changedKey);
    const kept = defaults(), removed = [];
    for (const key of order) {
      if (MULTI.includes(key)) {
        for (const c of answers[key]) {
          const t = clone(kept);
          if (key === 'brand') t.brand = [c]; else t[key].push(c);
          if (anyFit(t)) kept[key].push(c); else removed.push(labelOf(key, c));
        }
      } else if (key === 'price') {
        if (answers.price_min === '' && answers.price_max === '') continue;
        if (anyFit({...clone(kept), price_min:answers.price_min, price_max:answers.price_max})) {
          kept.price_min = answers.price_min; kept.price_max = answers.price_max;
        } else removed.push('Rentang harga');
      } else if (answers[key]) {
        if (anyFit({...clone(kept), [key]: answers[key]})) kept[key] = answers[key];
        else removed.push(labelOf(key, answers[key]));
      }
    }
    answers = kept;
    return removed;
  }
  function validateAgainstOptions() {
    const valid = key => new Set(options[STEPS.find(s => s.key === key).opt].map(o => o.value));
    for (const key of ['age','skin','category','ingredient']) if (answers[key] && !valid(key).has(answers[key])) answers[key] = '';
    for (const key of MULTI) {
      const ok = valid(key);
      answers[key] = [...new Set(answers[key])].filter(c => ok.has(c));
    }
    answers.concern = answers.concern.slice(0, 2);
    for (const key of ['price_min','price_max']) answers[key] = toInt(answers[key]) === null ? '' : String(Math.max(0, toInt(answers[key])));
  }
  const resetNotice = removed => removed.length ? `Pilihan direset karena tidak lagi memiliki produk yang cocok: ${removed.join(', ')}.` : '';

  const isAnswered = s => s.type === 'price' ? !!(answers.price_min || answers.price_max)
    : MULTI.includes(s.key) ? answers[s.key].length > 0 : !!answers[s.key];
  const CHECK_SOFT = '<svg class="h-3.5 w-3.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 6.5 5 9l4.5-5"/></svg>';

  function renderSteps() {
    $('steps-count').textContent = `${STEPS.filter(isAnswered).length} dari ${STEPS.length}`;
    const state = (s, i) => i === current ? 'active' : isAnswered(s) ? (i < current ? 'done' : 'answered') : (i < current ? 'skipped' : 'todo');
    $('steps').innerHTML = STEPS.map((s, i) => {
      const st = state(s, i);
      const badge = st === 'active' ? 'bg-[#E6748E] text-white'
        : st === 'done' ? 'bg-[#6BB1AD]/15 text-[#6BB1AD]'
        : 'border border-[#345452]/15 bg-white text-[#657674]';
      const note = st === 'skipped' ? '<span class="block text-[11px] font-medium text-[#657674]/80">Dilewati</span>' : '';
      return `<li><button type="button" data-goto="${i}" ${st === 'active' ? 'aria-current="step"' : ''}`
        + ` class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition ${st === 'active' ? 'bg-[#FDEEF1] font-bold text-[#345452]' : 'font-semibold text-[#345452]/80 hover:bg-[#FDEEF1]/50'}">`
        + `<span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold ${badge}">${st === 'done' ? CHECK_SOFT : i + 1}</span>`
        + `<span class="min-w-0 flex-1">${encode(s.title)}${note}</span>`
        + (st === 'answered' ? '<span class="h-2 w-2 shrink-0 rounded-full bg-[#6BB1AD]" aria-label="Sudah dijawab"></span>' : '')
        + `</button></li>`;
    }).join('');
    $('steps-mobile').innerHTML = STEPS.map((s, i) => {
      const st = state(s, i);
      const cls = st === 'active' ? 'bg-[#E6748E] text-white shadow-sm'
        : st === 'done' ? 'bg-[#6BB1AD]/15 text-[#6BB1AD]'
        : st === 'answered' ? 'border-2 border-[#6BB1AD]/60 bg-white text-[#345452]'
        : 'border border-[#345452]/15 bg-white text-[#657674]';
      return `<li><button type="button" data-goto="${i}" aria-label="Tahap ${i + 1}: ${encode(s.title)}" ${st === 'active' ? 'aria-current="step"' : ''} class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold transition ${cls}">${st === 'done' ? CHECK_SOFT : i + 1}</button></li>`;
    }).join('');
    document.querySelectorAll('[data-goto]').forEach(b => b.onclick = () => { current = Number(b.dataset.goto); feedback.textContent = ''; update(); });
  }

  let shown = null, shownIdx = 0;
  function animateStage(view) {
    if (shown === view) return;
    stage.style.setProperty('--dx', current < shownIdx ? '-18px' : '18px');
    stage.classList.remove('q-enter'); void stage.offsetWidth; stage.classList.add('q-enter');
    shown = view; shownIdx = current;
  }

  function setView(summaryView) {
    $('layout').dataset.view = summaryView ? 'summary' : 'quiz';
    $('steps-panel').classList.toggle('hidden', summaryView);
    $('progress-wrap').classList.toggle('hidden', summaryView);
  }

  function update() {
    reviewed = false;
    setView(false);
    $('step-label').textContent = `Tahap ${current+1} dari ${STEPS.length}`;
    const p = Math.round((current+1) / STEPS.length * 100);
    $('progress').style.width = p + '%'; $('progress-label').textContent = p + '%';
    $('back').disabled = current === 0;
    $('next').classList.toggle('hidden', current === LAST);
    $('analyze').classList.remove('hidden'); $('analyze').textContent = 'Analisis Sekarang →';
    $('skip').classList.remove('hidden');
    render();
  }

  const DOT = '<span class="h-2 w-2 rounded-full bg-white"></span>';
  const CHECK = '<svg class="h-3 w-3 text-white" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 6.5 5 9l4.5-5"/></svg>';

  function choose(step) {
    const multi = step.type === 'multi';
    return `<div class="grid gap-3 sm:grid-cols-2" role="${multi ? 'group' : 'radiogroup'}" aria-label="${encode(step.title)}">${options[step.opt].map(o => {
      const sel = multi ? answers[step.key].includes(o.value) : answers[step.key] === o.value;
      const ok = sel || optionAvailable(step.key, o.value);
      const reason = ok ? '' : disabledReason(step.key, o.value);
      const card = !ok ? 'cursor-not-allowed border-dashed border-[#345452]/25 bg-transparent opacity-60'
        : sel ? 'cursor-pointer border-[#6BB1AD] bg-[#B8E8EE]/30 shadow-[0_6px_18px_rgba(107,177,173,.18)]' : 'cursor-pointer border-[#E6748E]/15 bg-[#FFFEFA] hover:-translate-y-0.5 hover:border-[#6BB1AD] hover:shadow-[0_8px_20px_rgba(52,84,82,.08)]';
      const box = sel ? 'border-[#6BB1AD] bg-[#6BB1AD]' : 'border-[#345452]/30 bg-white';
      return `<label class="flex items-center gap-3 rounded-2xl border p-4 transition ${card}"${reason ? ` title="${encode(reason)}"` : ''}>`
        + `<input class="peer sr-only" type="${multi ? 'checkbox' : 'radio'}" name="${step.key}" value="${encode(o.value)}" data-key="${step.key}" ${sel ? 'checked' : ''} ${ok ? '' : 'disabled aria-disabled="true"'}>`
        + `<span aria-hidden="true" class="flex h-5 w-5 shrink-0 items-center justify-center border-2 ${multi ? 'rounded-md' : 'rounded-full'} ${box} peer-focus-visible:ring-2 peer-focus-visible:ring-[#6BB1AD] peer-focus-visible:ring-offset-2">${sel ? (multi ? CHECK : DOT) : ''}</span>`
        + `<span class="min-w-0"><span class="block text-sm font-semibold">${encode(o.label)}</span>${reason ? `<span class="mt-0.5 block text-xs font-medium text-[#657674]">${encode(reason)}</span>` : ''}</span>`
        + `</label>`;
    }).join('')}</div>`;
  }

  function priceStep() {
    const presets = PRICE_PRESETS.map(([min, max, label]) => {
      const sel = answers.price_min === min && answers.price_max === max;
      const ok = sel || presetAvailable(min, max);
      const card = !ok ? 'cursor-not-allowed border-dashed border-[#345452]/25 bg-transparent opacity-60'
        : sel ? 'border-[#6BB1AD] bg-[#B8E8EE]/30 shadow-[0_6px_18px_rgba(107,177,173,.18)]' : 'border-[#E6748E]/15 bg-[#FFFEFA] hover:-translate-y-0.5 hover:border-[#6BB1AD] hover:shadow-[0_8px_20px_rgba(52,84,82,.08)]';
      return `<button type="button" role="radio" aria-checked="${sel}" data-min="${min}" data-max="${max}" ${ok ? '' : 'disabled'} class="price flex items-center gap-3 rounded-2xl border ${card} p-4 text-left text-sm font-semibold transition">`
        + `<span aria-hidden="true" class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 ${sel ? 'border-[#6BB1AD] bg-[#6BB1AD]' : 'border-[#345452]/30 bg-white'}">${sel ? DOT : ''}</span>`
        + `<span><span class="block">${label}</span>${ok ? '' : '<span class="mt-0.5 block text-xs font-medium text-[#657674]">Tidak ada produk untuk kombinasi pilihanmu</span>'}</span></button>`;
    }).join('');
    return `<div class="grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="Rentang harga">${presets}</div>`
      + `<div class="mt-5 grid grid-cols-2 gap-3"><label class="text-xs font-bold">Minimum (Rp)<input id="pmin" type="number" min="0" class="mt-1 w-full rounded-xl border p-3" value="${encode(answers.price_min)}"></label><label class="text-xs font-bold">Maksimum (Rp)<input id="pmax" type="number" min="0" class="mt-1 w-full rounded-xl border p-3" value="${encode(answers.price_max)}"></label></div>`;
  }

  function refreshCount() {
    const n = countFit(answers), el = $('match-count');
    el.classList.remove('hidden');
    el.classList.toggle('text-[#6BB1AD]', n > 0); el.classList.toggle('text-red-600', n === 0);
    el.textContent = n > 0 ? `${n} produk cocok dengan pilihanmu saat ini.` :'Tidak ada produk untuk kombinasi ini. Ubah rentang harga atau pilihan lain.';
    $('analyze').disabled = n === 0 || busy;
    return n;
  }

  function render() {
    const step = STEPS[current];
    const counter = step.max ? ` <b>${answers[step.key].length}/${step.max} dipilih</b>`
      : step.type === 'multi' && answers[step.key].length ? ` <b>${answers[step.key].length} dipilih</b>` : '';
    const focused = document.activeElement && stage.contains(document.activeElement) ? document.activeElement : null;
    const focusSel = focused && focused.dataset.key ? `input[data-key="${focused.dataset.key}"][value="${CSS.escape(focused.value)}"]` : (focused && focused.id ? '#' + focused.id : null);
    stage.innerHTML = `<h2 class="serif mb-2 text-2xl font-bold">${step.title}</h2><p class="mb-5 text-sm text-[#657674]">${step.hint}${counter}</p>`
      + (step.type === 'price' ? priceStep() : choose(step));

    stage.querySelectorAll('input[data-key]').forEach(x => x.onclick = () => select(x.dataset.key, x.value));
    stage.querySelectorAll('.price').forEach(x => x.onclick = () => {
      const same = answers.price_min === x.dataset.min && answers.price_max === x.dataset.max;
      answers.price_min = same ? '' : x.dataset.min; answers.price_max = same ? '' : x.dataset.max;
      feedback.textContent = resetNotice(sanitize('price')); save(); render();
    });
    for (const [id, key] of [['pmin','price_min'],['pmax','price_max']]) {
      const el = $(id);
      if (el) el.oninput = () => { answers[key] = el.value; save(); refreshCount(); };
    }
    if (focusSel) { const el = stage.querySelector(focusSel); if (el) el.focus(); }
    renderSteps();
    animateStage('step' + current);
    refreshCount();
  }

  function select(key, value) {
    if (key === 'concern') {
      if (answers.concern.includes(value)) answers.concern = answers.concern.filter(v => v !== value);
      else if (answers.concern.length >= 2) { feedback.textContent = 'Maksimal dua keluhan dapat dipilih.'; render(); return; }
      else answers.concern = [...answers.concern, value];
      feedback.textContent = answers.concern.length === 2 ? 'Batas dua keluhan tercapai.' : '';
    } else if (MULTI.includes(key)) {
      answers[key] = answers[key].includes(value) ? answers[key].filter(v => v !== value) : [...answers[key], value];
      feedback.textContent = '';
    } else {
      answers[key] = answers[key] === value ? '' : value;
      feedback.textContent = '';
    }
    const notice = resetNotice(sanitize(key));
    if (notice) feedback.textContent = notice;
    save(); render();
  }

  function summary() {
    const values = s => s.type === 'price'
      ? (isAnswered(s) ? [`Rp${answers.price_min||'0'} – Rp${answers.price_max||'tanpa batas'}`] : [])
      : MULTI.includes(s.key) ? answers[s.key].map(v => labelOf(s.key, v))
      : answers[s.key] ? [labelOf(s.key, answers[s.key])] : [];
    const done = STEPS.filter(isAnswered).length, pct = Math.round(done / STEPS.length * 100), n = countFit(answers);
    const top = `<div class="mb-6">`
      + `<p class="text-[11px] font-extrabold uppercase tracking-[.2em] text-[#6BB1AD]">Ringkasan</p>`
      + `<h2 class="serif mt-1 text-2xl font-bold sm:text-3xl">Profil kulitmu</h2>`
      + `<p class="mt-2 text-lg font-bold text-[#E6748E]">${n} produk cocok dengan pilihanmu</p>`
      + `<p class="mt-0.5 text-xs font-medium text-[#657674]">${done} dari ${STEPS.length} pertanyaan terjawab · ${pct}%</p></div>`;
    const cards = STEPS.map((s, i) => {
      const vals = values(s);
      const chipCls = s.key === 'avoid' ? 'border-[#E6748E]/30 bg-[#FDEEF1] text-[#E6748E]' : 'border-[#6BB1AD]/25 bg-[#B8E8EE]/30 text-[#345452]';
      const chips = vals.length
        ? vals.map(v => `<span class="rounded-full border px-3 py-1 text-xs font-bold ${chipCls}">${encode(v)}</span>`).join('')
        : `<span class="rounded-full border border-dashed border-[#345452]/25 px-3 py-1 text-xs font-semibold text-[#657674] opacity-70">Dilewati${s.type === 'price' ? ' · semua harga' : ''}</span>`;
      return `<div class="rounded-2xl border border-[#E6748E]/10 bg-[#FFFEFA] p-4 transition hover:border-[#6BB1AD]/40">`
        + `<div class="mb-3 flex items-center justify-between gap-3"><h3 class="text-sm font-bold">${encode(s.title)}</h3>`
        + `<button type="button" data-edit="${i}" class="rounded-lg px-2.5 py-1 text-xs font-bold text-[#6BB1AD] transition hover:bg-[#B8E8EE]/30">Ubah</button></div>`
        + `<div class="flex flex-wrap gap-2">${chips}</div></div>`;
    }).join('');
    return top + `<div class="grid gap-3 sm:grid-cols-2">${cards}</div>`;
  }
  function showSummary() {
    stage.innerHTML = summary(); reviewed = true;
    setView(true); animateStage('summary');
    $('next').classList.add('hidden'); $('skip').classList.add('hidden');
    $('back').disabled = false;
    $('analyze').textContent = 'Konfirmasi & Analisis →';
    feedback.textContent = 'Periksa kembali jawaban sebelum melanjutkan.';
    stage.querySelectorAll('[data-edit]').forEach(b => b.onclick = () => { current = Number(b.dataset.edit); feedback.textContent = ''; update(); });
    refreshCount();
  }

  $('back').onclick = () => {
    if (reviewed) { feedback.textContent = ''; update(); return; }
    if (current > 0) { current--; feedback.textContent = ''; update(); }
  };
  $('next').onclick = () => { if (current < LAST) { current++; feedback.textContent = ''; update(); } };
  $('skip').onclick = () => {
    const key = STEPS[current].key;
    if (MULTI.includes(key)) answers[key] = [];
    else if (key === 'price') { answers.price_min = ''; answers.price_max = ''; }
    else answers[key] = '';
    save(); feedback.textContent = '';
    if (current < LAST) { current++; update(); } else showSummary();
  };

  $('analyze').onclick = async () => {
    if (busy) return;
    if (refreshCount() === 0) return;
    if (!reviewed) { showSummary(); return; }
    busy = true; $('analyze').disabled = true; $('analyze').textContent = 'Menganalisis…';
    try {
      const response = await fetch(root.dataset.submit, {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}, body:JSON.stringify(answers)});
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'Pilihan tidak valid. Muat ulang kuis.');
      const history = JSON.parse(localStorage.getItem('aphrodite-quiz-history') || '[]');
      history.unshift({answers, created_at: new Date().toISOString()});
      localStorage.setItem('aphrodite-quiz-history', JSON.stringify(history.slice(0, 5)));
      localStorage.removeItem(DRAFT);
      const params = new URLSearchParams();
      for (const k of ['age','skin','category','ingredient','price_min','price_max']) if (answers[k]) params.set(k, answers[k]);
      for (const k of MULTI) answers[k].forEach(x => params.append(k + '[]', x));
      location.href = '/rekomendasi?' + params.toString();
    } catch (e) {
      feedback.textContent = e.message; busy = false; $('analyze').disabled = false; $('analyze').textContent = 'Analisis Sekarang →';
    }
  };

  fetch(root.dataset.options).then(async r => {
    const data = await r.json();
    if (!r.ok) throw new Error(data.message || 'Gagal memuat pilihan.');
    options = data; avail = data.availability;
    options.brands ??= [];
    for (const k of ['age_groups','skin_types','categories','concerns','ingredients']) if (!options[k]?.length) throw new Error('Data pilihan ontologi belum tersedia.');
    if (!avail?.products?.length) throw new Error('Data produk ontologi belum tersedia.');
    validateAgainstOptions();
    const notice = resetNotice(sanitize());
    if (notice) feedback.textContent = notice;
    save();
    $('loading').classList.add('hidden'); stage.classList.remove('hidden'); update();
  }).catch(e => { $('loading').classList.add('hidden'); $('error').classList.remove('hidden'); $('error').textContent = e.message; });
})();
</script>
</body></html>
