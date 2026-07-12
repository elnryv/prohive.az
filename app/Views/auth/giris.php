<?php
/**
 * @var callable $t
 */
require __DIR__ . '/../partials/head.php';
?>
<div class="auth-page" data-my-rol="giris">
  <div class="auth-heading">
    <div class="deck-brand"><?php require __DIR__ . '/../partials/logo.php'; ?></div>
    <div class="auth-sub"><?= htmlspecialchars($t('qapi.secim_basliq')) ?></div>
  </div>

  <div class="accordion-item" data-target="giris">
    <button type="button" class="accordion-header">
      <span class="accordion-icon">&#128273;</span>
      <span class="accordion-title"><?= htmlspecialchars($t('qapi.giris_basliq')) ?><span class="accordion-sub"><?= htmlspecialchars($t('qapi.giris_alt')) ?></span></span>
      <span class="accordion-chev">&#8964;</span>
    </button>
    <div class="accordion-body-wrap">
      <div class="accordion-body">
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
      </div>
    </div>
  </div>

  <div class="accordion-item" data-target="qeydiyyat">
    <button type="button" class="accordion-header">
      <span class="accordion-icon">&#10024;</span>
      <span class="accordion-title"><?= htmlspecialchars($t('qapi.qeydiyyat_basliq')) ?><span class="accordion-sub"><?= htmlspecialchars($t('qapi.qeydiyyat_alt')) ?></span></span>
      <span class="accordion-chev">&#8964;</span>
    </button>
    <div class="accordion-body-wrap">
      <div class="accordion-body"></div>
    </div>
  </div>
</div>
<script>
  Birlikde.initAuthAccordion();
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
