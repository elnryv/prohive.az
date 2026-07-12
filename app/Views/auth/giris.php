<?php
/**
 * @var callable $t
 */
require __DIR__ . '/../partials/head.php';
?>
<div class="auth-page">
<div class="auth-deck" id="authDeck" data-my-rol="giris">
  <div class="deck-heading">
    <div class="deck-brand"><?php require __DIR__ . '/../partials/logo.php'; ?></div>
    <div class="deck-sub"><?= htmlspecialchars($t('qapi.secim_basliq')) ?></div>
  </div>
  <div class="deck-stack" id="deckStack">
    <div class="deck-card" data-target="giris">
      <div class="deck-card-inner">
        <span class="deck-card-icon">&#128273;</span>
        <span class="deck-card-title"><?= htmlspecialchars($t('qapi.giris_basliq')) ?></span>
        <span class="deck-card-sub"><?= htmlspecialchars($t('qapi.giris_alt')) ?></span>
      </div>
    </div>
    <div class="deck-card" data-target="qeydiyyat">
      <div class="deck-card-inner">
        <span class="deck-card-icon">&#10024;</span>
        <span class="deck-card-title"><?= htmlspecialchars($t('qapi.qeydiyyat_basliq')) ?></span>
        <span class="deck-card-sub"><?= htmlspecialchars($t('qapi.qeydiyyat_alt')) ?></span>
      </div>
    </div>
  </div>
</div>

<div class="auth-form-wrap" id="authFormWrap" hidden>
<div class="card glass">
  <h1><?= htmlspecialchars($t('giris.basliq')) ?></h1>
  <div class="error-box" id="errBox"></div>
  <form id="girisForm">
    <div class="field">
      <label for="telefon"><?= htmlspecialchars($t('giris.telefon')) ?></label>
      <div class="input-icon-wrap">
        <span class="input-icon">&#128222;</span>
        <input type="tel" id="telefon" name="telefon" required autocomplete="tel" placeholder="994501234567">
      </div>
    </div>
    <div class="field">
      <label for="parol"><?= htmlspecialchars($t('giris.parol')) ?></label>
      <div class="input-icon-wrap">
        <span class="input-icon">&#128274;</span>
        <input type="password" id="parol" name="parol" required autocomplete="current-password">
      </div>
    </div>
    <div class="field field-check">
      <input type="checkbox" id="meniXatirla" name="meni_xatirla" value="1" checked>
      <label for="meniXatirla" style="margin:0;"><?= htmlspecialchars($t('giris.meni_xatirla')) ?></label>
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t('giris.duyme')) ?></button>
  </form>
  <p style="text-align:center; margin-top:16px; font-size:14px; color:var(--text-dim);">
    <?= htmlspecialchars($t('nav.qeydiyyat_yoxdur')) ?> <a href="/qeydiyyat"><?= htmlspecialchars($t('ortaq.qeydiyyat')) ?></a>
  </p>
</div>
</div>
</div>
<script>
  Birlikde.initAuthDeck();
</script>
<script>
var XETA_METNI = <?= json_encode($t('ortaq.xeta'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

(function () {
  var form = document.getElementById('girisForm');
  var errBox = document.getElementById('errBox');

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    Birlikde.hideError(errBox);
    var submitBtn = form.querySelector('button[type=submit]');
    submitBtn.disabled = true;

    var body = {
      telefon: document.getElementById('telefon').value,
      parol: document.getElementById('parol').value,
      meni_xatirla: document.getElementById('meniXatirla').checked ? '1' : '0'
    };

    var res = await Birlikde.api('POST', '/giris', body);
    submitBtn.disabled = false;

    if (!res.ok) {
      Birlikde.showError(errBox, (res.data && res.data.error) || XETA_METNI);
      return;
    }

    window.location.href = res.data.rol === 'musteri' ? '/panel' : '/lovhe';
  });
})();
</script>
<?php require __DIR__ . '/../partials/foot.php'; ?>
