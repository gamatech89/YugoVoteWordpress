/**
 * Quiz Archive — AJAX category + difficulty filtering, no page reload
 */
document.addEventListener('DOMContentLoaded', function () {
    const page    = document.querySelector('.ygv-quiz-archive-page');
    if (!page) return;

    const filters      = document.getElementById('ygv-archive-filters');
    const grid         = document.getElementById('ygv-archive-grid');
    const pagWrap      = document.getElementById('ygv-archive-pagination');
    const diffSelect   = document.getElementById('ygv-diff-select');
    const catBtns      = filters ? Array.from(filters.querySelectorAll('.ygv-filter-btn[data-cat]')) : [];

    if (!grid || !filters) return;

    const nonce   = filters.dataset.nonce || '';
    const ajaxUrl = (typeof ygvArchivePage !== 'undefined') ? ygvArchivePage.ajaxurl : '';

    const params  = new URLSearchParams(window.location.search);
    let currentCat  = params.get('cat')  || '';
    let currentDiff = params.get('diff') || '';
    let isLoading   = false;

    // ── Load quizzes via AJAX ──────────────────────────────────────────────
    async function loadQuizzes(paged) {
        paged = paged || 1;
        if (isLoading) return;
        isLoading = true;

        grid.style.opacity       = '0.4';
        grid.style.pointerEvents = 'none';

        const body = new FormData();
        body.append('action', 'ygv_archive_filter');
        body.append('nonce',  nonce);
        body.append('cat',    currentCat);
        body.append('diff',   currentDiff);
        body.append('paged',  paged);

        try {
            const res  = await fetch(ajaxUrl, { method: 'POST', body });
            const data = await res.json();

            if (data.success) {
                grid.innerHTML = data.data.html;
                renderPagination(data.data.current, data.data.total_pages);

                // Update URL without reload
                const url = new URL(window.location.href);
                currentCat  ? url.searchParams.set('cat',   currentCat)  : url.searchParams.delete('cat');
                currentDiff ? url.searchParams.set('diff',  currentDiff) : url.searchParams.delete('diff');
                paged > 1   ? url.searchParams.set('paged', paged)       : url.searchParams.delete('paged');
                history.pushState({}, '', url);
            }
        } catch (err) {
            console.error('Quiz filter error:', err);
        } finally {
            grid.style.opacity       = '1';
            grid.style.pointerEvents = '';
            isLoading = false;
        }
    }

    // ── Render pagination client-side ─────────────────────────────────────
    function renderPagination(current, total) {
        if (!pagWrap) return;
        if (total <= 1) { pagWrap.innerHTML = ''; return; }

        let items = '';
        if (current > 1)
            items += `<li><a href="#" data-page="${current - 1}">‹ Prethodna</a></li>`;

        for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || Math.abs(i - current) <= 2) {
                items += i === current
                    ? `<li><span class="current">${i}</span></li>`
                    : `<li><a href="#" data-page="${i}">${i}</a></li>`;
            } else if (Math.abs(i - current) === 3) {
                items += `<li><span class="dots">…</span></li>`;
            }
        }

        if (current < total)
            items += `<li><a href="#" data-page="${current + 1}">Sledeća ›</a></li>`;

        pagWrap.innerHTML = `<nav class="ygv-pagination"><ul>${items}</ul></nav>`;
    }

    // ── Category filter clicks ────────────────────────────────────────────
    catBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            // Reset all buttons
            catBtns.forEach(function (b) {
                b.classList.remove('active');
                b.style.cssText = '';
            });

            // Activate clicked button with its category color
            this.classList.add('active');
            const color = this.dataset.color;
            if (color) {
                this.style.background   = color;
                this.style.borderColor  = color;
                this.style.color        = '#fff';
            }

            currentCat = this.dataset.cat;
            loadQuizzes(1);
        });
    });

    // ── Difficulty dropdown ───────────────────────────────────────────────
    if (diffSelect) {
        diffSelect.addEventListener('change', function () {
            currentDiff = this.value;
            loadQuizzes(1);
        });
    }

    // ── Pagination clicks (delegated) ─────────────────────────────────────
    if (pagWrap) {
        pagWrap.addEventListener('click', function (e) {
            const link = e.target.closest('a[data-page]');
            if (!link) return;
            e.preventDefault();
            loadQuizzes(parseInt(link.dataset.page, 10));
            page.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    // ── Quiz start buttons (delegated — works after AJAX reload) ──────────
    grid.addEventListener('click', function (e) {
        const btn = e.target.closest('.ygv-quiz-start-btn');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();

        const quizId = btn.dataset.quizId;
        if (!quizId || !window.Quiz || !window.quizSettings?.apiUrl) return;
        if (window.currentQuiz?.closeQuiz) window.currentQuiz.closeQuiz();

        try {
            window.currentQuiz = new window.Quiz(window.quizSettings.apiUrl, quizId);
        } catch (err) {
            console.error('Quiz launch error:', err);
        }
    });

    // ── Init: load if URL already has filters (e.g. back-navigation) ──────
    // (initial render is PHP so no AJAX needed on first load)
});
