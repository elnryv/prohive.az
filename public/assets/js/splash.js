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
