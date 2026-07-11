<?php
/**
 * @var callable $t
 */
require __DIR__ . '/../partials/head.php';
?>
<div class="card glass" style="display:flex; justify-content:space-between; align-items:center;">
  <strong><?= htmlspecialchars($t('kurye.lovhe')) ?></strong>
  <label style="display:flex; align-items:center; gap:8px; font-size:14px;">
    <span id="onlaynLabel"><?= htmlspecialchars($t('kurye.offline')) ?></span>
    <input type="checkbox" id="onlaynToggle">
  </label>
</div>


<div class="push-warning" id="pushWarning"><?= htmlspecialchars($t('profil.bildiris_xeberdarliq')) ?></div>

<div id="menimIslerim"></div>

<h3 style="font-size:14px; color:var(--text-dim);"><?= htmlspecialchars($t('kurye.lovhe')) ?></h3>
<div id="lovheKartlar"></div>
<div class="empty-state" id="lovheBos"><?= htmlspecialchars($t('kurye.lovhe_bos')) ?></div>

<script>
var STATUS_ETIKETLERI = <?= json_encode([
    'axtarisda' => $t('status.axtarisda'),
    'goturulub' => $t('status.goturulub'),
    'tamamlandi' => $t('status.tamamlandi'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var GOT_METNI = <?= json_encode($t('kurye.got'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var TAMAMLA_METNI = <?= json_encode($t('kurye.tamamla'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var WHATSAPP_METNI = <?= json_encode($t('kurye.whatsapp_elaqe'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var ONLAYN_METNI = <?= json_encode($t('kurye.onlayn'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var OFFLINE_METNI = <?= json_encode($t('kurye.offline'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

(function () {
  var kartlarEl = document.getElementById('lovheKartlar');
  var bosEl = document.getElementById('lovheBos');
  var menimIslerimEl = document.getElementById('menimIslerim');

  function sifarisKarti(s, tamamlanmisBolme) {
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

    if (tamamlanmisBolme) {
      html += '<button class="btn btn-primary btn-small" data-tamamla="' + s.id + '">' + Birlikde.escapeHtml(TAMAMLA_METNI) + '</button>';
    } else {
      html += '<button class="btn btn-primary btn-small" data-got="' + s.id + '">' + Birlikde.escapeHtml(GOT_METNI) + '</button>';
    }
    html += '<div class="wa-link" style="margin-top:8px;"></div>';

    div.innerHTML = html;
    return div;
  }

  function baglaGotDuymesi(div, id) {
    var btn = div.querySelector('[data-got]');
    if (!btn) return;
    btn.addEventListener('click', async function () {
      btn.disabled = true;
      var res = await Birlikde.api('POST', '/sifaris/' + id + '/gotur');
      if (!res.ok) {
        div.remove();
        yenileBos();
        return;
      }
      var waDiv = div.querySelector('.wa-link');
      if (res.data.data.kurye_whatsapp_link) {
        waDiv.innerHTML = '<a class="btn btn-ghost btn-small" target="_blank" rel="noopener" href="' +
          Birlikde.escapeHtml(res.data.data.musteri_whatsapp_link) + '">' + Birlikde.escapeHtml(WHATSAPP_METNI) + '</a>';
      }
      div.remove();
      menimIslerimEl.appendChild(div);
      div.querySelector('button[data-got]')?.remove();
      var tamamlaBtn = document.createElement('button');
      tamamlaBtn.className = 'btn btn-primary btn-small';
      tamamlaBtn.textContent = TAMAMLA_METNI;
      tamamlaBtn.dataset.tamamla = id;
      div.insertBefore(tamamlaBtn, waDiv);
      baglaTamamlaDuymesi(div, id);
      yenileBos();
    });
  }

  function baglaTamamlaDuymesi(div, id) {
    var btn = div.querySelector('[data-tamamla]');
    if (!btn) return;
    btn.addEventListener('click', async function () {
      btn.disabled = true;
      var res = await Birlikde.api('POST', '/sifaris/' + id + '/tamamla');
      if (res.ok) {
        div.remove();
      } else {
        btn.disabled = false;
      }
    });
  }

  function yenileBos() {
    bosEl.style.display = kartlarEl.children.length === 0 ? 'block' : 'none';
  }

  async function menimIslerimYukle() {
    var res = await Birlikde.api('GET', '/kurye/aktiv-isler');
    if (Birlikde.redirectIfUnauthorized(res.status)) return;
    (res.data.data || []).forEach(function (s) {
      var div = sifarisKarti(s, true);
      menimIslerimEl.appendChild(div);
      baglaTamamlaDuymesi(div, s.id);
    });
  }

  // Onlayn/offline toggle
  var onlaynToggle = document.getElementById('onlaynToggle');
  var onlaynLabel = document.getElementById('onlaynLabel');
  onlaynToggle.addEventListener('change', async function () {
    var res = await Birlikde.api('POST', '/kurye/onlayn', { onlayn: onlaynToggle.checked ? '1' : '0' });
    if (Birlikde.redirectIfUnauthorized(res.status)) return;
    onlaynLabel.textContent = onlaynToggle.checked ? ONLAYN_METNI : OFFLINE_METNI;
    if (onlaynToggle.checked) {
      connectSse();
    }
  });

  var es = null;
  function connectSse() {
    if (es) return;
    es = new EventSource('/sse/lovhe');
    es.addEventListener('yeni_sifaris', function (event) {
      var s = JSON.parse(event.data);
      var div = sifarisKarti(s, false);
      kartlarEl.appendChild(div);
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

  menimIslerimYukle();
  yenileBos();
  onlaynToggle.checked = false;
  connectSse();
})();
</script>
<?php require __DIR__ . '/../partials/foot.php'; ?>
