/* =========================================================
   Katalog Aphrodite — interaksi khusus halaman /katalog (Orang 5)
   - Drawer filter (mobile/tablet) & pintasan "/" ke pencarian.
   - Search, filter, sorting, dan pagination diterapkan tanpa muat ulang (progressive enhancement):
     tanpa JavaScript, #katalog-form tetap berupa form GET biasa.
   ========================================================= */
(function () {
    'use strict';

    document.documentElement.classList.add('k-js');

    const panel = document.getElementById('katalog-filter');
    const overlay = document.querySelector('.k-overlay');
    const openers = document.querySelectorAll('[data-filter-open]');
    const closers = document.querySelectorAll('[data-filter-close]');
    const search = document.getElementById('katalog-q');
    const desktop = window.matchMedia('(min-width: 1024px)');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let lastFocus = null;

    /* ---------- Drawer filter ---------- */
    function isOpen() {
        return !!panel && panel.classList.contains('is-open');
    }

    function setOpen(open) {
        if (!panel) return;
        panel.classList.toggle('is-open', open);
        overlay && overlay.classList.toggle('is-open', open);
        document.body.classList.toggle('k-lock', open);
        openers.forEach((btn) => btn.setAttribute('aria-expanded', String(open)));

        if (open) {
            lastFocus = document.activeElement;
            panel.setAttribute('role', 'dialog');
            panel.setAttribute('aria-modal', 'true');
            const first = panel.querySelector('button, summary, [href], input:not(:disabled), select');
            first && first.focus();
        } else {
            panel.removeAttribute('role');
            panel.removeAttribute('aria-modal');
            lastFocus && lastFocus.focus();
        }
    }

    openers.forEach((btn) => btn.addEventListener('click', () => setOpen(true)));
    closers.forEach((el) => el.addEventListener('click', () => setOpen(false)));

    // Tutup drawer bila layar melebar ke desktop.
    desktop.addEventListener('change', (e) => { if (e.matches && isOpen()) setOpen(false); });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen()) {
            setOpen(false);
            return;
        }
        // Tekan "/" untuk langsung ke kolom pencarian.
        const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName);
        if (e.key === '/' && !typing && search) {
            e.preventDefault();
            search.focus();
        }
    });

    /* ---------- Search & filter tanpa muat ulang ---------- */
    const form = document.getElementById('katalog-form');
    const results = document.getElementById('katalog-results');
    const listing = document.getElementById('katalog-listing');
    if (!form || !results || !window.fetch || !window.DOMParser || !window.URLSearchParams) return;

    const sortSelect = document.getElementById('katalog-sort');
    const SEARCH_DELAY = 400;
    let controller = null;
    let timer = null;
    let seq = 0;
    let currentUrl = window.location.href;

    /**
     * URL dari isi form (search & sort ikut lewat atribut form="katalog-form"); format sama dengan GET biasa.
     * "page" sengaja tidak ada di form: setiap perubahan search/filter/sort kembali ke halaman 1.
     */
    function formUrl() {
        const params = new URLSearchParams();
        new FormData(form).forEach((value, key) => {
            const v = String(value).trim();
            if ((key === 'q' || key === 'sort') && v === '') return;
            params.append(key, key === 'q' ? v : value);
        });
        const qs = params.toString();
        // Origin selalu dari halaman ini (bukan APP_URL) agar fetch tetap same-origin.
        return window.location.origin + new URL(form.action, window.location.href).pathname + (qs ? '?' + qs : '');
    }

    function setLoading(on) {
        listing && listing.classList.toggle('is-loading', on);
        results.setAttribute('aria-busy', String(on));
    }

    function bump(el) {
        if (reduceMotion.matches) return;
        el.classList.remove('is-bump');
        void el.offsetWidth; // restart animasi
        el.classList.add('is-bump');
    }

    /** Samakan opsi filter dengan dokumen baru tanpa mengganti elemen (fokus tidak hilang). */
    function syncForm(doc, syncSearch) {
        const freshForm = doc.getElementById('katalog-form');
        if (!freshForm) return;

        const inputs = form.querySelectorAll('input[type="checkbox"]');
        const freshInputs = freshForm.querySelectorAll('input[type="checkbox"]');
        if (inputs.length !== freshInputs.length) {
            // Struktur berbeda (mis. Fuseki baru pulih / mati): ganti seluruh isi form.
            form.innerHTML = freshForm.innerHTML;
        } else {
            inputs.forEach((input, i) => {
                const fresh = freshInputs[i];
                if (fresh.name !== input.name || fresh.value !== input.value) return;
                input.checked = fresh.checked;
                input.disabled = fresh.disabled;

                const label = input.closest('.k-option');
                const freshLabel = fresh.closest('.k-option');
                if (!label || !freshLabel) return;
                label.className = freshLabel.className;

                const count = label.querySelector('.k-option__count');
                const freshCount = freshLabel.querySelector('.k-option__count');
                if (count && freshCount && count.textContent !== freshCount.textContent) {
                    count.innerHTML = freshCount.innerHTML;
                    bump(count);
                }
            });
        }

        if (syncSearch && search) {
            const freshSearch = doc.getElementById('katalog-q');
            search.value = freshSearch ? freshSearch.value : '';
        }

        const freshSort = doc.getElementById('katalog-sort');
        if (sortSelect && freshSort && sortSelect.value !== freshSort.value) sortSelect.value = freshSort.value;
    }

    /** Pesan gagal memuat (jaringan/server) dengan tombol coba lagi; hasil lama disembunyikan. */
    function showFailure(url, opts) {
        const box = document.createElement('div');
        box.className = 'k-empty k-empty--alert k-fail mt-8';
        box.setAttribute('role', 'alert');

        const title = document.createElement('h3');
        title.className = 'font-serif text-2xl font-bold text-ink';
        title.textContent = 'Hasil belum dapat dimuat';
        const text = document.createElement('p');
        text.className = 'mx-auto mt-3 max-w-md text-sm leading-relaxed text-muted';
        text.textContent = 'Koneksi ke server terputus atau server sedang bermasalah. Periksa koneksi, lalu coba lagi.';

        const actions = document.createElement('div');
        actions.className = 'mt-7 flex flex-wrap justify-center gap-3';
        const retry = document.createElement('button');
        retry.type = 'button';
        retry.className = 'k-btn rounded-xl bg-rose px-5 py-3 text-xs font-black uppercase tracking-wider text-white shadow-md hover:bg-teal';
        retry.textContent = 'Coba lagi';
        retry.addEventListener('click', () => load(url, opts));
        const full = document.createElement('a');
        full.href = url;
        full.className = 'k-btn rounded-xl border border-teal/40 px-5 py-3 text-xs font-black uppercase tracking-wider text-ink hover:bg-aqua/30';
        full.textContent = 'Muat ulang halaman';

        actions.append(retry, full);
        box.append(title, text, actions);
        results.replaceChildren(box);
        retry.focus({ preventScroll: true });
    }

    function apply(doc, opts) {
        document.querySelectorAll('[data-katalog-swap][id]').forEach((el) => {
            const fresh = doc.getElementById(el.id);
            if (fresh && el.innerHTML !== fresh.innerHTML) el.innerHTML = fresh.innerHTML;
        });
        syncForm(doc, opts.syncSearch);
    }

    /** Gulir ke awal daftar bila hasil sudah di luar layar (bukan saat mengetik search). */
    function revealResults(opts) {
        if (!listing || opts.fromSearch) return;
        if (listing.getBoundingClientRect().top < 0) {
            listing.scrollIntoView({ behavior: reduceMotion.matches ? 'auto' : 'smooth', block: 'start' });
        }
    }

    async function load(url, opts) {
        opts = opts || {};
        controller && controller.abort();
        const ctrl = (controller = new AbortController());
        const id = ++seq; // hanya respons request terbaru yang boleh diterapkan
        setLoading(true);

        try {
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                credentials: 'same-origin',
                signal: ctrl.signal,
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const html = await res.text();
            if (id !== seq) return;
            // Nomor halaman di luar jangkauan dialihkan server ke halaman valid: pakai URL akhirnya.
            const redirected = res.redirected && !!res.url;
            if (redirected) url = res.url;
            const doc = new DOMParser().parseFromString(html, 'text/html');

            apply(doc, opts);
            if (opts.history === 'push' && url !== currentUrl) history.pushState({ katalog: true }, '', url);
            // replace saat mengetik search, atau saat Back/Forward dialihkan server (mis. page=99 → halaman terakhir).
            else if (opts.history === 'replace' || (redirected && url !== window.location.href)) history.replaceState({ katalog: true }, '', url);
            currentUrl = url;

            revealResults(opts);
            if (opts.focusTitle) {
                const title = document.getElementById('katalog-list-title');
                title && title.focus({ preventScroll: true });
            }
        } catch (err) {
            if (err.name === 'AbortError' || id !== seq) return;
            // Jaringan/server bermasalah: tampilkan pesan + tombol coba lagi. URL tetap mencerminkan
            // permintaan pengguna (push/replace) agar "Coba lagi" maupun muat ulang membuka kondisi yang sama.
            if (opts.history === 'push' && url !== currentUrl) history.pushState({ katalog: true }, '', url);
            if (opts.history === 'replace') history.replaceState({ katalog: true }, '', url);
            currentUrl = url;
            showFailure(url, { syncSearch: true });
        } finally {
            if (ctrl === controller) setLoading(false);
        }
    }

    function schedule(delay, opts) {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const url = formUrl();
            if (url !== currentUrl) load(url, opts);
        }, delay);
    }

    form.addEventListener('change', (e) => {
        if (e.target.matches('input[type="checkbox"]')) schedule(0, { history: 'push' });
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        clearTimeout(timer);
        if (isOpen()) setOpen(false);
        const url = formUrl();
        if (url !== currentUrl) load(url, { history: 'push' });
    });

    search && search.addEventListener('input', () => schedule(SEARCH_DELAY, { history: 'replace', fromSearch: true }));

    // Sorting: select berada di luar <form> (atribut form=), jadi event change didengar langsung.
    sortSelect && sortSelect.addEventListener('change', () => schedule(0, { history: 'push' }));

    // Chip hapus-satu, "Hapus semua", Reset, pager, dan tombol reset di empty state.
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[data-katalog-link]');
        if (!link || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        clearTimeout(timer);
        if (isOpen()) setOpen(false);
        load(link.href, { history: 'push', syncSearch: true, focusTitle: true });
    });

    history.replaceState({ katalog: true }, '', currentUrl);
    window.addEventListener('popstate', () => {
        clearTimeout(timer);
        load(window.location.href, { syncSearch: true });
    });
})();
