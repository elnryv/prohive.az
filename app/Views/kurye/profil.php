<?php
/**
 * @var callable $t
 */
require __DIR__ . '/../partials/head.php';
?>
<div class="card glass">
  <h1><?= htmlspecialchars($t('profil.basliq')) ?></h1>
  <p><strong><?= htmlspecialchars($t('profil.neqliyyat')) ?>:</strong> <span id="neqliyyatVal">—</span></p>
  <p><strong><?= htmlspecialchars($t('profil.tamamlanan')) ?>:</strong> <span id="tamamlananVal">—</span></p>
</div>

<div class="card glass" id="abuneCard">
  <h3 style="margin-top:0;"><?= htmlspecialchars($t('profil.abune')) ?></h3>
  <p><span class="badge" id="abuneBadge">—</span></p>
  <p id="qalanGunSetiri" style="display:none; color:var(--text-dim); font-size:14px;"></p>
  <button class="btn btn-primary" id="odeBtn" style="display:none;"><?= htmlspecialchars($t('profil.ode')) ?></button>
</div>

<div class="card glass">
  <h3 style="margin-top:0;"><?= htmlspecialchars($t('profil.erazilerim')) ?></h3>
  <p id="eraziXulase" style="color:var(--text-dim); font-size:14px;">—</p>

  <div class="field">
    <label><?= htmlspecialchars($t('musteri.sehir')) ?></label>
    <select id="eraziSehir"></select>
  </div>
  <div id="eraziCheckboxlar"></div>
  <button class="btn btn-primary" id="eraziSaxlaBtn" style="margin-top:10px;"><?= htmlspecialchars($t('ortaq.gonder')) ?></button>
</div>

<div class="card glass">
  <button class="btn btn-ghost" id="pushBtn"><?= htmlspecialchars($t('profil.bildiris_icaze')) ?></button>
</div>

<script>
var VAPID_PUBLIC_KEY = <?= json_encode(\App\Core\Env::get('VAPID_PUBLIC_KEY', ''), JSON_UNESCAPED_UNICODE) ?>;
var ABUNE_ETIKETLERI = <?= json_encode([
    'aktiv' => $t('profil.abune.aktiv'),
    'pulsuz' => $t('profil.abune.pulsuz'),
    'bitib' => $t('profil.abune.bitib'),
    'bloklu' => $t('profil.abune.bloklu'),
    'pulsuz_qlobal' => $t('profil.abune.pulsuz_qlobal'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var QALAN_GUN_METNI = <?= json_encode($t('profil.qalan_gun'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

(function () {
  var secilmisRayonlar = new Set();

  async function profilYukle() {
    var res = await Birlikde.api('GET', '/kurye/profilim');
    if (Birlikde.redirectIfUnauthorized(res.status)) return;
    document.getElementById('neqliyyatVal').textContent = res.data.data.neqliyyat || '—';
    document.getElementById('tamamlananVal').textContent = res.data.data.tamamlanan;
  }

  async function abuneYukle() {
    var res = await Birlikde.api('GET', '/kurye/abunelik');
    if (!res.ok) return;
    var d = res.data.data;
    var badge = document.getElementById('abuneBadge');
    badge.textContent = ABUNE_ETIKETLERI[d.label] || d.label;
    badge.className = 'badge badge-' + (d.label === 'aktiv' || d.label === 'pulsuz' || d.label === 'pulsuz_qlobal' ? 'tamamlandi' : 'legv');

    var qalanEl = document.getElementById('qalanGunSetiri');
    if (d.qalan_gun !== null) {
      qalanEl.textContent = QALAN_GUN_METNI + ': ' + d.qalan_gun;
      qalanEl.style.display = 'block';
    }

    var odeBtn = document.getElementById('odeBtn');
    if (d.label === 'bitib') {
      odeBtn.style.display = 'block';
    }
  }

  document.getElementById('odeBtn').addEventListener('click', async function () {
    var res = await Birlikde.api('POST', '/odenis/basla');
    if (res.ok && res.data.data.redirect_url) {
      window.location.href = res.data.data.redirect_url;
    }
  });

  async function eraziXulaseYenile() {
    var res = await Birlikde.api('GET', '/kurye/bolgeler');
    if (Birlikde.redirectIfUnauthorized(res.status)) return;
    var rayonlar = res.data.data || [];
    secilmisRayonlar = new Set(rayonlar.map(function (r) { return String(r.id); }));
    var xulaseEl = document.getElementById('eraziXulase');
    xulaseEl.textContent = rayonlar.length === 0
      ? '—'
      : rayonlar.map(function (r) { return r.ad_az; }).join(', ');
  }

  async function sehirleriYukle() {
    var res = await Birlikde.api('GET', '/sehirler');
    var select = document.getElementById('eraziSehir');
    (res.data.data || []).forEach(function (sehir) {
      var opt = document.createElement('option');
      opt.value = sehir.id;
      opt.textContent = sehir.ad_az;
      select.appendChild(opt);
    });
    if (select.value) rayonCheckboxlariYukle(select.value);
  }

  async function rayonCheckboxlariYukle(sehirId) {
    var res = await Birlikde.api('GET', '/sehir/' + sehirId + '/rayonlar');
    var container = document.getElementById('eraziCheckboxlar');
    container.innerHTML = '';
    (res.data.data || []).forEach(function (rayon) {
      var wrap = document.createElement('div');
      wrap.className = 'field-check';
      wrap.style.marginBottom = '6px';
      var checked = secilmisRayonlar.has(String(rayon.id)) ? 'checked' : '';
      wrap.innerHTML = '<input type="checkbox" id="r' + rayon.id + '" value="' + rayon.id + '" ' + checked + '>' +
        '<label for="r' + rayon.id + '" style="margin:0;">' + Birlikde.escapeHtml(rayon.ad_az) + '</label>';
      container.appendChild(wrap);

      wrap.querySelector('input').addEventListener('change', function (event) {
        if (event.target.checked) {
          secilmisRayonlar.add(String(rayon.id));
        } else {
          secilmisRayonlar.delete(String(rayon.id));
        }
      });
    });
  }

  document.getElementById('eraziSehir').addEventListener('change', function (event) {
    rayonCheckboxlariYukle(event.target.value);
  });

  document.getElementById('eraziSaxlaBtn').addEventListener('click', async function () {
    var formData = new FormData();
    secilmisRayonlar.forEach(function (id) { formData.append('rayonlar[]', id); });
    var res = await Birlikde.api('POST', '/kurye/bolgeler', formData);
    if (res.ok) {
      eraziXulaseYenile();
    }
  });

  document.getElementById('pushBtn').addEventListener('click', async function () {
    if (!VAPID_PUBLIC_KEY) return;
    await Birlikde.subscribeToPush(VAPID_PUBLIC_KEY);
  });

  profilYukle();
  abuneYukle();
  sehirleriYukle();
  eraziXulaseYenile();
})();
</script>
<?php require __DIR__ . '/../partials/foot.php'; ?>
