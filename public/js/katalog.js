/* =========================================================
   Katalog Aphrodite — interaksi khusus halaman /katalog (Orang 5)
   Tahap 1: drawer filter (mobile/tablet) & pintasan pencarian.
   ========================================================= */
(function () {
    'use strict';

    const panel = document.getElementById('katalog-filter');
    const overlay = document.querySelector('.k-overlay');
    const openers = document.querySelectorAll('[data-filter-open]');
    const closers = document.querySelectorAll('[data-filter-close]');
    const search = document.getElementById('katalog-q');
    const desktop = window.matchMedia('(min-width: 1024px)');
    let lastFocus = null;

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
            const first = panel.querySelector('button, summary, [href], input, select');
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
    desktop.addEventListener('change', (e) => { if (e.matches && panel && panel.classList.contains('is-open')) setOpen(false); });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && panel && panel.classList.contains('is-open')) {
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
})();
