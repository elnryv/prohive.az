<?php
/**
 * @var callable $t
 */
require __DIR__ . '/../partials/head.php';
?>
<div class="tabs" id="lovheTabs">
  <div class="tab active" data-panel="lovhe"><?= htmlspecialchars($t('kurye.lovhe')) ?></div>
  <div class="tab" data-panel="sifarislerim"><?= htmlspecialchars($t('kurye.sifarislerim')) ?></div>
</div>

<div id="panelLovhe">
  <div class="card glass lovhe-header">
    <div>
      <div class="lovhe-header-title"><?= htmlspecialchars($t('kurye.lovhe')) ?></div>
      <div class="lovhe-status-row" style="margin-top:6px;">
        <span class="lovhe-status-dot" id="statusDot"></span>
        <span class="lovhe-status-label" id="onlaynLabel"><?= htmlspecialchars($t('kurye.offline')) ?></span>
      </div>
    </div>
    <label class="toggle-switch">
      <input type="checkbox" id="onlaynToggle">
      <span class="toggle-slider"></span>
    </label>
  </div>

  <div class="push-warning" id="pushWarning"><?= htmlspecialchars($t('profil.bildiris_xeberdarliq')) ?></div>

  <div id="lovheKartlar"></div>
  <div class="empty-state" id="lovheBos"><?= htmlspecialchars($t('kurye.lovhe_bos')) ?></div>
</div>

<div id="panelSifarislerim" style="display:none;">
  <div id="sifarislerimList"></div>
</div>

<div class="modal-overlay" id="goturModal">
  <div class="modal-sheet glass">
    <div class="modal-icon">&#10003;</div>
    <h2><?= htmlspecialchars($t('kurye.goturuldu_basliq')) ?></h2>
    <p id="goturMusteri"></p>
    <p id="goturUnvanlar"></p>
    <div class="modal-actions">
      <a class="btn btn-primary" id="goturWaBtn" target="_blank" rel="noopener"><?= htmlspecialchars($t('kurye.whatsapp_elaqe')) ?></a>
      <button class="btn btn-ghost" id="goturBaglaBtn" type="button"><?= htmlspecialchars($t('ortaq.bagla')) ?></button>
    </div>
  </div>
</div>

<script>
var GOT_METNI = <?= json_encode($t('kurye.got'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var ONLAYN_METNI = <?= json_encode($t('kurye.onlayn'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var OFFLINE_METNI = <?= json_encode($t('kurye.offline'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var MUSTERI_ETIKETI = <?= json_encode($t('kurye.musteri_adi'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var WHATSAPP_METNI = <?= json_encode($t('kurye.whatsapp_elaqe'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var SIFARISLERIM_BOS = <?= json_encode($t('kurye.sifarislerim_bos'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

(function () {
  var kartlarEl = document.getElementById('lovheKartlar');
  var bosEl = document.getElementById('lovheBos');

  function sifarisKarti(s) {
    var div = document.createElement('div');
    div.className = 'card glass';
    div.id = 'sifaris-' + s.id;

    var html = '<div style="display:flex; justify-content:space-between;">';
    html += '<strong>#' + s.id + '</strong>';
    if (s.tecili) html += '<span class="badge badge-legv">!</span>';
    html += '</div>';
    html += '<p style="font-size:14px;">' + Birlikde.escapeHtml(s.goturulme_unvan) + ' &rarr; ' + Birlikde.escapeHtml(s.catdirilma_unvan) + '</p>';
    if (s.yuk_tesviri) html += '<p style="color:var(--text-dim); font-size:13px;">' + Birlikde.escapeHtml(s.yuk_tesviri) + '</p>';
    if (s.teklif_qiymet) html += '<p style="font-weight:600;">' + Birlikde.escapeHtml(s.teklif_qiymet) + ' AZN</p>';
    html += '<button class="btn btn-primary btn-small" data-got="' + s.id + '">' + Birlikde.escapeHtml(GOT_METNI) + '</button>';

    div.innerHTML = html;
    return div;
  }

  function goturModalGoster(data) {
    document.getElementById('goturMusteri').textContent = MUSTERI_ETIKETI + ': ' + (data.musteri_adi || '—');
    document.getElementById('goturUnvanlar').textContent = data.goturulme_unvan + ' → ' + data.catdirilma_unvan;
    var waBtn = document.getElementById('goturWaBtn');
    if (data.musteri_whatsapp_link) {
      waBtn.href = data.musteri_whatsapp_link;
      waBtn.style.display = 'flex';
    } else {
      waBtn.style.display = 'none';
    }
    document.getElementById('goturModal').classList.add('visible');
  }

  document.getElementById('goturBaglaBtn').addEventListener('click', function () {
    document.getElementById('goturModal').classList.remove('visible');
  });

  function baglaGotDuymesi(div, id) {
    var btn = div.querySelector('[data-got]');
    if (!btn) return;
    btn.addEventListener('click', async function () {
      btn.disabled = true;
      var res = await Birlikde.api('POST', '/sifaris/' + id + '/gotur');
      if (!res.ok) {
        btn.disabled = false;
        return;
      }
      div.remove();
      yenileBos();
      goturModalGoster(res.data.data);
    });
  }

  function yenileBos() {
    bosEl.style.display = kartlarEl.children.length === 0 ? 'block' : 'none';
  }

  // Onlayn/offline toggle
  var onlaynToggle = document.getElementById('onlaynToggle');
  var onlaynLabel = document.getElementById('onlaynLabel');
  var statusDot = document.getElementById('statusDot');

  function tetbiqOnlaynGorunus(onlayn) {
    onlaynLabel.textContent = onlayn ? ONLAYN_METNI : OFFLINE_METNI;
    statusDot.classList.toggle('online', onlayn);
  }

  onlaynToggle.addEventListener('change', async function () {
    var yeniVeziyyet = onlaynToggle.checked;
    var res = await Birlikde.api('POST', '/kurye/onlayn', { onlayn: yeniVeziyyet ? '1' : '0' });
    if (Birlikde.redirectIfUnauthorized(res.status)) return;
    if (!res.ok) {
      onlaynToggle.checked = !yeniVeziyyet;
      return;
    }
    tetbiqOnlaynGorunus(yeniVeziyyet);
    if (yeniVeziyyet) {
      connectSse();
    }
  });

  var es = null;
  function connectSse() {
    if (es) return;
    es = new EventSource('/sse/lovhe');
    es.addEventListener('yeni_sifaris', function (event) {
      var s = JSON.parse(event.data);
      var div = sifarisKarti(s);
      kartlarEl.insertBefore(div, kartlarEl.firstChild);
      baglaGotDuymesi(div, s.id);
      yenileBos();
    });
    es.onerror = function () {
      // Bağlantı kəsilərsə brauzer avtomatik yenidən qoşulmağa cəhd edir.
    };
  }

  // Bax bölmə 9.3.1: bildiriş icazəsi verilməyibsə davamlı xəbərdarlıq göstərilir.
  if (!('Notification' in window) || Notification.permission !== 'granted') {
    document.getElementById('pushWarning').classList.add('visible');
  }

  // Səhifə açılanda serverdəki HƏQİQİ onlayn vəziyyəti oxunur (əvvəllər hər dəfə
  // "offline" kimi sıfırlanırdı — bu, istifadəçiyə "özü-özünə offline olur" kimi
  // görünürdü, halbuki server tərəfdəki vəziyyət toxunulmamış qalırdı).
  (async function initVeziyyet() {
    var res = await Birlikde.api('GET', '/kurye/profilim');
    if (Birlikde.redirectIfUnauthorized(res.status)) return;
    var onlayn = !!(res.data && res.data.data && res.data.data.onlayn);
    onlaynToggle.checked = onlayn;
    tetbiqOnlaynGorunus(onlayn);
    if (onlayn) {
      connectSse();
    }
  })();

  yenileBos();

  // ---- "Sifarişlərim" bölməsi (götürülmüş sifarişlərin tarixçəsi) ----
  var sifarislerimList = document.getElementById('sifarislerimList');

  async function sifarislerimYukle() {
    var res = await Birlikde.api('GET', '/kurye/sifarislerim');
    if (Birlikde.redirectIfUnauthorized(res.status)) return;
    var sifarisler = (res.data && res.data.data) || [];

    if (sifarisler.length === 0) {
      sifarislerimList.innerHTML = '<div class="empty-state">' + Birlikde.escapeHtml(SIFARISLERIM_BOS) + '</div>';
      return;
    }

    sifarislerimList.innerHTML = sifarisler.map(function (s) {
      var html = '<div class="card glass">' +
        '<div style="display:flex; justify-content:space-between;"><strong>#' + s.id + '</strong>' +
        '<span class="badge badge-tamamlandi">' + Birlikde.escapeHtml(s.goturulme_vaxti || s.created_at) + '</span></div>' +
        '<p style="font-size:14px;">' + Birlikde.escapeHtml(s.goturulme_unvan) + ' &rarr; ' + Birlikde.escapeHtml(s.catdirilma_unvan) + '</p>';
      if (s.musteri_adi) {
        html += '<div class="dasiyici-info">' +
          '<span>' + Birlikde.escapeHtml(MUSTERI_ETIKETI) + ': ' + Birlikde.escapeHtml(s.musteri_adi) + '</span>';
        if (s.musteri_whatsapp_link) {
          html += '<a class="btn btn-ghost btn-small" target="_blank" rel="noopener" href="' +
            Birlikde.escapeHtml(s.musteri_whatsapp_link) + '">' + Birlikde.escapeHtml(WHATSAPP_METNI) + '</a>';
        }
        html += '</div>';
      }
      html += '</div>';
      return html;
    }).join('');
  }

  var lovheTabs = document.querySelectorAll('#lovheTabs .tab');
  var panelLovhe = document.getElementById('panelLovhe');
  var panelSifarislerim = document.getElementById('panelSifarislerim');
  lovheTabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      lovheTabs.forEach(function (t) { t.classList.toggle('active', t === tab); });
      if (tab.dataset.panel === 'sifarislerim') {
        panelLovhe.style.display = 'none';
        panelSifarislerim.style.display = 'block';
        sifarislerimYukle();
      } else {
        panelLovhe.style.display = 'block';
        panelSifarislerim.style.display = 'none';
      }
    });
  });
})();
</script>
<?php require __DIR__ . '/../partials/foot.php'; ?>
