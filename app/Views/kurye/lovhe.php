<?php
/**
 * @var callable $t
 */
require __DIR__ . '/../partials/head.php';
?>
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

  // SPA naviqasiyası ilə bu səhifədən ayrılanda (tam səhifə yenilənməsi
  // olmadan) SSE bağlantısı DOM-dan asılı olmayaraq arxa fonda açıq qalardı —
  // bax app.js Birlikde.onPageLeave().
  Birlikde.onPageLeave(function () {
    if (es) {
      es.close();
      es = null;
    }
  });

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
})();
</script>
<?php require __DIR__ . '/../partials/foot.php'; ?>
