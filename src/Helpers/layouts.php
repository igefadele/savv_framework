<?php

/**
 * Unified Head Helper
 * Injects PWA Meta tags, Manifest, Bootstrap (CSS & Icons), and AOS.
 */
function savv_head() {
    $pwa = config('pwa');
    $root = ROOT_PATH ?: '';
    $themeColor = $pwa['theme_color'] ?? '#000000';
    $appIcon = $pwa['icons'][0]['src'] ?? '';

    ?>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="<?= $themeColor ?>">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="apple-touch-icon" href="<?= $appIcon ?>">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
<?php
}


/**
 * Injects all required JS for PWA, SPA-feel, and UI Animations.
 * No user-side scripts are required for these features to work.
 */
function savv_scripts() {
    $pwa = config('pwa');
    $name = $pwa['short_name'] ?? 'Savv';
    $root = ROOT_PATH ?: '';

    ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>

<script src="https://unpkg.com/swup@4"></script>
<script src="https://unpkg.com/@swup/head-plugin@2"></script>
<script src="https://unpkg.com/@swup/scroll-plugin@3"></script>

<script>
(function() {
    /**
     * INTERNAL UI LOGIC
     * Handles AOS and Counter animations out of the box.
     */
    const runSavvInternalUI = () => {
        // 1. Initialize AOS
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 700,
                easing: 'ease-out-cubic',
                once: true,
                offset: 60
            });
        }

        // 2. Initialize Counters (Intersection Observer)
        const counters = document.querySelectorAll('.counter-element');
        if (counters.length > 0 && 'IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const el = entry.target;
                        const target = parseInt(el.getAttribute('data-target')) || 0;
                        const duration = 2000;
                        let start = 0;
                        const step = (timestamp) => {
                            if (!start) start = timestamp;
                            const progress = Math.min((timestamp - start) / duration, 1);
                            el.innerText = Math.floor(progress * target);
                            if (progress < 1) window.requestAnimationFrame(step);
                        };
                        window.requestAnimationFrame(step);
                        observer.unobserve(el);
                    }
                });
            }, {
                threshold: 0.5
            });
            counters.forEach(el => observer.observe(el));
        }
    };


    // Initialize SPA Engine
    if (typeof Swup !== 'undefined') {
        const swup = new Swup({
            plugins: [new SwupHeadPlugin(), new SwupScrollPlugin({
                doScrolling: true,
                animateScroll: true
            })],
            ignoreVisit: (url, {
                el
            }) => el?.closest('[data-no-swup]') || url === window.location.href
        });

        // Re-run Internal UI logic on every page swap
        swup.hooks.on('page:view', () => {
            if (typeof AOS !== 'undefined') AOS.init();
            window.scrollTo(0, 0);
            runSavvInternalUI();

            // Allow user-defined hooks if they exist, but don't depend on them
            if (typeof window.initPageScripts === 'function') window.initPageScripts();
        });
    }

    // PWA Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', {
                    scope: '<?= $root ?>/'
                })
                .then(reg => console.log('<?= $name ?> Service Worker Active'))
                .catch(err => console.error('PWA Error:', err));
        });

        navigator.serviceWorker.addEventListener("controllerchange", () => {
            console.log("New version available, reloading...");
            window.location.reload();
        });
    }

    // Catch-all for # links
    document.addEventListener('click', (e) => {
        const target = e.target.closest('a');
        if (target && target.getAttribute('href') === '#') e.preventDefault();
    });

    // Initial Run
    document.addEventListener('DOMContentLoaded', runSavvInternalUI);
})();
</script>
<?php
}