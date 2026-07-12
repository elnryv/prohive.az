<?php
/**
 * @var callable $t
 */
$aktivSehife = 'dashboard';
require __DIR__ . '/partials/shell_head.php';
?>
<div class="admin-topbar">
  <h1><?= htmlspecialchars($t('admin.dashboard')) ?></h1>
</div>

<div class="stat-grid" id="statGrid"></div>

<div class="glass" style="padding:16px;">
  <h3 style="margin-top:0;">Son hadisələr</h3>
  <div class="table-wrap-admin">
    <table>
      <thead><tr><th>Vaxt</th><th>Hadisə</th><th>Detal</th></tr></thead>
      <tbody id="hadiseTbody"></tbody>
    </table>
  </div>
</div>

<script>
(function () {
  var ETIKETLER = {
    musteri_sayi: 'Müştəri',
    kurye_sayi: 'Kuryer',
    onlayn_kurye_sayi: 'Onlayn Kuryer',
    bloklu_istifadeci_sayi: 'Bloklu',
    axtarisda_sifaris_sayi: 'Axtarışda',
    goturulmus_sifaris_sayi: 'Götürülüb',
    tamamlanan_sifaris_sayi: 'Tamamlanıb',
    legv_sifaris_sayi: 'Ləğv',
    passiv_sifaris_sayi: 'Passiv',
    aktiv_banner_sayi: 'Aktiv Banner'
  };

  async function yukle() {
    var res = await BirlikdeAdmin.api('GET', '/dashboard');
    if (BirlikdeAdmin.redirectIfUnauthorized(res.status)) return;

    var grid = document.getElementById('statGrid');
    var sayğaclar = res.data['sayğaclar'];
    grid.innerHTML = Object.keys(sayğaclar).map(function (key) {
      return '<div class="stat-tile glass"><div class="value">' + sayğaclar[key] + '</div>' +
        '<div class="label">' + BirlikdeAdmin.escapeHtml(ETIKETLER[key] || key) + '</div></div>';
    }).join('');

    var tbody = document.getElementById('hadiseTbody');
    tbody.innerHTML = res.data.son_hadiseler.map(function (h) {
      return '<tr><td>' + BirlikdeAdmin.escapeHtml(h.created_at) + '</td>' +
        '<td>' + BirlikdeAdmin.escapeHtml(h.hadise) + '</td>' +
        '<td style="font-size:12px; color:var(--text-dim);">' + BirlikdeAdmin.escapeHtml(h.detal_json) + '</td></tr>';
    }).join('');
  }

  yukle();
})();
</script>
<?php require __DIR__ . '/partials/shell_foot.php'; ?>
