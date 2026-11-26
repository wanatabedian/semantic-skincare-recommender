<!doctype html>
<html lang="id">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/logo/logo-aphrodite.png') }}">
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>Kuis Analisis Kulit — Aphrodite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'DM Sans',sans-serif}.serif{font-family:'Playfair Display',serif}</style>
</head>
<body class="min-h-screen bg-[#FFFEFA] text-[#345452]">
@include('partials.global-nav')
<nav class="border-b border-[#E6748E]/10 bg-[#FDEEF1] px-5 py-4"><div class="mx-auto flex max-w-5xl items-center justify-between"><a href="/" class="text-xl font-extrabold text-[#E6748E]">Aphrodite</a><a href="/" class="text-sm font-bold text-[#657674] hover:text-[#E6748E]">← Beranda</a></div></nav>
<main class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
    <p class="mb-3 text-center text-xs font-extrabold uppercase tracking-[.25em] text-[#6BB1AD]">Analisis personal</p>
    <h1 class="serif mb-2 text-center text-4xl font-bold text-[#E6748E] sm:text-5xl">Kenali kebutuhan kulitmu</h1>
    <p class="mb-9 text-center text-sm text-[#657674]">Jawab yang kamu tahu. Semua pertanyaan boleh dilewati.</p>
    <section class="rounded-[28px] border border-[#E6748E]/10 bg-white p-5 shadow-[0_18px_50px_rgba(52,84,82,.08)] sm:p-9" id="quiz" data-options="{{ route('api.quiz-options') }}" data-submit="{{ route('api.quiz.analyze') }}">
        <div class="mb-8"><div class="mb-2 flex justify-between text-xs font-bold"><span id="step-label">Tahap 1 dari 8</span><span id="progress-label">13%</span></div><div class="h-2 overflow-hidden rounded-full bg-[#FDEEF1]"><div id="progress" class="h-full rounded-full bg-[#6BB1AD] transition-all" style="width:12.5%"></div></div></div>
        <div id="loading" class="py-12 text-center text-sm text-[#657674]">Memuat pilihan dari katalog ontologi…</div>
        <div id="error" class="hidden rounded-2xl bg-red-50 p-4 text-sm text-red-700"></div>
        <div id="stage" class="hidden min-h-64"></div>
        <p id="match-count" class="mt-5 hidden text-xs font-bold text-[#6BB1AD]" aria-live="polite"></p>
        <div id="feedback" class="mt-2 min-h-5 text-sm font-semibold text-[#E6748E]" role="status"></div>
        <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-[#E6748E]/10 pt-5">
            <button id="back" class="rounded-xl border border-[#6BB1AD]/30 px-5 py-3 text-sm font-bold text-[#345452] disabled:opacity-40">Kembali</button>
            <div class="flex flex-wrap gap-2">
                <button id="skip" class="rounded-xl px-4 py-3 text-sm font-bold text-[#657674] hover:bg-[#FDEEF1]">Lewati</button>
                <button id="next" class="rounded-xl bg-[#E6748E] px-5 py-3 text-sm font-bold text-white hover:bg-[#d95f7b]">Selanjutnya</button>
                <button id="analyze" class="hidden rounded-xl bg-[#345452] px-5 py-3 text-sm font-bold text-white hover:bg-[#263f3e] disabled:cursor-not-allowed disabled:opacity-40">Analisis Sekarang →</button></div>
        </div>
    </section>
    <p class="mt-5 text-center text-xs text-[#657674]">Pilihan kuis dimuat dari ontologi Aphrodite. Opsi yang tidak memiliki produk untuk kombinasi pilihanmu otomatis dinonaktifkan.</p>
</main>
<script>
(() => {
  const root = document.querySelector('#quiz'), stage = document.querySelector('#stage'), feedback = document.querySelector('#feedback');
  const $ = id => document.getElementById(id);
  const encode = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

  // Urutan tahap. 'single' = pilih satu (indikator bulat), 'multi' = boleh lebih dari satu (indikator kotak).
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
  // Rentang tidak saling tumpang tindih (sama dengan Katalog): harga tepat di batas hanya masuk satu rentang.
  // Mesin rekomendasi memakai min/max inklusif, jadi batas atas ditulis 49.999 / 99.999 / 149.999.
  const PRICE_PRESETS = [['','49999','Di bawah Rp50.000'],['50000','99999','Rp50.000 – Rp99.999'],['100000','149999','Rp100.000 – Rp149.999'],['150000','','Rp150.000 ke atas']];
  const defaults = () => ({age:'',skin:'',concern:[],category:'',ingredient:'',avoid:[],brand:[],price_min:'',price_max:''});
  const DRAFT = 'aphrodite-quiz-draft';

  let current = 0, busy = false, options = null, avail = null, reviewed = false;
  if (new URLSearchParams(location.search).get('new') === '1') localStorage.removeItem(DRAFT);
  let answers = defaults();
  try { answers = {...answers, ...JSON.parse(localStorage.getItem(DRAFT) || '{}')}; } catch {}
  for (const k of MULTI) if (!Array.isArray(answers[k])) answers[k] = [];
  const save = () => { try { localStorage.setItem(DRAFT, JSON.stringify(answers)); } catch {} };

  // ---------- Cross-filtering (logika sama dengan mesin rekomendasi) ----------
  const toInt = v => (v === '' || v === null || v === undefined || !Number.isFinite(Number(v))) ? null : Math.trunc(Number(v));
  function fits(p, a) {
    if (a.age) {
      if (!p.age.includes(a.age)) return false;
      if (a.age === avail.teen_age && p.teen_locked) return false;            // Teen Lock
    }
    if (a.skin && !(p.skin.includes(a.skin) || p.skin.some(s => avail.all_skin_types.includes(s)))) return false;
    if (a.category && !p.category.includes(a.category)) return false;
    for (const c of a.concern) if (!p.concern.includes(c)) return false;
    if (a.ingredient && !p.ingredient.includes(a.ingredient)) return false;
    if (a.avoid.some(x => p.ingredient.includes(x))) return false;              // bahan dihindari (individu/golongan)
    if (a.brand.length && !a.brand.some(b => (p.brand || []).includes(b))) return false; // cukup salah satu brand
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

  // Opsi dianggap tersedia bila masih ada produk yang memenuhi pilihan lain + opsi ini.
  // Keluhan & bahan dihindari = DAN (ditambahkan); brand = ATAU, jadi tiap brand dicek sendiri.
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

  // Pertahankan pilihan yang baru diubah, lalu periksa pilihan lain sesuai urutan tahap.
  // Pilihan yang membuat hasil kosong direset otomatis.
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

  // ---------- Tampilan ----------
  function update() {
    reviewed = false;
    $('step-label').textContent = `Tahap ${current+1} dari ${STEPS.length} · ${STEPS[current].title}`;
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
      const card = !ok ? 'cursor-not-allowed border-[#345452]/10 bg-[#345452]/[.03] opacity-50'
        : sel ? 'cursor-pointer border-[#6BB1AD] bg-[#B8E8EE]/30' : 'cursor-pointer border-[#E6748E]/15 bg-[#FFFEFA] hover:border-[#6BB1AD]';
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
      const card = !ok ? 'cursor-not-allowed border-[#345452]/10 bg-[#345452]/[.03] opacity-50'
        : sel ? 'border-[#6BB1AD] bg-[#B8E8EE]/30' : 'border-[#E6748E]/15 hover:border-[#6BB1AD]';
      return `<button type="button" role="radio" aria-checked="${sel}" data-min="${min}" data-max="${max}" ${ok ? '' : 'disabled'} class="price flex items-center gap-3 rounded-2xl border ${card} p-4 text-left text-sm font-semibold">`
        + `<span aria-hidden="true" class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 ${sel ? 'border-[#6BB1AD] bg-[#6BB1AD]' : 'border-[#345452]/30 bg-white'}">${sel ? DOT : ''}</span>`
        + `<span><span class="block">${label}</span>${ok ? '' : '<span class="mt-0.5 block text-xs font-medium text-[#657674]">Tidak ada produk untuk kombinasi pilihanmu</span>'}</span></button>`;
    }).join('');
    return `<div class="grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="Rentang harga">${presets}</div>`
      + `<div class="mt-5 grid grid-cols-2 gap-3"><label class="text-xs font-bold">Minimum (Rp)<input id="pmin" type="number" min="0" class="mt-1 w-full rounded-xl border p-3" value="${encode(answers.price_min)}"></label><label class="text-xs font-bold">Maksimum (Rp)<input id="pmax" type="number" min="0" class="mt-1 w-full rounded-xl border p-3" value="${encode(answers.price_max)}"></label></div>`;
  }

  // Jumlah produk yang cocok dengan semua pilihan saat ini; Analisis dikunci bila 0.
  function refreshCount() {
    const n = countFit(answers), el = $('match-count');
    el.classList.remove('hidden');
    el.classList.toggle('text-[#6BB1AD]', n > 0); el.classList.toggle('text-red-600', n === 0);
    el.textContent = n > 0 ? `${n} produk cocok dengan semua pilihanmu saat ini.` : 'Tidak ada produk untuk kombinasi ini. Ubah rentang harga atau pilihan lain.';
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
      // Pilihan tunggal: klik opsi yang sama sekali lagi untuk membatalkan.
      answers[key] = answers[key] === value ? '' : value;
      feedback.textContent = '';
    }
    const notice = resetNotice(sanitize(key));
    if (notice) feedback.textContent = notice;
    save(); render();
  }

  function summary() {
    const one = key => answers[key] ? labelOf(key, answers[key]) : '';
    const many = key => answers[key].map(v => labelOf(key, v)).join(', ');
    const rows = [['Umur',one('age')],['Jenis kulit',one('skin')],['Kategori',one('category')],['Keluhan',many('concern')],['Bahan aktif',one('ingredient')],['Hindari bahan',many('avoid')],['Brand',many('brand')],['Harga',answers.price_min||answers.price_max?`Rp${answers.price_min||'0'} – Rp${answers.price_max||'tanpa batas'}`:'Semua harga']];
    return `<h2 class="serif mb-4 text-2xl font-bold">Ringkasan pilihan</h2><div class="divide-y divide-[#E6748E]/10">${rows.map(([k,v])=>`<div class="flex justify-between gap-4 py-3 text-sm"><span class="font-bold">${k}</span><span class="text-right text-[#657674]">${encode(v||'Dilewati')}</span></div>`).join('')}<button id="edit-answers" type="button" class="mt-5 rounded-xl border border-[#6BB1AD]/40 px-4 py-3 text-sm font-bold">Ubah jawaban</button></div>`;
  }
  function showSummary() {
    stage.innerHTML = summary(); reviewed = true;
    $('next').classList.add('hidden'); $('skip').classList.add('hidden');
    $('analyze').textContent = 'Konfirmasi & Analisis →';
    feedback.textContent = 'Periksa kembali jawaban sebelum melanjutkan.';
    $('edit-answers').onclick = () => { feedback.textContent = ''; update(); };
    refreshCount();
  }

  $('back').onclick = () => { if (current > 0) { current--; feedback.textContent = ''; update(); } };
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
    const notice = resetNotice(sanitize());  // draf lama bisa berisi kombinasi yang sudah tidak valid
    if (notice) feedback.textContent = notice;
    save();
    $('loading').classList.add('hidden'); stage.classList.remove('hidden'); update();
  }).catch(e => { $('loading').classList.add('hidden'); $('error').classList.remove('hidden'); $('error').textContent = e.message; });
})();
</script>
</body></html>
