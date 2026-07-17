<div id="splash" class="splash" hidden>
  <div class="splash-inner">
    <span class="splash-word splash-word-1">Birlikdə</span>
    <span class="splash-word splash-word-2">Yük</span>
    <svg class="splash-truck" viewBox="0 0 64 32" width="64" height="32" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path class="splash-truck-path" d="M2 24 H40 V10 H30 L26 16 H40 M40 24 V14 H50 L58 20 V24 M2 24 H62" stroke="#2F6FED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      <circle class="splash-wheel" cx="16" cy="25" r="3" stroke="#2F6FED" stroke-width="2"/>
      <circle class="splash-wheel" cx="47" cy="25" r="3" stroke="#2F6FED" stroke-width="2"/>
    </svg>
  </div>
</div>
<script>
  // Sessiyada 1 dəfə (bölmə 10.2) — flaş effektindən qaçmaq üçün dərhal (defer olmadan) icra olunur.
  (function () {
    if (!sessionStorage.getItem('yuk_splash_shown')) {
      document.getElementById('splash').hidden = false;
      sessionStorage.setItem('yuk_splash_shown', '1');
      setTimeout(function () {
        var el = document.getElementById('splash');
        if (el) el.remove();
      }, 2000);
    } else {
      document.getElementById('splash').remove();
    }
  })();
</script>
