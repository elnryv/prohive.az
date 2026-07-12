<?php
/**
 * @var callable $t
 */
$aktivSehife = 'sifarisler';
require __DIR__ . '/partials/shell_head.php';
?>
<div class="admin-topbar">
  <h1><?= htmlspecialchars($t('admin.sifarisler')) ?></h1>
</div>

<div class="toolbar">
  <select id="statusFiltri">
    <option value="">— <?= htmlspecialchars($t('admin.status_filtri')) ?> —</option>
    <option value="axtarisda"><?= htmlspecialchars($t('status.axtarisda')) ?></option>
    <option value="goturulub"><?= htmlspecialchars($t('status.goturulub')) ?></option>
    <option value="tamamlandi"><?= htmlspecialchars($t('status.tamamlandi')) ?></option>
    <option value="legv"><?= htmlspecialchars($t('status.legv')) ?></option>
    <option value="passiv"><?= htmlspecialchars($t('status.passiv')) ?></option>
  </select>
  <input type="date" id="basTarixFiltri">
  <input type="date" id="bitTarixFiltri">
</div>

<div class="glass" style="padding:8px 16px;">
  <div class="table-wrap-admin">
    <table>
      <thead><tr><th>#</th><th>Müştəri</th><th>Kuryer</th><th>Status</th><th>Tarix</th></tr></thead>
      <tbody id="siyahiTbody"></tbody>
    </table>
  </div>
</div>
<div class="pagination" id="pagination"></div>

<div class="modal-overlay" id="modalOverlay">
  <div class="modal glass" id="modalIcerik"></div>
</div>

<script>
(function () {
  var page = 1;

  function statusBadge(status) {
    return '<span class="badge badge-' + status + '">' + BirlikdeAdmin.escapeHtml(status) + '</span>';
  }

  async function siyahiYukle() {
    var qs = new URLSearchParams({ page: page, limit: 20 });
    var status = document.getElementById('statusFiltri').value;
    var basTarix = document.getElementById('basTarixFiltri').value;
    var bitTarix = document.getElementById('bitTarixFiltri').value;
    if (status) qs.set('status', status);
    if (basTarix) qs.set('bas_tarix', basTarix);
    if (bitTarix) qs.set('bit_tarix', bitTarix);

    var res = await BirlikdeAdmin.api('GET', '/sifarisler?' + qs.toString());
    if (BirlikdeAdmin.redirectIfUnauthorized(res.status)) return;

    var tbody = document.getElementById('siyahiTbody');
    tbody.innerHTML = res.data.data.map(function (s) {
      return '<tr data-id="' + s.id + '">' +
        '<td>#' + s.id + '</td>' +
        '<td>' + BirlikdeAdmin.escapeHtml(s.musteri_ad) + ' ' + BirlikdeAdmin.escapeHtml(s.musteri_soyad) + '</td>' +
        '<td>' + (s.kurye_ad ? BirlikdeAdmin.escapeHtml(s.kurye_ad) + ' ' + BirlikdeAdmin.escapeHtml(s.kurye_soyad) : '—') + '</td>' +
        '<td>' + statusBadge(s.status) + '</td>' +
        '<td>' + BirlikdeAdmin.escapeHtml(s.created_at) + '</td>' +
        '</tr>';
    }).join('');

    Array.from(tbody.querySelectorAll('tr')).forEach(function (tr) {
      tr.addEventListener('click', function () { detalGoster(tr.dataset.id); });
    });

    document.getElementById('pagination').textContent =
      'Səhifə ' + res.data.page + ' / ' + Math.max(1, res.data.total_pages) + ' (cəmi ' + res.data.total + ')';
  }

  async function detalGoster(id) {
    var res = await BirlikdeAdmin.api('GET', '/sifaris/' + id);
    if (!res.ok) return;
    var s = res.data.data;

    var html = '<h2 style="margin-top:0;">Sifariş #' + s.id + ' ' + statusBadge(s.status) + '</h2>';
    html += '<p>' + BirlikdeAdmin.escapeHtml(s.goturulme_unvan) + ' &rarr; ' + BirlikdeAdmin.escapeHtml(s.catdirilma_unvan) + '</p>';
    html += '<p style="color:var(--text-dim); font-size:13px;">Müştəri: ' + BirlikdeAdmin.escapeHtml(s.musteri_ad) + ' ' + BirlikdeAdmin.escapeHtml(s.musteri_soyad) + ' (' + BirlikdeAdmin.escapeHtml(s.musteri_telefon) + ')</p>';
    if (s.kurye_ad) {
      html += '<p style="color:var(--text-dim); font-size:13px;">Kuryer: ' + BirlikdeAdmin.escapeHtml(s.kurye_ad) + ' ' + BirlikdeAdmin.escapeHtml(s.kurye_soyad) + ' (' + BirlikdeAdmin.escapeHtml(s.kurye_telefon) + ')</p>';
    }
    if (s.yuk_tesviri) html += '<p style="color:var(--text-dim); font-size:13px;">Yük: ' + BirlikdeAdmin.escapeHtml(s.yuk_tesviri) + '</p>';

    html += '<h3>Tarixçə</h3>';
    if (s.tarixce.length === 0) {
      html += '<p style="color:var(--text-dim); font-size:13px;">—</p>';
    } else {
      html += '<div class="table-wrap-admin"><table><tbody>' + s.tarixce.map(function (h) {
        return '<tr><td>' + BirlikdeAdmin.escapeHtml(h.created_at) + '</td><td>' + BirlikdeAdmin.escapeHtml(h.hadise) + '</td></tr>';
      }).join('') + '</tbody></table></div>';
    }

    html += '<button class="btn btn-ghost" id="modalBaglaBtn" style="margin-top:12px;"><?= htmlspecialchars($t('ortaq.bagla')) ?></button>';

    document.getElementById('modalIcerik').innerHTML = html;
    document.getElementById('modalOverlay').classList.add('visible');
    document.getElementById('modalBaglaBtn').addEventListener('click', function () {
      document.getElementById('modalOverlay').classList.remove('visible');
    });
  }

  document.getElementById('modalOverlay').addEventListener('click', function (event) {
    if (event.target.id === 'modalOverlay') event.target.classList.remove('visible');
  });

  ['statusFiltri', 'basTarixFiltri', 'bitTarixFiltri'].forEach(function (id) {
    document.getElementById(id).addEventListener('change', function () { page = 1; siyahiYukle(); });
  });

  siyahiYukle();
})();
</script>
<?php require __DIR__ . '/partials/shell_foot.php'; ?>
