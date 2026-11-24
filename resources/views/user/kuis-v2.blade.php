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
        <div class="mb-8"><div class="mb-2 flex justify-between text-xs font-bold"><span id="step-label">Tahap 1 dari 6</span><span id="progress-label">17%</span></div><div class="h-2 overflow-hidden rounded-full bg-[#FDEEF1]"><div id="progress" class="h-full rounded-full bg-[#6BB1AD] transition-all" style="width:16.67%"></div></div></div>
        <div id="loading" class="py-12 text-center text-sm text-[#657674]">Memuat pilihan dari katalog ontologi…</div>
        <div id="error" class="hidden rounded-2xl bg-red-50 p-4 text-sm text-red-700"></div>
        <div id="stage" class="hidden min-h-64"></div>
        <div id="feedback" class="mt-4 min-h-5 text-sm font-semibold text-[#E6748E]" role="status"></div>
        <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-[#E6748E]/10 pt-5">
            <button id="back" class="rounded-xl border border-[#6BB1AD]/30 px-5 py-3 text-sm font-bold text-[#345452] disabled:opacity-40">Kembali</button>
            <div class="flex flex-wrap gap-2">
                <button id="skip" class="rounded-xl px-4 py-3 text-sm font-bold text-[#657674] hover:bg-[#FDEEF1]">Lewati</button>
                <button id="next" class="rounded-xl bg-[#E6748E] px-5 py-3 text-sm font-bold text-white hover:bg-[#d95f7b]">Selanjutnya</button>
                <button id="analyze" class="hidden rounded-xl bg-[#345452] px-5 py-3 text-sm font-bold text-white hover:bg-[#263f3e]">Analisis Sekarang →</button></div>
        </div>
    </section>
    <p class="mt-5 text-center text-xs text-[#657674]">Pilihan kuis dimuat dari ontologi Aphrodite. Rentang harga menggunakan Rupiah.</p>
</main>
<script>
(() => {
  const root=document.querySelector('#quiz'),stage=document.querySelector('#stage'),feedback=document.querySelector('#feedback'),steps=['Kelompok umur','Jenis kulit','Keluhan kulit','Kategori produk','Bahan aktif','Rentang harga'];
  const keys=['age','skin','concern','category','ingredient','price']; let current=0,busy=false,options=null,reviewed=false;
  const defaults={age:'',skin:'',concern:[],category:'',ingredient:'',price_min:'',price_max:''};
  const startNew=new URLSearchParams(location.search).get('new')==='1';
  if(startNew)localStorage.removeItem('aphrodite-quiz-draft');
  let answers={...defaults}; try{answers={...answers,...JSON.parse(localStorage.getItem('aphrodite-quiz-draft')||'{}')}}catch{}
  const $=id=>document.getElementById(id); const save=()=>localStorage.setItem('aphrodite-quiz-draft',JSON.stringify(answers));
  const encode=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  function update(){ reviewed=false;$('step-label').textContent=`Tahap ${current+1} dari 6 · ${steps[current]}`;let p=Math.round((current+1)/6*100);$('progress').style.width=p+'%';$('progress-label').textContent=p+'%';$('back').disabled=current===0;$('next').classList.toggle('hidden',current===5);$('analyze').classList.remove('hidden');$('analyze').textContent='Analisis Sekarang →';$('skip').classList.remove('hidden');render(); }
  function choose(group,key,multiple=false){return `<div class="grid gap-3 sm:grid-cols-2">${group.map(o=>{let val=encode(o.value),sel=multiple?answers[key].includes(o.value):answers[key]===o.value;return `<label class="flex cursor-pointer items-center gap-3 rounded-2xl border ${sel?'border-[#6BB1AD] bg-[#B8E8EE]/30':'border-[#E6748E]/15 bg-[#FFFEFA]'} p-4 hover:border-[#6BB1AD]"><input class="accent-[#6BB1AD]" type="checkbox" name="${key}" value="${val}" ${sel?'checked':''}><span class="text-sm font-semibold">${encode(o.label)}</span></label>`}).join('')}</div>`}
  function render(){ let html='';if(current===0)html=`<h2 class="serif mb-2 text-2xl font-bold">Kelompok umur</h2><p class="mb-5 text-sm text-[#657674]">Pilih kelompok yang paling sesuai.</p>${choose(options.age_groups,'age')}`;
    if(current===1)html=`<h2 class="serif mb-2 text-2xl font-bold">Jenis kulit</h2><p class="mb-5 text-sm text-[#657674]">Pilih tipe kulit yang tercatat pada data produk.</p>${choose(options.skin_types,'skin')}`;
    if(current===2)html=`<h2 class="serif mb-2 text-2xl font-bold">Keluhan kulit</h2><p class="mb-5 text-sm text-[#657674]">Pilih hingga dua keluhan. <b>${answers.concern.length}/2 dipilih</b></p>${choose(options.concerns,'concern',true)}`;
    if(current===3)html=`<h2 class="serif mb-2 text-2xl font-bold">Kategori produk</h2><p class="mb-5 text-sm text-[#657674]">Kategori dimuat dari ontologi.</p>${choose(options.categories,'category')}`;
    if(current===4)html=`<h2 class="serif mb-2 text-2xl font-bold">Bahan aktif</h2><p class="mb-5 text-sm text-[#657674]">Kamu dapat memilih satu preferensi kandungan.</p>${choose(options.ingredients,'ingredient')}`;
    if(current===5)html=`<h2 class="serif mb-2 text-2xl font-bold">Rentang harga</h2><p class="mb-5 text-sm text-[#657674]">Pilih rentang atau lewati untuk semua harga.</p><div class="grid gap-3 sm:grid-cols-2">${[['','50000','Di bawah Rp50.000'],['50000','100000','Rp50.000–Rp100.000'],['100000','150000','Rp100.000–Rp150.000'],['150000','','Di atas Rp150.000']].map(([min,max,label])=>`<button type="button" data-min="${min}" data-max="${max}" class="price rounded-2xl border ${(answers.price_min===min&&answers.price_max===max)?'border-[#6BB1AD] bg-[#B8E8EE]/30':'border-[#E6748E]/15'} p-4 text-left text-sm font-semibold">${label}</button>`).join('')}</div><div class="mt-5 grid grid-cols-2 gap-3"><label class="text-xs font-bold">Minimum (Rp)<input id="pmin" type="number" min="0" class="mt-1 w-full rounded-xl border p-3" value="${answers.price_min}"></label><label class="text-xs font-bold">Maksimum (Rp)<input id="pmax" type="number" min="0" class="mt-1 w-full rounded-xl border p-3" value="${answers.price_max}"></label></div>`;
    stage.innerHTML=html; stage.querySelectorAll('input[type=checkbox]').forEach(x=>x.onchange=()=>{if(x.name==='concern'){if(x.checked&&answers.concern.length>=2){x.checked=false;feedback.textContent='Maksimal dua keluhan dapat dipilih.';return}answers.concern=x.checked?[...answers.concern,x.value]:answers.concern.filter(v=>v!==x.value);feedback.textContent=answers.concern.length===2?'Batas dua keluhan tercapai.':''}else{answers[x.name]=x.checked?x.value:'';feedback.textContent=''}save();render()});stage.querySelectorAll('.price').forEach(x=>x.onclick=()=>{const same=answers.price_min===x.dataset.min&&answers.price_max===x.dataset.max;answers.price_min=same?'':x.dataset.min;answers.price_max=same?'':x.dataset.max;save();render()});for(const [id,key] of [['pmin','price_min'],['pmax','price_max']]){const el=$(id);if(el)el.oninput=()=>{answers[key]=el.value;save()}} }
  function summary(){let rows=[['Umur',answers.age],['Jenis kulit',answers.skin],['Keluhan',answers.concern.join(', ')],['Kategori',answers.category],['Bahan aktif',answers.ingredient],['Harga',answers.price_min||answers.price_max?`Rp${answers.price_min||'0'} – Rp${answers.price_max||'tanpa batas'}`:'Semua harga']];return `<h2 class="serif mb-4 text-2xl font-bold">Ringkasan pilihan</h2><div class="divide-y divide-[#E6748E]/10">${rows.map(([k,v])=>`<div class="flex justify-between gap-4 py-3 text-sm"><span class="font-bold">${k}</span><span class="text-right text-[#657674]">${encode(v||'Dilewati')}</span></div>`).join('')}<button id="edit-answers" type="button" class="mt-5 rounded-xl border border-[#6BB1AD]/40 px-4 py-3 text-sm font-bold">Ubah jawaban</button></div>`}
  $('back').onclick=()=>{if(current>0){current--;feedback.textContent='';update()}};$('next').onclick=()=>{if(current<5){current++;feedback.textContent='';update()}};$('skip').onclick=()=>{if(current===2)answers.concern=[];else if(current===0)answers.age='';else if(current===1)answers.skin='';else if(current===3)answers.category='';else if(current===4)answers.ingredient='';else {answers.price_min='';answers.price_max=''}save();if(current<5){current++;update()}else{stage.innerHTML=summary()}};
  $('analyze').onclick=async()=>{if(busy)return;if(!reviewed){stage.innerHTML=summary();reviewed=true;$('next').classList.add('hidden');$('skip').classList.add('hidden');$('analyze').textContent='Konfirmasi & Analisis →';feedback.textContent='Periksa kembali jawaban sebelum melanjutkan.';$('edit-answers').onclick=()=>{reviewed=false;update()};return}busy=true;$('analyze').disabled=true;$('analyze').textContent='Menganalisis…';try{const response=await fetch(root.dataset.submit,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify(answers)});const data=await response.json();if(!response.ok)throw new Error(data.message||'Pilihan tidak valid. Muat ulang kuis.');const history=JSON.parse(localStorage.getItem('aphrodite-quiz-history')||'[]');history.unshift({answers,created_at:new Date().toISOString()});localStorage.setItem('aphrodite-quiz-history',JSON.stringify(history.slice(0,5)));localStorage.removeItem('aphrodite-quiz-draft');const params=new URLSearchParams();for(const k of ['age','skin','category','ingredient','price_min','price_max'])if(answers[k])params.set(k,answers[k]);answers.concern.forEach(x=>params.append('concern[]',x));location.href='/rekomendasi?'+params.toString()}catch(e){feedback.textContent=e.message;busy=false;$('analyze').disabled=false;$('analyze').textContent='Analisis Sekarang →'}};
  fetch(root.dataset.options).then(async r=>{let data=await r.json();if(!r.ok)throw new Error(data.message||'Gagal memuat pilihan.');options=data;for(const k of ['age_groups','skin_types','categories','concerns','ingredients'])if(!options[k]?.length)throw new Error('Data pilihan ontologi belum tersedia.');$('loading').classList.add('hidden');stage.classList.remove('hidden');update()}).catch(e=>{$('loading').classList.add('hidden');$('error').classList.remove('hidden');$('error').textContent=e.message});
})();
</script>
</body></html>
