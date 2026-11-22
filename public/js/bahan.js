/* =========================================================
   Kamus Bahan Aphrodite — interaksi /bahan (Orang 5)
   - Pencarian instan nama bahan (tanpa muat ulang). Tanpa JavaScript, form GET ?q= tetap
     berfungsi: server menyaring lewat SPARQL (IngredientController::SEARCH_QUERY).
   - Navigasi A–Z: huruf aktif mengikuti posisi scroll; huruf tanpa hasil dinonaktifkan.
   ========================================================= */
(function () {
    'use strict';

    const form = document.getElementById('bahan-form');
    const input = document.getElementById('bahan-q');
    const cards = Array.from(document.querySelectorAll('.b-card[data-search]'));
    const sections = Array.from(document.querySelectorAll('.b-section[data-letter]'));
    const letterLinks = Array.from(document.querySelectorAll('a.b-az__link[data-letter]'));
    const count = document.getElementById('bahan-count');
    const empty = document.getElementById('bahan-empty');
    let timer = null;

    // Tekan "/" untuk langsung ke kolom pencarian.
    document.addEventListener('keydown', (e) => {
        const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName);
        if (e.key === '/' && !typing && input) {
            e.preventDefault();
            input.focus();
        }
    });

    if (!form || !input || cards.length === 0) return;

    /** Sama dengan server: dirapikan lalu huruf kecil. */
    const normalize = (value) => value.replace(/\s+/g, ' ').trim().toLowerCase();

    function renderCount(visible, term) {
        if (!count) return;
        count.textContent = '';
        const strong = (text) => { const b = document.createElement('span'); b.className = 'font-bold text-ink tabular-nums'; b.textContent = text; return b; };
        if (term) {
            const quote = document.createElement('span');
            quote.className = 'font-bold text-ink';
            quote.textContent = '“' + term + '”';
            count.append(strong(String(visible)), ' dari ' + cards.length + ' bahan cocok dengan ', quote);
        } else {
            count.append('Menampilkan ', strong(String(cards.length)), ' bahan');
        }
    }

    function apply(rawTerm, updateUrl) {
        const term = normalize(rawTerm);
        let visible = 0;

        cards.forEach((card) => {
            const match = term === '' || card.dataset.search.includes(term);
            card.hidden = !match;
            if (match) visible++;
        });

        const lettersWithResults = new Set();
        sections.forEach((section) => {
            const has = section.querySelector('.b-card:not([hidden])') !== null;
            section.hidden = !has;
            if (has) lettersWithResults.add(section.dataset.letter);
        });

        letterLinks.forEach((link) => {
            const on = lettersWithResults.has(link.dataset.letter);
            link.classList.toggle('is-off', !on);
            if (on) { link.removeAttribute('aria-disabled'); link.removeAttribute('tabindex'); }
            else { link.setAttribute('aria-disabled', 'true'); link.setAttribute('tabindex', '-1'); }
        });

        if (empty) empty.hidden = !(term !== '' && visible === 0);
        renderCount(visible, rawTerm.replace(/\s+/g, ' ').trim());
        updateCurrent();

        if (updateUrl) {
            const url = new URL(window.location.href);
            const clean = rawTerm.replace(/\s+/g, ' ').trim();
            clean ? url.searchParams.set('q', clean) : url.searchParams.delete('q');
            url.hash = '';
            history.replaceState(null, '', url.pathname + url.search);
        }
    }

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => apply(input.value, true), 120);
    });

    // Enter / tombol Cari: hasil sudah tersaring, jadi tidak perlu muat ulang.
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        clearTimeout(timer);
        apply(input.value, true);
    });

    // Huruf yang dinonaktifkan tidak boleh melompat ke seksi tersembunyi.
    letterLinks.forEach((link) => link.addEventListener('click', (e) => {
        if (link.classList.contains('is-off')) e.preventDefault();
    }));

    // Huruf aktif = seksi terakhir yang judulnya sudah melewati bilah A–Z (tepat juga untuk seksi pendek).
    const azBar = document.querySelector('.b-az');
    const byLetter = new Map(letterLinks.map((l) => [l.dataset.letter, l]));
    let current = null;
    let ticking = false;

    function updateCurrent() {
        ticking = false;
        const line = (azBar ? azBar.getBoundingClientRect().bottom : 0) + 48;
        let active = null;
        for (const section of sections) {
            if (section.hidden) continue;
            if (section.getBoundingClientRect().top <= line) active = section.dataset.letter;
            else break;
        }
        if (active === current) return;
        current = active;
        letterLinks.forEach((l) => l.classList.toggle('is-current', l.dataset.letter === active));
        const link = active && byLetter.get(active);
        // Jaga huruf aktif tetap terlihat di bilah A–Z yang bisa digeser (mobile).
        const list = link && link.closest('.b-az__list');
        if (list && list.scrollWidth > list.clientWidth) {
            list.scrollTo({ left: link.offsetLeft - list.clientWidth / 2, behavior: 'auto' });
        }
    }

    window.addEventListener('scroll', () => {
        if (!ticking) { ticking = true; requestAnimationFrame(updateCurrent); }
    }, { passive: true });
    updateCurrent();
})();
