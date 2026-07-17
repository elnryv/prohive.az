// Paylaşılan elan səhifəsi (/e/{code}) — foto lightbox. Ayrı fayldır (app.js deyil),
// çünki app.js SW qeydiyyatı/quraşdırma təklifi/SSE kimi yalnız giriş etmiş
// istifadəçilərə aid məntiq daşıyır, anonim ziyarətçi üçün lazım deyil.
(function () {
  'use strict';
  var lightbox = document.getElementById('photo-lightbox');
  var lightboxImg = document.getElementById('photo-lightbox-img');
  if (!lightbox || !lightboxImg) return;
  document.querySelectorAll('.photo-strip img').forEach(function (img) {
    img.addEventListener('click', function () {
      lightboxImg.src = img.src;
      lightbox.hidden = false;
    });
  });
  var close = function () { lightbox.hidden = true; lightboxImg.src = ''; };
  var closeBtn = document.getElementById('photo-lightbox-close');
  if (closeBtn) closeBtn.addEventListener('click', close);
  lightbox.addEventListener('click', function (e) { if (e.target === lightbox) close(); });
})();
