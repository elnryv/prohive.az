<?php
/**
 * @var callable $t
 */
$aktivSehife = 'erazi';
require __DIR__ . '/partials/shell_head.php';
?>
<div class="admin-topbar">
  <h1><?= htmlspecialchars($t('admin.erazi')) ?></h1>
</div>

<div class="glass" style="padding:20px; margin-bottom:20px;">
  <h3 style="margin-top:0;">Yeni şəhər</h3>
  <div class="error-box" id="sehirErrBox"></div>
  <form id="sehirForm" style="display:flex; gap:10px; align-items:flex-end;">
    <div class="field" style="flex:1; margin-bottom:0;">
      <label>Ad (AZ)</label>
      <input type="text" name="ad_az" required>
    </div>
    <button type="submit" class="btn btn-primary">Əlavə et</button>
  </form>
</div>

<div class="toolbar">
  <select id="sehirSelect"></select>
</div>

<div class="glass" style="padding:20px; margin-bottom:20px;">
  <h3 style="margin-top:0;">Yeni ərazi</h3>
  <div class="error-box" id="rayonErrBox"></div>
  <form id="rayonForm" style="display:flex; gap:10px; align-items:flex-end;">
    <div class="field" style="flex:1; margin-bottom:0;">
      <label>Ad (AZ)</label>
      <input type="text" name="ad_az" required>
    </div>
    <button type="submit" class="btn btn-primary">Əlavə et</button>
  </form>
</div>

<div class="glass" style="padding:8px 16px;">
  <table>
    <thead><tr><th>Ad</th><th>Status</th><th></th></tr></thead>
    <tbody id="rayonTbody"></tbody>
  </table>
</div>

<script>
(function () {
  var sehirSelect = document.getElementById('sehirSelect');

  async function sehirleriYukle() {
    var res = await BirlikdeAdmin.api('GET', '/sehirler');
    if (BirlikdeAdmin.redirectIfUnauthorized(res.status)) return;

    var mevcud = sehirSelect.value;
    sehirSelect.innerHTML = '';
    res.data.data.forEach(function (sehir) {
      var opt = document.createElement('option');
      opt.value = sehir.id;
      opt.textContent = sehir.ad_az;
      sehirSelect.appendChild(opt);
    });
    if (mevcud) sehirSelect.value = mevcud;
    rayonlariYukle();
  }

  async function rayonlariYukle() {
    if (!sehirSelect.value) return;
    var res = await BirlikdeAdmin.api('GET', '/sehir/' + sehirSelect.value + '/rayonlar');
    var tbody = document.getElementById('rayonTbody');
    tbody.innerHTML = res.data.data.map(function (r) {
      return '<tr>' +
        '<td>' + BirlikdeAdmin.escapeHtml(r.ad_az) + '</td>' +
        '<td><span class="badge badge-' + (r.aktiv ? 'aktiv' : 'bloklu') + '">' + (r.aktiv ? 'Aktiv' : 'Deaktiv') + '</span></td>' +
        '<td>' +
        '<button class="btn btn-small btn-ghost" data-toggle="' + r.id + '" data-aktiv="' + (r.aktiv ? '0' : '1') + '">' + (r.aktiv ? 'Deaktiv et' : 'Aktivləşdir') + '</button> ' +
        '<button class="btn btn-small btn-danger" data-sil="' + r.id + '">Sil</button>' +
        '</td></tr>';
    }).join('');

    Array.from(tbody.querySelectorAll('[data-toggle]')).forEach(function (btn) {
      btn.addEventListener('click', async function () {
        await BirlikdeAdmin.api('POST', '/rayon/' + btn.dataset.toggle + '/aktivlik', { aktiv: btn.dataset.aktiv });
        rayonlariYukle();
      });
    });
    Array.from(tbody.querySelectorAll('[data-sil]')).forEach(function (btn) {
      btn.addEventListener('click', async function () {
        var res2 = await BirlikdeAdmin.api('POST', '/rayon/' + btn.dataset.sil + '/sil');
        if (!res2.ok) {
          alert((res2.data && res2.data.error) || 'Xəta baş verdi');
          return;
        }
        rayonlariYukle();
      });
    });
  }

  sehirSelect.addEventListener('change', rayonlariYukle);

  document.getElementById('sehirForm').addEventListener('submit', async function (event) {
    event.preventDefault();
    var errBox = document.getElementById('sehirErrBox');
    BirlikdeAdmin.hideError(errBox);
    var formData = new FormData(event.target);
    var res = await BirlikdeAdmin.api('POST', '/sehir', formData);
    if (!res.ok) {
      BirlikdeAdmin.showError(errBox, (res.data && res.data.error) || 'Xəta baş verdi');
      return;
    }
    event.target.reset();
    sehirleriYukle();
  });

  document.getElementById('rayonForm').addEventListener('submit', async function (event) {
    event.preventDefault();
    var errBox = document.getElementById('rayonErrBox');
    BirlikdeAdmin.hideError(errBox);
    if (!sehirSelect.value) return;
    var formData = new FormData(event.target);
    var res = await BirlikdeAdmin.api('POST', '/sehir/' + sehirSelect.value + '/rayon', formData);
    if (!res.ok) {
      BirlikdeAdmin.showError(errBox, (res.data && res.data.error) || 'Xəta baş verdi');
      return;
    }
    event.target.reset();
    rayonlariYukle();
  });

  sehirleriYukle();
})();
</script>
<?php require __DIR__ . '/partials/shell_foot.php'; ?>
