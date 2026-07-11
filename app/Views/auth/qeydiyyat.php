<?php
/**
 * @var callable $t
 * @var array $olculer
 * @var string $sozlesmeMetni
 */
require __DIR__ . '/../partials/head.php';
?>
<div class="card glass">
  <h1><?= htmlspecialchars($t('qeydiyyat.basliq')) ?></h1>
  <div class="error-box" id="errBox"></div>

  <div class="tabs" id="rolTabs">
    <div class="tab active" data-rol="musteri"><?= htmlspecialchars($t('qeydiyyat.rol_musteri')) ?></div>
    <div class="tab" data-rol="kurye"><?= htmlspecialchars($t('qeydiyyat.rol_kurye')) ?></div>
    <div class="tab" data-rol="yukdasima"><?= htmlspecialchars($t('qeydiyyat.rol_yukdasima')) ?></div>
  </div>

  <form id="qeydiyyatForm">
    <input type="hidden" name="rol" id="rolInput" value="musteri">

    <div class="field">
      <label for="ad"><?= htmlspecialchars($t('qeydiyyat.ad')) ?></label>
      <input type="text" id="ad" name="ad" required>
    </div>
    <div class="field">
      <label for="soyad"><?= htmlspecialchars($t('qeydiyyat.soyad')) ?></label>
      <input type="text" id="soyad" name="soyad" required>
    </div>
    <div class="field">
      <label for="telefon"><?= htmlspecialchars($t('qeydiyyat.telefon')) ?></label>
      <input type="tel" id="telefon" name="telefon" required placeholder="994501234567">
    </div>
    <div class="field">
      <label for="whatsapp"><?= htmlspecialchars($t('qeydiyyat.whatsapp')) ?></label>
      <input type="tel" id="whatsapp" name="whatsapp" required placeholder="994501234567">
    </div>
    <div class="field">
      <label for="parol"><?= htmlspecialchars($t('qeydiyyat.parol')) ?></label>
      <input type="password" id="parol" name="parol" required autocomplete="new-password" minlength="6">
    </div>

    <div class="field" id="neqliyyatField" style="display:none;">
      <label for="neqliyyat"><?= htmlspecialchars($t('qeydiyyat.neqliyyat')) ?></label>
      <select id="neqliyyat" name="neqliyyat">
        <option value="avtomobil"><?= htmlspecialchars($t('qeydiyyat.neqliyyat.avtomobil')) ?></option>
        <option value="moto"><?= htmlspecialchars($t('qeydiyyat.neqliyyat.moto')) ?></option>
        <option value="skuter"><?= htmlspecialchars($t('qeydiyyat.neqliyyat.skuter')) ?></option>
        <option value="velosiped"><?= htmlspecialchars($t('qeydiyyat.neqliyyat.velosiped')) ?></option>
        <option value="piyada"><?= htmlspecialchars($t('qeydiyyat.neqliyyat.piyada')) ?></option>
      </select>
    </div>

    <div class="field" id="olculerField" style="display:none;">
      <label><?= htmlspecialchars($t('qeydiyyat.olculer')) ?></label>
      <?php foreach ($olculer as $olcu): ?>
        <div class="field-check" style="margin-bottom:8px;">
          <input type="checkbox" name="olculer[]" value="<?= (int) $olcu['id'] ?>" id="olcu<?= (int) $olcu['id'] ?>">
          <label for="olcu<?= (int) $olcu['id'] ?>" style="margin:0;">
            <?= htmlspecialchars($olcu['kod']) ?> · <?= htmlspecialchars((string) $olcu['uzunluq']) ?> · <?= htmlspecialchars((string) $olcu['tutum']) ?>
          </label>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="field glass" style="padding:12px; max-height:120px; overflow-y:auto; font-size:12px; color:var(--text-dim);">
      <?= htmlspecialchars($sozlesmeMetni) ?>
      <div style="margin-top:8px;">
        <a href="/huquqi/istifadeci-muqavilesi" target="_blank" rel="noopener"><?= htmlspecialchars($t('huquqi.tam_metni_oxu')) ?> — <?= htmlspecialchars($t('qeydiyyat.sozlesme_qebul')) ?></a><br>
        <a href="/huquqi/mexfilik-siyaseti" target="_blank" rel="noopener"><?= htmlspecialchars($t('huquqi.basliq')) ?>: Məxfilik Siyasəti</a>
      </div>
    </div>
    <div class="field field-check">
      <input type="checkbox" id="sozlesme" name="sozlesme_qebul" value="1" required>
      <label for="sozlesme" style="margin:0;"><?= htmlspecialchars($t('qeydiyyat.sozlesme_qebul')) ?></label>
    </div>

    <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t('qeydiyyat.duyme')) ?></button>
  </form>

  <p style="text-align:center; margin-top:16px; font-size:14px; color:var(--text-dim);">
    <?= htmlspecialchars($t('nav.giris_var')) ?> <a href="/giris"><?= htmlspecialchars($t('ortaq.giris')) ?></a>
  </p>
</div>
<script>
var XETA_METNI = <?= json_encode($t('ortaq.xeta'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

(function () {
  var tabs = document.querySelectorAll('#rolTabs .tab');
  var rolInput = document.getElementById('rolInput');
  var neqliyyatField = document.getElementById('neqliyyatField');
  var olculerField = document.getElementById('olculerField');

  function applyRol(rol) {
    rolInput.value = rol;
    tabs.forEach(function (tab) {
      tab.classList.toggle('active', tab.dataset.rol === rol);
    });
    neqliyyatField.style.display = rol === 'kurye' ? 'block' : 'none';
    olculerField.style.display = rol === 'yukdasima' ? 'block' : 'none';
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      applyRol(tab.dataset.rol);
    });
  });

  var form = document.getElementById('qeydiyyatForm');
  var errBox = document.getElementById('errBox');

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    Birlikde.hideError(errBox);
    var submitBtn = form.querySelector('button[type=submit]');
    submitBtn.disabled = true;

    var formData = new FormData(form);
    var res = await Birlikde.api('POST', '/qeydiyyat', formData);
    submitBtn.disabled = false;

    if (!res.ok) {
      Birlikde.showError(errBox, (res.data && res.data.error) || XETA_METNI);
      return;
    }

    window.location.href = '/giris';
  });
})();
</script>
<?php require __DIR__ . '/../partials/foot.php'; ?>
