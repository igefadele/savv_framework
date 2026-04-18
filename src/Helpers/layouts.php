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
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="https://unpkg.com/swup@4"></script>
<script src="https://unpkg.com/@swup/head-plugin@2"></script>
<script src="https://unpkg.com/@swup/scroll-plugin@3"></script>

<script>
(function() {
    /**
     * INTERNAL UI RE-INITIALIZATION
     * Safely checks for AOS, Counters, and Bootstrap.
     */
    const runSavvInternalUI = () => {
        // 1. AOS (Silent Check)
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 700,
                easing: 'ease-out-cubic',
                once: true,
                offset: 60
            });
        }

        // 2. Bootstrap Dropdown Re-hydration (Awareness, not Dependency)
        // If the user included Bootstrap, we ensure dropdowns work after Swup transitions.
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            document.querySelectorAll('.dropdown-toggle').forEach(el => {
                const existing = bootstrap.Dropdown.getInstance(el);
                if (existing) existing.dispose();
                new bootstrap.Dropdown(el);
            });
        }


        /**
         * 3. THE SAVV WAY: Dispatch a custom event.
         * Users can listen for 'savv:init' in their own scripts to 
         * re-run their logic (like counters) after a page swap.
         */
        document.dispatchEvent(new CustomEvent('savv:init'));
    };

    // Initialize SPA Engine
    if (typeof Swup !== 'undefined') {
        const swup = new Swup({
            containers: ["#savv"],
            plugins: [new SwupHeadPlugin(), new SwupScrollPlugin({
                doScrolling: true,
                animateScroll: true
            })],
            ignoreVisit: (url, {
                el
            }) => {
                // Ignore elements with data-no-swup or hash links
                if (el?.closest('[data-no-swup]')) return true;
                const href = el?.getAttribute('href');
                if (href && (href.startsWith('#') || href.includes('#'))) return true;
                return url === window.location.href;
            }
        });

        swup.hooks.on('page:view', () => {
            runSavvInternalUI();

            // Handle scrolling
            const hash = window.location.hash;
            if (hash) {
                setTimeout(() => {
                    const target = document.querySelector(hash);
                    if (target) target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }, 100);
            } else {
                window.scrollTo(0, 0);
            }
        });
    }

    // PWA Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', {
                    scope: '<?= $root ?>/'
                })
                .then(reg => console.log('<?= $name ?> PWA Active'))
                .catch(err => console.error('PWA Error:', err));
        });
        navigator.serviceWorker.addEventListener("controllerchange", () => window.location.reload());
    }

    // Global Click Listener: Fixes '#' Jump while allowing Bootstrap Dropdowns
    document.addEventListener('click', (e) => {
        const target = e.target.closest('a');
        if (!target) return;
        const href = target.getAttribute('href');

        if (href === '#') {
            // Only stop default if it's NOT a toggle (like a dropdown)
            // This prevents the jump-to-top without breaking JS events
            if (!target.hasAttribute('data-bs-toggle')) {
                e.preventDefault();
            }
        }
    });

    document.addEventListener('DOMContentLoaded', runSavvInternalUI);
})();
</script>
<?php
}