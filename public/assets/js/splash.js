// FAZA 21: app.birlikde.biz-in `playSplashOnce()` yanaşmasına uyğunlaşdırıldı — bütün
// açılış ardıcıllığı (mark, radar-halqa, glow, söz-bounce, loadbar) CSS keyframe
// `animation-delay`-lə idarə olunur (bax app.css .splash-* qaydaları). Bu skript
// yalnız sabit bir müddətdən sonra `.splash-hide` sinifini əlavə edib elementi silir —
// canlı hissəcik/faiz hesablaması YOXDUR.
//
// "Nə vaxt göstər" qərarı serverdədir (bax layouts/app.php + Auth::establishSession()
// — `#splash` yalnız server bunu qərarlaşdıranda DOM-a yazılır). Element varsa, server
// onu göstərməyə qərar verib deməkdir.
(function () {
  var splash = document.getElementById('splash');
  if (!splash) return;

  splash.hidden = false;

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  setTimeout(function () {
    splash.classList.add('splash-hide');
    setTimeout(function () { splash.remove(); }, 450);
  }, reduceMotion ? 0 : 1900);
})();
