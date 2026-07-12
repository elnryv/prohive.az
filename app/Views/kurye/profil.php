<?php
/**
 * @var callable $t
 */
require __DIR__ . '/../partials/head.php';
?>
<div class="card glass profil-hero">
  <div class="profil-avatar-wrap">
    <img class="profil-avatar" id="avatarImg" style="display:none;" alt="">
    <div class="profil-avatar-placeholder" id="avatarPlaceholder">?</div>
    <div class="profil-avatar-edit" id="avatarEditBtn">&#9998;</div>
    <input type="file" id="avatarInput" accept="image/*" style="display:none;">
  </div>
  <div class="profil-name" id="profilAd"><?= htmlspecialchars($t('profil.basliq')) ?></div>
  <div class="profil-rol-badge"><span class="badge badge-tamamlandi" id="neqliyyatBadge">—</span></div>
  <div class="error-box" id="avatarXeta" style="margin-top:10px;"></div>

  <div class="profil-stats">
    <a class="profil-stat-pill" href="/sifarislerim">
      <strong id="tamamlananVal">—</strong>
      <span><?= htmlspecialchars($t('profil.tamamlanan')) ?></span>
    </a>
  </div>
</div>

<div class="card glass" id="abuneCard">
  <h3><span class="section-icon">&#128179;</span><?= htmlspecialchars($t('profil.abune')) ?></h3>
  <p><span class="badge" id="abuneBadge">—</span></p>
  <p id="qalanGunSetiri" style="display:none; color:var(--text-dim); font-size:14px;"></p>
  <button class="btn btn-primary" id="odeBtn" style="display:none;"><?= htmlspecialchars($t('profil.ode')) ?></button>
</div>

<div class="card glass">
  <h3><span class="section-icon">&#128205;</span><?= htmlspecialchars($t('profil.erazilerim')) ?></h3>
  <p id="eraziXulase" style="color:var(--text-dim); font-size:14px;">—</p>

  <div class="field">
    <label><?= htmlspecialchars($t('musteri.sehir')) ?></label>
    <select id="eraziSehir"></select>
  </div>
  <div id="eraziCheckboxlar"></div>
  <button class="btn btn-primary" id="eraziSaxlaBtn" style="margin-top:10px;"><?= htmlspecialchars($t('ortaq.gonder')) ?></button>
</div>

<div class="card glass">
  <button class="btn btn-ghost" id="pushBtn">&#128276;&nbsp; <?= htmlspecialchars($t('profil.bildiris_icaze')) ?></button>
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
var XETA_METNI = <?= json_encode($t('ortaq.xeta'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

(function () {
  var secilmisRayonlar = new Set();

  function avatarGoster(sekil) {
    var img = document.getElementById('avatarImg');
    var placeholder = document.getElementById('avatarPlaceholder');
    if (sekil) {
      img.src = '/kurye-sekil/' + sekil;
      img.style.display = 'block';
      placeholder.style.display = 'none';
    } else {
      img.style.display = 'none';
      placeholder.style.display = 'flex';
    }
  }

  async function profilYukle() {
    var res = await Birlikde.api('GET', '/kurye/profilim');
    if (Birlikde.redirectIfUnauthorized(res.status)) return;
    var d = res.data.data;
    var ad = (d.ad || '') + ' ' + (d.soyad || '');
    ad = ad.trim();
    document.getElementById('profilAd').textContent = ad || '—';
    document.getElementById('avatarPlaceholder').textContent = ad ? ad.charAt(0).toUpperCase() : '?';
    document.getElementById('neqliyyatBadge').textContent = d.neqliyyat || '—';
    document.getElementById('tamamlananVal').textContent = d.tamamlanan;
    avatarGoster(d.sekil);
  }

  document.getElementById('avatarEditBtn').addEventListener('click', function () {
    document.getElementById('avatarInput').click();
  });

  var avatarEditBtn = document.getElementById('avatarEditBtn');
  var avatarXeta = document.getElementById('avatarXeta');

  document.getElementById('avatarInput').addEventListener('change', async function (event) {
    var file = event.target.files[0];
    if (!file) return;
    Birlikde.hideError(avatarXeta);
    avatarEditBtn.classList.add('loading');

    try {
      // HEIC (iPhone-un default foto formatı) daxil olmaqla hər növ şəkil
      // brauzerdə kiçik JPEG-ə çevrilir — server-tərəfli format rədd
      // olunmasının (səssizcə "heç nə baş vermir" görünən) qarşısı alınır,
      // həm də yükləmə kiçik ölçü sayəsində daha sürətli olur.
      var blob = await Birlikde.imageToJpegBlob(file, 640, 0.85);
      var formData = new FormData();
      formData.append('sekil', blob, 'avatar.jpg');
      var res = await Birlikde.api('POST', '/kurye/sekil', formData);
      if (res.ok) {
        avatarGoster(res.data.data.sekil);
      } else {
        Birlikde.showError(avatarXeta, (res.data && res.data.error) || XETA_METNI);
      }
    } catch (e) {
      Birlikde.showError(avatarXeta, XETA_METNI);
    } finally {
      avatarEditBtn.classList.remove('loading');
      event.target.value = '';
    }
  });

  async function abuneYukle() {
    var res = await Birlikde.api('GET', '/kurye/abunelik');
    if (!res.ok) return;
    var d = res.data.data;
    var badge = document.getElementById('abuneBadge');
    var etiket = ABUNE_ETIKETLERI[d.label] || d.label;
    badge.textContent = etiket;
    var aktivmi = d.label === 'aktiv' || d.label === 'pulsuz' || d.label === 'pulsuz_qlobal';
    badge.className = 'badge badge-' + (aktivmi ? 'tamamlandi' : 'legv');

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
