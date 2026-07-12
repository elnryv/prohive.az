<?php
/**
 * @var callable $t
 * @var array $olculer
 * @var string $whatsappSupport
 */
require __DIR__ . '/../partials/head.php';
?>
<div class="tabs" id="panelTabs">
  <div class="tab active" data-panel="yeni"><?= htmlspecialchars($t('musteri.yeni_sifaris')) ?></div>
  <div class="tab" data-panel="tarixce"><?= htmlspecialchars($t('musteri.tarixce')) ?></div>
</div>

<div id="panelYeni">
  <div class="card glass" id="aktivSifarisCard" style="display:none;"></div>

  <div class="card glass">
    <h1><?= htmlspecialchars($t('musteri.yeni_sifaris')) ?></h1>
    <div class="error-box" id="errBox"></div>

    <div class="tabs" id="tipTabs">
      <div class="tab active" data-tip="kurye"><?= htmlspecialchars($t('qeydiyyat.rol_kurye')) ?></div>
      <div class="tab" data-tip="yukdasima"><?= htmlspecialchars($t('qeydiyyat.rol_yukdasima')) ?></div>
    </div>

    <form id="sifarisForm">
      <input type="hidden" name="tip" id="tipInput" value="kurye">

      <h3 style="font-size:14px; color:var(--text-dim);"><?= htmlspecialchars($t('musteri.goturulme')) ?></h3>
      <div class="field">
        <label><?= htmlspecialchars($t('musteri.sehir')) ?></label>
        <select id="gSehir" name="goturulme_sehir_id" required></select>
      </div>
      <div class="field">
        <label><?= htmlspecialchars($t('musteri.rayon')) ?></label>
        <select id="gRayon" name="goturulme_rayon_id" required></select>
      </div>
      <div class="field">
        <label><?= htmlspecialchars($t('musteri.unvan')) ?></label>
        <input type="text" name="goturulme_unvan" required>
      </div>

      <h3 style="font-size:14px; color:var(--text-dim);"><?= htmlspecialchars($t('musteri.catdirilma')) ?></h3>
      <div class="field">
        <label><?= htmlspecialchars($t('musteri.sehir')) ?></label>
        <select id="cSehir" name="catdirilma_sehir_id" required></select>
      </div>
      <div class="field">
        <label><?= htmlspecialchars($t('musteri.rayon')) ?></label>
        <select id="cRayon" name="catdirilma_rayon_id" required></select>
      </div>
      <div class="field">
        <label><?= htmlspecialchars($t('musteri.unvan')) ?></label>
        <input type="text" name="catdirilma_unvan" required>
      </div>

      <div class="field" id="olcuField" style="display:none;">
        <label><?= htmlspecialchars($t('musteri.yukdasima_olcusu')) ?></label>
        <select id="olcuSelect" name="yukdasima_olcu_id">
          <?php foreach ($olculer as $olcu): ?>
            <option value="<?= (int) $olcu['id'] ?>">
              <?= htmlspecialchars($olcu['kod']) ?> · <?= htmlspecialchars((string) $olcu['uzunluq']) ?> · <?= htmlspecialchars((string) $olcu['tutum']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label><?= htmlspecialchars($t('musteri.yuk_tesviri')) ?></label>
        <textarea name="yuk_tesviri"></textarea>
      </div>
      <div class="field">
        <label><?= htmlspecialchars($t('musteri.teklif_qiymet')) ?></label>
        <input type="number" step="0.01" min="0" name="teklif_qiymet">
      </div>
      <div class="field field-check">
        <input type="checkbox" id="tecili" name="tecili" value="1">
        <label for="tecili" style="margin:0;"><?= htmlspecialchars($t('musteri.tecili')) ?></label>
      </div>

      <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t('musteri.sifaris_yarat')) ?></button>
    </form>
  </div>
</div>

<div id="panelTarixce" style="display:none;">
  <div id="tarixceList"></div>
</div>

<a class="btn btn-support" style="margin-top:6px;" href="<?= htmlspecialchars('https://wa.me/' . $whatsappSupport . '?text=' . rawurlencode('Təklif/İrad — ')) ?>" target="_blank" rel="noopener">
  &#128172;&nbsp; <?= htmlspecialchars($t('musteri.sikayet')) ?>
</a>

<script>
var STATUS_ETIKETLERI = <?= json_encode([
    'axtarisda' => $t('status.axtarisda'),
    'goturulub' => $t('status.goturulub'),
    'tamamlandi' => $t('status.tamamlandi'),
    'legv' => $t('status.legv'),
    'passiv' => $t('status.passiv'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var XETA_METNI = <?= json_encode($t('ortaq.xeta'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var LEGV_METNI = <?= json_encode($t('ortaq.legv'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var BOS_TARIXCE = <?= json_encode($t('musteri.aktiv_sifaris_yoxdur'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var DASIYICI_ETIKETI = <?= json_encode($t('musteri.dasiyici'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var WHATSAPP_METNI = <?= json_encode($t('kurye.whatsapp_elaqe'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var GOTURULME_ETIKETI = <?= json_encode($t('musteri.goturulme'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var CATDIRILMA_ETIKETI = <?= json_encode($t('musteri.catdirilma'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var TECILI_METNI = <?= json_encode($t('musteri.tecili'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

(function () {
  var errBox = document.getElementById('errBox');

  async function loadSehirler(selectEl) {
    var res = await Birlikde.api('GET', '/sehirler');
    if (Birlikde.redirectIfUnauthorized(res.status)) return;
    selectEl.innerHTML = '';
    (res.data.data || []).forEach(function (sehir) {
      var opt = document.createElement('option');
      opt.value = sehir.id;
      opt.textContent = sehir.ad_az;
      selectEl.appendChild(opt);
    });
  }

  async function loadRayonlar(sehirId, selectEl) {
    var res = await Birlikde.api('GET', '/sehir/' + sehirId + '/rayonlar');
    selectEl.innerHTML = '';
    (res.data.data || []).forEach(function (rayon) {
      var opt = document.createElement('option');
      opt.value = rayon.id;
      opt.textContent = rayon.ad_az;
      selectEl.appendChild(opt);
    });
  }

  var gSehir = document.getElementById('gSehir');
  var gRayon = document.getElementById('gRayon');
  var cSehir = document.getElementById('cSehir');
  var cRayon = document.getElementById('cRayon');

  gSehir.addEventListener('change', function () { loadRayonlar(gSehir.value, gRayon); });
  cSehir.addEventListener('change', function () { loadRayonlar(cSehir.value, cRayon); });

  (async function initSehirler() {
    await loadSehirler(gSehir);
    await loadSehirler(cSehir);
    if (gSehir.value) loadRayonlar(gSehir.value, gRayon);
    if (cSehir.value) loadRayonlar(cSehir.value, cRayon);
  })();

  // Tip tabs
  var tipTabs = document.querySelectorAll('#tipTabs .tab');
  var tipInput = document.getElementById('tipInput');
  var olcuField = document.getElementById('olcuField');
  tipTabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tipInput.value = tab.dataset.tip;
      tipTabs.forEach(function (t) { t.classList.toggle('active', t === tab); });
      olcuField.style.display = tab.dataset.tip === 'yukdasima' ? 'block' : 'none';
    });
  });

  // Panel tabs (Yeni sifariş / Tarixçə)
  var panelTabs = document.querySelectorAll('#panelTabs .tab');
  var panelYeni = document.getElementById('panelYeni');
  var panelTarixce = document.getElementById('panelTarixce');
  panelTabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      panelTabs.forEach(function (t) { t.classList.toggle('active', t === tab); });
      if (tab.dataset.panel === 'tarixce') {
        panelYeni.style.display = 'none';
        panelTarixce.style.display = 'block';
        loadTarixce();
      } else {
        panelYeni.style.display = 'block';
        panelTarixce.style.display = 'none';
      }
    });
  });

  function statusBadge(status) {
    var label = STATUS_ETIKETLERI[status] || status;
    return '<span class="badge badge-' + Birlikde.escapeHtml(status) + '">' + Birlikde.escapeHtml(label) + '</span>';
  }

  function renderAktivSifaris(sifarisler) {
    var aktivCard = document.getElementById('aktivSifarisCard');
    var aktiv = sifarisler.find(function (s) { return s.status === 'axtarisda'; });

    if (!aktiv) {
      aktivCard.style.display = 'none';
      return;
    }

    var html = '';
    if (aktiv.tecili) html += '<div class="card-ribbon">' + Birlikde.escapeHtml(TECILI_METNI) + '</div>';
    html += '<div style="display:flex; justify-content:space-between; align-items:center;">';
    html += '<strong>#' + aktiv.id + '</strong>' + statusBadge(aktiv.status);
    html += '</div>';
    html += Birlikde.routeStepperHtml(aktiv.goturulme_unvan, aktiv.catdirilma_unvan, GOTURULME_ETIKETI, CATDIRILMA_ETIKETI);
    html += '<button class="btn btn-danger btn-small" id="legvBtn" data-id="' + aktiv.id + '">' + Birlikde.escapeHtml(LEGV_METNI) + '</button>';
    aktivCard.innerHTML = html;
    aktivCard.style.display = 'block';

    var legvBtn = document.getElementById('legvBtn');
    if (legvBtn) {
      legvBtn.addEventListener('click', async function () {
        legvBtn.disabled = true;
        var res = await Birlikde.api('POST', '/sifaris/' + legvBtn.dataset.id + '/legv');
        if (res.ok) {
          aktivCard.style.display = 'none';
        } else {
          legvBtn.disabled = false;
        }
      });
    }
  }

  async function loadTarixce() {
    var res = await Birlikde.api('GET', '/sifaris/tarixce');
    if (Birlikde.redirectIfUnauthorized(res.status)) return;

    var sifarisler = (res.data && res.data.data) || [];
    renderAktivSifaris(sifarisler);

    var listEl = document.getElementById('tarixceList');
    if (sifarisler.length === 0) {
      listEl.innerHTML = '<div class="empty-state">' + Birlikde.escapeHtml(BOS_TARIXCE) + '</div>';
      return;
    }

    listEl.innerHTML = sifarisler.map(function (s) {
      var html = '<div class="card glass">';
      if (s.tecili) html += '<div class="card-ribbon">' + Birlikde.escapeHtml(TECILI_METNI) + '</div>';
      html += '<div style="display:flex; justify-content:space-between;"><strong>#' + s.id + '</strong>' + statusBadge(s.status) + '</div>' +
        Birlikde.routeStepperHtml(s.goturulme_unvan, s.catdirilma_unvan, GOTURULME_ETIKETI, CATDIRILMA_ETIKETI) +
        '<p style="color:var(--text-dim); font-size:12px;">' + Birlikde.escapeHtml(s.created_at) + '</p>';
      if (s.dasiyici_adi) {
        html += '<div class="dasiyici-info">' +
          '<span>' + Birlikde.escapeHtml(DASIYICI_ETIKETI) + ': ' + Birlikde.escapeHtml(s.dasiyici_adi) + '</span>';
        if (s.dasiyici_whatsapp_link) {
          html += '<a class="btn btn-ghost btn-small" target="_blank" rel="noopener" href="' +
            Birlikde.escapeHtml(s.dasiyici_whatsapp_link) + '">' + Birlikde.escapeHtml(WHATSAPP_METNI) + '</a>';
        }
        html += '</div>';
      }
      html += '</div>';
      return html;
    }).join('');
  }

  document.getElementById('sifarisForm').addEventListener('submit', async function (event) {
    event.preventDefault();
    Birlikde.hideError(errBox);
    var submitBtn = event.target.querySelector('button[type=submit]');
    submitBtn.disabled = true;

    var formData = new FormData(event.target);
    var res = await Birlikde.api('POST', '/sifaris/yarat', formData);
    submitBtn.disabled = false;

    if (Birlikde.redirectIfUnauthorized(res.status)) return;

    if (!res.ok) {
      Birlikde.showError(errBox, (res.data && res.data.error) || XETA_METNI);
      return;
    }

    event.target.reset();
    loadTarixce();
  });

  loadTarixce();
})();
</script>
<?php require __DIR__ . '/../partials/foot.php'; ?>
