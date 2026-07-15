<?php
/**
 * @var callable $t
 * @var array $olculer
 * @var string $sozlesmeMetni
 */
require __DIR__ . '/../partials/head.php';
?>
<div class="auth-page">
<div class="auth-wizard" id="authWizard">

  <div class="auth-step" data-step="phone">
    <div class="wizard-logo"><?php require __DIR__ . '/../partials/logo.php'; ?></div>
    <h1 class="wizard-title"><?= htmlspecialchars($t('qapi.xos_gelmisen')) ?></h1>
    <p class="wizard-sub"><?= htmlspecialchars($t('qapi.telefon_izah')) ?></p>

    <div class="error-box" id="phoneErrBox"></div>

    <form id="phoneForm">
      <div class="phone-input-group" id="phoneInputGroup">
        <select class="country-select" id="countrySelect" aria-label="Ölkə kodu"></select>
        <input type="tel" id="phoneDigits" inputmode="numeric" autocomplete="tel-national" placeholder="50 123 45 67" maxlength="12" required>
      </div>
      <div class="field-helper" id="phoneHelper"></div>
      <button type="submit" class="btn btn-primary" id="phoneContinueBtn" style="margin-top:14px;"><?= htmlspecialchars($t('qapi.davam_et')) ?></button>
    </form>
  </div>

  <div class="auth-step" data-step="login" hidden>
    <div class="wizard-phone-chip">
      <span id="loginPhoneDisplay"></span>
      <button type="button" class="wizard-change-btn" data-back><?= htmlspecialchars($t('qapi.deyis')) ?></button>
    </div>
    <h1 class="wizard-title"><?= htmlspecialchars($t('qapi.giris_xosgeldin')) ?></h1>

    <div class="error-box" id="errBox"></div>
    <form id="girisForm">
      <div class="field">
        <label for="parol"><?= htmlspecialchars($t('giris.parol')) ?></label>
        <div class="password-field-wrap">
          <span class="input-icon">&#128274;</span>
          <input type="password" id="parol" name="parol" required autocomplete="current-password">
          <button type="button" class="password-toggle" data-toggle-for="parol" aria-label="Parolu göstər">&#128065;</button>
        </div>
      </div>
      <div class="field field-check">
        <input type="checkbox" id="meniXatirla" name="meni_xatirla" value="1" checked>
        <label for="meniXatirla" style="margin:0;"><?= htmlspecialchars($t('giris.meni_xatirla')) ?></label>
      </div>
      <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t('giris.duyme')) ?></button>
    </form>
  </div>

  <div class="auth-step" data-step="redirecting" hidden>
    <div class="wizard-redirect">
      <div class="wizard-redirect-dots">
        <span class="wizard-redirect-dot"></span>
        <span class="wizard-redirect-dot"></span>
        <span class="wizard-redirect-dot"></span>
      </div>
      <p class="wizard-redirect-text"><?= htmlspecialchars($t('qapi.qeydiyyata_yonlendirilir')) ?></p>
    </div>
  </div>

  <div class="auth-step" data-step="register" hidden>
    <div class="wizard-phone-chip">
      <span id="registerPhoneDisplay"></span>
      <button type="button" class="wizard-change-btn" data-back><?= htmlspecialchars($t('qapi.deyis')) ?></button>
    </div>
    <h1 class="wizard-title"><?= htmlspecialchars($t('qapi.qeydiyyat_xosgeldin')) ?></h1>

    <div class="error-box" id="qeydErrBox"></div>

    <div class="tabs" id="rolTabs">
      <div class="tab active" data-rol="musteri"><?= htmlspecialchars($t('qeydiyyat.rol_musteri')) ?></div>
      <div class="tab" data-rol="kurye"><?= htmlspecialchars($t('qeydiyyat.rol_kurye')) ?></div>
      <div class="tab" data-rol="yukdasima"><?= htmlspecialchars($t('qeydiyyat.rol_yukdasima')) ?></div>
    </div>

    <form id="qeydiyyatForm">
      <input type="hidden" name="rol" id="rolInput" value="musteri">
      <input type="hidden" name="telefon" id="qeydTelefonHidden" value="">

      <div class="field">
        <label for="ad"><?= htmlspecialchars($t('qeydiyyat.ad')) ?></label>
        <input type="text" id="ad" name="ad" required>
        <div class="field-helper" id="adHelper"></div>
      </div>
      <div class="field">
        <label for="soyad"><?= htmlspecialchars($t('qeydiyyat.soyad')) ?></label>
        <input type="text" id="soyad" name="soyad" required>
        <div class="field-helper" id="soyadHelper"></div>
      </div>
      <div class="field">
        <label for="whatsapp"><?= htmlspecialchars($t('qeydiyyat.whatsapp')) ?></label>
        <div class="input-icon-wrap">
          <span class="input-icon">&#128172;</span>
          <input type="tel" id="whatsapp" name="whatsapp" required placeholder="994501234567">
        </div>
        <div class="field-helper" id="whatsappHelper"></div>
      </div>
      <div class="field">
        <label for="qeydParol"><?= htmlspecialchars($t('qeydiyyat.parol')) ?></label>
        <div class="password-field-wrap">
          <span class="input-icon">&#128274;</span>
          <input type="password" id="qeydParol" name="parol" required autocomplete="new-password" minlength="6">
          <button type="button" class="password-toggle" data-toggle-for="qeydParol" aria-label="Parolu göstər">&#128065;</button>
        </div>
        <div class="field-helper" id="qeydParolHelper"></div>
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
  </div>

</div>
</div>
<script src="<?= \App\Core\Asset::v('js/olke-kodlari.js') ?>"></script>
<script>
  Birlikde.initAuthWizard(typeof OLKE_KODLARI !== 'undefined' ? OLKE_KODLARI : null);
  Birlikde.initPasswordToggles();
</script>
<script>
var XETA_METNI = <?= json_encode($t('ortaq.xeta'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var TELEFON_YANLIS_METNI = <?= json_encode($t('qapi.telefon_yanlis'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var AD_MECBURI_METNI = <?= json_encode($t('qapi.sahe_mecburi'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var PAROL_QISA_METNI = <?= json_encode($t('qapi.parol_qisa'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

(function () {
  // ---- Giriş (Parol addımı) ----
  var girisForm = document.getElementById('girisForm');
  var errBox = document.getElementById('errBox');

  girisForm.addEventListener('submit', async function (event) {
    event.preventDefault();
    Birlikde.hideError(errBox);
    var submitBtn = girisForm.querySelector('button[type=submit]');
    submitBtn.disabled = true;

    var body = {
      telefon: window.BirlikdeAuthWizard.telefon(),
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

  // ---- Qeydiyyat (yeni istifadəçi addımı) ----
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

  var qeydiyyatForm = document.getElementById('qeydiyyatForm');
  var qeydErrBox = document.getElementById('qeydErrBox');
  var adInput = document.getElementById('ad');
  var soyadInput = document.getElementById('soyad');
  var whatsappInput = document.getElementById('whatsapp');
  var qeydParolInput = document.getElementById('qeydParol');
  var adHelper = document.getElementById('adHelper');
  var soyadHelper = document.getElementById('soyadHelper');
  var whatsappHelper = document.getElementById('whatsappHelper');
  var qeydParolHelper = document.getElementById('qeydParolHelper');

  function validateRequired(input, helper) {
    if (input.value.trim() === '') {
      Birlikde.showFieldError(input, helper, AD_MECBURI_METNI);
      return false;
    }
    Birlikde.clearFieldError(input, helper);
    return true;
  }

  // Bax Birlikde.initAuthWizard() phoneForm validasiyası + AuthService::AZ_TELEFON_REGEX
  // (server-tərəfdə eyni qayda) — 994 ilə başlayan nömrələr operator kodu + 7 rəqəm
  // formatına uyğun olmalıdır.
  function validateWhatsapp() {
    var digits = whatsappInput.value.replace(/\D/g, '');
    var validFormat = digits.indexOf('994') === 0
      ? /^994(10|50|51|55|70|77|99)\d{7}$/.test(digits)
      : digits.length >= 7;
    if (!validFormat) {
      Birlikde.showFieldError(whatsappInput, whatsappHelper, TELEFON_YANLIS_METNI);
      return false;
    }
    Birlikde.clearFieldError(whatsappInput, whatsappHelper);
    return true;
  }

  function validateParolUzunluq() {
    if (qeydParolInput.value.length > 0 && qeydParolInput.value.length < 6) {
      Birlikde.showFieldError(qeydParolInput, qeydParolHelper, PAROL_QISA_METNI);
      return false;
    }
    Birlikde.clearFieldError(qeydParolInput, qeydParolHelper);
    return qeydParolInput.value.length >= 6;
  }

  adInput.addEventListener('blur', function () { validateRequired(adInput, adHelper); });
  soyadInput.addEventListener('blur', function () { validateRequired(soyadInput, soyadHelper); });
  whatsappInput.addEventListener('blur', validateWhatsapp);
  qeydParolInput.addEventListener('blur', validateParolUzunluq);
  qeydParolInput.addEventListener('input', function () {
    if (qeydParolInput.classList.contains('invalid')) validateParolUzunluq();
  });

  qeydiyyatForm.addEventListener('submit', async function (event) {
    event.preventDefault();
    Birlikde.hideError(qeydErrBox);

    var adOk = validateRequired(adInput, adHelper);
    var soyadOk = validateRequired(soyadInput, soyadHelper);
    var whatsappOk = validateWhatsapp();
    var parolOk = validateParolUzunluq();
    if (!adOk || !soyadOk || !whatsappOk || !parolOk) {
      return;
    }

    document.getElementById('qeydTelefonHidden').value = window.BirlikdeAuthWizard.telefon();

    var submitBtn = qeydiyyatForm.querySelector('button[type=submit]');
    submitBtn.disabled = true;

    var formData = new FormData(qeydiyyatForm);
    var res = await Birlikde.api('POST', '/qeydiyyat', formData);

    if (!res.ok) {
      submitBtn.disabled = false;
      Birlikde.showError(qeydErrBox, (res.data && res.data.error) || XETA_METNI);
      return;
    }

    // Qeydiyyat sessiya açmır (yalnız hesab yaradır) — istifadəçi əl ilə
    // yenidən "Giriş" addımına qayıtmasın deyə, elə həmin telefon+parolla
    // avtomatik daxil edilir (bax AuthService::qeydiyyat()/giris()).
    var girisRes = await Birlikde.api('POST', '/giris', {
      telefon: window.BirlikdeAuthWizard.telefon(),
      parol: qeydParolInput.value,
      meni_xatirla: '1'
    });
    submitBtn.disabled = false;

    if (girisRes.ok) {
      window.location.href = girisRes.data.rol === 'musteri' ? '/panel' : '/lovhe';
    } else {
      window.location.href = '/giris';
    }
  });
})();
</script>
<?php require __DIR__ . '/../partials/foot.php'; ?>
