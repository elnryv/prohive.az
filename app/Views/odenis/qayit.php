<?php
/**
 * @var callable $t
 * @var array{order_id:string,status:string}|null $ilkinHal
 */
require __DIR__ . '/../partials/head.php';
?>
<div class="card glass" style="text-align:center; padding:36px 24px;">
  <div id="odenisSpinner" class="odenis-spinner"></div>
  <div id="odenisIkon" style="font-size:44px; display:none;"></div>
  <h1 id="odenisBasliq" style="margin:16px 0 6px;"><?= htmlspecialchars($t('odenis.qayit.yoxlanilir')) ?></h1>
  <p id="odenisIzah" style="color:var(--text-dim);"></p>
  <a class="btn btn-primary" id="odenisLovheBtn" href="/lovhe" style="display:none; margin-top:18px;"><?= htmlspecialchars($t('odenis.qayit.lovheye_get')) ?></a>
  <button class="btn btn-ghost" id="odenisYenidenBtn" type="button" style="display:none; margin-top:10px;"><?= htmlspecialchars($t('odenis.qayit.yeniden_cehd')) ?></button>
</div>

<script>
var METINLER = {
  yoxlanilir: <?= json_encode($t('odenis.qayit.yoxlanilir'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
  ugurlu: <?= json_encode($t('odenis.qayit.ugurlu'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
  ugurluIzah: <?= json_encode($t('odenis.qayit.ugurlu_izah'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
  ugursuz: <?= json_encode($t('odenis.qayit.ugursuz'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
  ugursuzIzah: <?= json_encode($t('odenis.qayit.ugursuz_izah'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
};

(function () {
  var basliqEl = document.getElementById('odenisBasliq');
  var izahEl = document.getElementById('odenisIzah');
  var spinnerEl = document.getElementById('odenisSpinner');
  var ikonEl = document.getElementById('odenisIkon');
  var lovheBtn = document.getElementById('odenisLovheBtn');
  var yenidenBtn = document.getElementById('odenisYenidenBtn');

  yenidenBtn.addEventListener('click', function () {
    window.location.href = '/profil';
  });

  function gosterUgurlu() {
    spinnerEl.style.display = 'none';
    ikonEl.style.display = 'block';
    ikonEl.textContent = '✅';
    basliqEl.textContent = METINLER.ugurlu;
    izahEl.textContent = METINLER.ugurluIzah;
    lovheBtn.style.display = 'inline-flex';
    setTimeout(function () { window.location.href = '/lovhe'; }, 1800);
  }

  function gosterUgursuz() {
    spinnerEl.style.display = 'none';
    ikonEl.style.display = 'block';
    ikonEl.textContent = '⚠️';
    basliqEl.textContent = METINLER.ugursuz;
    izahEl.textContent = METINLER.ugursuzIzah;
    yenidenBtn.style.display = 'inline-flex';
  }

  var cehd = 0;
  function yoxla() {
    cehd++;
    Birlikde.api('GET', '/odenis/son-hal').then(function (res) {
      if (Birlikde.redirectIfUnauthorized(res.status)) return;

      var hal = res.ok && res.data && res.data.data ? res.data.data.status : null;

      if (hal === 'ugurlu') {
        gosterUgurlu();
        return;
      }
      if (hal === 'ugursuz') {
        gosterUgursuz();
        return;
      }
      if (cehd >= 20) {
        gosterUgursuz();
        return;
      }
      setTimeout(yoxla, 1500);
    });
  }

  yoxla();
})();
</script>
<?php require __DIR__ . '/../partials/foot.php'; ?>
