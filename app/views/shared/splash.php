<div id="splash" aria-hidden="true">
    <div class="splash-word splash-word--birlikde">Birlikdə</div>
    <div class="splash-word splash-word--getdik">Getdik</div>
    <svg viewBox="0 0 120 40" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M5 35 L30 15 L45 28 L70 8 L95 30 L115 12" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
</div>
<script>
(function () {
    var splash = document.getElementById('splash');
    if (!splash) { return; }
    try {
        if (sessionStorage.getItem('getdik_splash_shown') === '1') {
            splash.parentNode.removeChild(splash);
            return;
        }
        sessionStorage.setItem('getdik_splash_shown', '1');
    } catch (e) {
        // sessionStorage əlçatan deyilsə (məs. gizli rejim) splash sadəcə bir dəfə görünər
    }

    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reducedMotion) {
        splash.parentNode.removeChild(splash);
        return;
    }

    setTimeout(function () {
        splash.classList.add('is-hiding');
        setTimeout(function () {
            if (splash.parentNode) { splash.parentNode.removeChild(splash); }
        }, 400);
    }, 1900);
})();
</script>
