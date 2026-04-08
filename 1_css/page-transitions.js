/**
 * page-transitions.js
 * MitigaPro — Smooth Page Transitions & Scroll Animations
 */
(function () {
    'use strict';

    // ── Progress bar for page navigation ──
    const bar = document.createElement('div');
    bar.className = 'progress-bar';
    bar.style.width = '0';
    document.body.prepend(bar);

    // Animate bar on load
    bar.style.width = '100%';
    setTimeout(() => { bar.style.opacity = '0'; }, 500);
    setTimeout(() => { bar.remove(); }, 800);

    // ── Scroll Reveal (IntersectionObserver) ──
    function initScrollReveal() {
        // Auto-tag elements for animation
        const selectors = [
            '.card', '.dinas-card', '.wcard', '.filter-bar',
            '.page-header', '.info-banner', '.identity-banner',
            '.id-stats', '.quick-nav', '.section-title',
            '.berita-card', '.news-card'
        ];

        const els = document.querySelectorAll(selectors.join(','));
        els.forEach((el, i) => {
            if (!el.classList.contains('anim-ready') && !el.dataset.animated) {
                el.dataset.animated = '1';
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                el.style.transitionDelay = Math.min(i * 0.06, 0.5) + 's';
            }
        });

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.08, rootMargin: '0px 0px -40px 0px' }
        );

        els.forEach((el) => observer.observe(el));
    }

    // ── Counter animation for stat numbers ──
    function animateCounters() {
        document.querySelectorAll('.id-stat-num, .count-badge, .stat-number').forEach(el => {
            const text = el.textContent.trim();
            const num = parseInt(text, 10);
            if (isNaN(num) || num < 1) return;

            el.textContent = '0';
            const duration = 1200;
            const start = performance.now();

            function tick(now) {
                const elapsed = now - start;
                const progress = Math.min(elapsed / duration, 1);
                // ease-out cubic
                const eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.round(num * eased);
                if (progress < 1) requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
        });
    }

    // ── Smooth page transitions on link click ──
    function initPageTransitions() {
        document.addEventListener('click', function (e) {
            const link = e.target.closest('a[href]');
            if (!link) return;

            const href = link.getAttribute('href');
            // Skip: external, hash, javascript, new tab, onclick with confirm
            if (!href ||
                href.startsWith('#') ||
                href.startsWith('javascript:') ||
                href.startsWith('http') && !href.includes(location.hostname) ||
                link.target === '_blank' ||
                link.hasAttribute('download') ||
                link.getAttribute('onclick')?.includes('confirm') ||
                e.ctrlKey || e.metaKey || e.shiftKey) {
                return;
            }

            e.preventDefault();

            // Create exit progress bar
            const exitBar = document.createElement('div');
            exitBar.className = 'progress-bar';
            exitBar.style.width = '0';
            document.body.prepend(exitBar);

            // Animate content out
            const content = document.getElementById('mainContent') || document.querySelector('.main-content');
            if (content) {
                content.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                content.style.opacity = '0';
                content.style.transform = 'translateY(-15px)';
            }

            // Progress bar animation
            requestAnimationFrame(() => {
                exitBar.style.width = '70%';
            });

            setTimeout(() => {
                exitBar.style.width = '100%';
                setTimeout(() => {
                    window.location.href = href;
                }, 150);
            }, 200);
        });
    }

    // ── Parallax-like scroll effect on banner ──
    function initParallax() {
        const banner = document.querySelector('.identity-banner');
        if (!banner) return;

        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;
            const rate = scrollY * 0.15;
            if (rate < 80) {
                banner.style.transform = 'translateY(' + rate + 'px)';
                banner.style.opacity = Math.max(1 - scrollY / 600, 0.4);
            }
        }, { passive: true });
    }

    // ── Table row hover wave effect ──
    function initTableEffects() {
        document.querySelectorAll('.tbl tbody tr').forEach(row => {
            row.addEventListener('mouseenter', () => {
                row.style.transition = 'transform 0.2s ease, background 0.2s';
                row.style.transform = 'scale(1.005)';
                row.style.zIndex = '1';
                row.style.position = 'relative';
            });
            row.addEventListener('mouseleave', () => {
                row.style.transform = 'scale(1)';
                row.style.zIndex = '';
            });
        });
    }

    // ── Back to top FAB ──
    function initBackToTop() {
        // Only show if page is scrollable
        if (document.body.scrollHeight <= window.innerHeight + 200) return;

        const fab = document.createElement('button');
        fab.className = 'fab';
        fab.innerHTML = '<i class="fas fa-arrow-up"></i>';
        fab.title = 'Kembali ke atas';
        fab.style.opacity = '0';
        fab.style.pointerEvents = 'none';
        document.body.appendChild(fab);

        fab.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                fab.style.opacity = '1';
                fab.style.pointerEvents = 'auto';
                fab.style.transform = 'scale(1)';
            } else {
                fab.style.opacity = '0';
                fab.style.pointerEvents = 'none';
                fab.style.transform = 'scale(0.5)';
            }
        }, { passive: true });
    }

    // ── Initialize everything ──
    document.addEventListener('DOMContentLoaded', function () {
        initScrollReveal();
        animateCounters();
        initPageTransitions();
        initParallax();
        initTableEffects();
        initBackToTop();
    });

})();
