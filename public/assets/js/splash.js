// FAZA 21: app.birlikde.biz-in `playSplashOnce()` yanaşmasına uyğunlaşdırıldı — bütün
// açılış ardıcıllığı (mark, radar-halqa, glow, söz-bounce, loadbar) CSS keyframe
// `animation-delay`-lə idarə olunur (bax app.css .splash-* qaydaları). Bu skript
// yalnız sabit bir müddətdən sonra `.splash-hide` sinifini əlavə edib elementi silir —
// canlı hissəcik/faiz hesablaması YOXDUR.
//
// FAZA 25: "göstərilsinmi" qərarı artıq İKİ mənbədən gəlir (bax layouts/app.php-dəki
// izahat): (1) server bayrağı (`data-force="1"` — qeydiyyat/giriş/yaddaş-saxla-bərpası,
// HƏMİŞƏ göstərilir), (2) `sessionStorage` — bu JS icra konteksti (tab/WebView instansı)
// ərzində artıq göstərilibsə, bir də göstərilmir; kontekst HƏQİQƏTƏN yenidən yaradılanda
// (tətbiq öldürülüb yenidən açılanda) sessionStorage sıfırlanır, ona görə YENƏ göstərilir
// — bu, sessiya kukisindən fərqli olaraq (kuki PWA-da tətbiq öldürüləndə də çox vaxt qalır)
// "tam yenidən açılma" halını düzgün aşkarlayır.
(function () {
  var splash = document.getElementById('splash');
  if (!splash) return;

  var forceShow = splash.dataset.force === '1';
  var alreadySeen = false;
  try {
    alreadySeen = sessionStorage.getItem('birlikde_splash_seen') === '1';
  } catch (e) {
    // Private/məhdud brauzer rejimində sessionStorage əlçatmaz ola bilər —
    // bu halda hər dəfə göstərmək (server bayrağına uyğun davranış) daha təhlükəsizdir.
  }

  if (!forceShow && alreadySeen) {
    splash.remove();
    return;
  }

  try {
    sessionStorage.setItem('birlikde_splash_seen', '1');
  } catch (e) {
    // Yazıla bilmirsə səssizcə davam et — splash bu dəfə göstəriləcək, bu zərərsizdir.
  }

  splash.hidden = false;

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  setTimeout(function () {
    splash.classList.add('splash-hide');
    setTimeout(function () { splash.remove(); }, 450);
  }, reduceMotion ? 0 : 1900);
})();
