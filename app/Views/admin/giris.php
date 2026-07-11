<?php
/**
 * @var callable $t
 */
require __DIR__ . '/partials/login_head.php';
?>
<h1 style="font-size:18px; margin-top:0;"><?= htmlspecialchars($t('admin.giris_basliq')) ?></h1>
<div class="error-box" id="errBox"></div>
<form id="girisForm">
  <div class="field">
    <label for="telefon"><?= htmlspecialchars($t('giris.telefon')) ?></label>
    <input type="tel" id="telefon" name="telefon" required autocomplete="tel">
  </div>
  <div class="field">
    <label for="parol"><?= htmlspecialchars($t('giris.parol')) ?></label>
    <input type="password" id="parol" name="parol" required autocomplete="current-password">
  </div>
  <button type="submit" class="btn btn-primary" style="width:100%;"><?= htmlspecialchars($t('giris.duyme')) ?></button>
</form>
<script src="/assets/js/admin.js"></script>
<script>
var XETA_METNI = <?= json_encode($t('ortaq.xeta'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

(function () {
  var form = document.getElementById('girisForm');
  var errBox = document.getElementById('errBox');

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    BirlikdeAdmin.hideError(errBox);
    var submitBtn = form.querySelector('button[type=submit]');
    submitBtn.disabled = true;

    var res = await BirlikdeAdmin.api('POST', '/giris', {
      telefon: document.getElementById('telefon').value,
      parol: document.getElementById('parol').value,
    });
    submitBtn.disabled = false;

    if (!res.ok) {
      BirlikdeAdmin.showError(errBox, (res.data && res.data.error) || XETA_METNI);
      return;
    }

    window.location.href = '/panel';
  });
})();
</script>
<?php require __DIR__ . '/partials/login_foot.php'; ?>
