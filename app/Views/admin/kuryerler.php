<?php
/**
 * @var callable $t
 */
$aktivSehife = 'kuryerler';
require __DIR__ . '/partials/shell_head.php';
?>
<div class="admin-topbar">
  <h1><?= htmlspecialchars($t('admin.kuryerler')) ?></h1>
</div>

<div class="toolbar">
  <input type="text" id="axtarInput" placeholder="<?= htmlspecialchars($t('admin.axtar')) ?>" style="flex:1; padding:10px 12px; border-radius:10px; border:1px solid var(--glass-border); background:rgba(255,255,255,0.04); color:var(--text);">
</div>

<div class="glass" style="padding:8px 16px;">
  <table>
    <thead><tr><th>Ad Soyad</th><th>Telefon</th><th>Rol</th><th>Status</th><th>Tarix</th></tr></thead>
    <tbody id="siyahiTbody"></tbody>
  </table>
</div>
<div class="pagination" id="pagination"></div>

<div class="modal-overlay" id="modalOverlay">
  <div class="modal glass" id="modalIcerik"></div>
</div>

<script>
var SEBEB_METNI = <?= json_encode($t('admin.sebeb'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var BLOKLA_METNI = <?= json_encode($t('admin.blokla'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var BLOKDAN_CIXAR_METNI = <?= json_encode($t('admin.blokdan_cixar'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var BAGLA_METNI = <?= json_encode($t('ortaq.bagla'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

(function () {
  var API = '/kuryerler';
  var page = 1;
  var axtarTimeout = null;

  function statusBadge(status) {
    return '<span class="badge badge-' + (status === 'bloklu' ? 'bloklu' : 'aktiv') + '">' + BirlikdeAdmin.escapeHtml(status) + '</span>';
  }

  async function siyahiYukle() {
    var axtar = document.getElementById('axtarInput').value;
    var qs = new URLSearchParams({ page: page, limit: 20 });
    if (axtar) qs.set('axtar', axtar);

    var res = await BirlikdeAdmin.api('GET', API + '?' + qs.toString());
    if (BirlikdeAdmin.redirectIfUnauthorized(res.status)) return;

    var tbody = document.getElementById('siyahiTbody');
    tbody.innerHTML = res.data.data.map(function (u) {
      return '<tr data-id="' + u.id + '">' +
        '<td>' + BirlikdeAdmin.escapeHtml(u.ad) + ' ' + BirlikdeAdmin.escapeHtml(u.soyad) + '</td>' +
        '<td>' + BirlikdeAdmin.escapeHtml(u.telefon) + '</td>' +
        '<td>' + BirlikdeAdmin.escapeHtml(u.rol) + '</td>' +
        '<td>' + statusBadge(u.status) + '</td>' +
        '<td>' + BirlikdeAdmin.escapeHtml(u.created_at) + '</td>' +
        '</tr>';
    }).join('');

    Array.from(tbody.querySelectorAll('tr')).forEach(function (tr) {
      tr.addEventListener('click', function () { detalGoster(tr.dataset.id); });
    });

    document.getElementById('pagination').textContent =
      'Səhifə ' + res.data.page + ' / ' + Math.max(1, res.data.total_pages) + ' (cəmi ' + res.data.total + ')';
  }

  async function abunelikBolmesiYukle(kuryeId) {
    var res = await BirlikdeAdmin.api('GET', '/kurye/' + kuryeId + '/abunelik');
    if (!res.ok) return;
    var d = res.data.data;

    var html = '<h3>Abunəlik</h3>';
    html += '<p>Vəziyyət: <span class="badge badge-' + (d.label === 'aktiv' || d.label === 'pulsuz' || d.label === 'pulsuz_qlobal' ? 'tamamlandi' : 'legv') + '">' + BirlikdeAdmin.escapeHtml(d.label) + '</span></p>';
    if (d.qalan_gun !== null) html += '<p>Qalan gün: ' + d.qalan_gun + '</p>';
    html += '<div class="field"><label>' + BirlikdeAdmin.escapeHtml(SEBEB_METNI) + '</label><input type="text" id="abuneSebebInput"></div>';
    html += '<div class="toolbar">';
    html += '<button class="btn btn-primary btn-small" id="uzatBtn">+30 gün</button>';
    html += '<button class="btn btn-ghost btn-small" id="tipDeyisBtn">Pulsuz/Pullu</button>';
    html += '<button class="btn btn-danger btn-small" id="dayandirBtn">Dayandır</button>';
    html += '<button class="btn btn-success btn-small" id="aktivEtBtn">Aktivləşdir</button>';
    html += '</div>';

    document.getElementById('abunelikBolmesi').innerHTML = html;

    function sebeb() { return document.getElementById('abuneSebebInput').value; }

    document.getElementById('uzatBtn').addEventListener('click', async function () {
      await BirlikdeAdmin.api('POST', '/kurye/' + kuryeId + '/abunelik/uzat', { gun: 30, sebeb: sebeb() });
      abunelikBolmesiYukle(kuryeId);
    });
    document.getElementById('tipDeyisBtn').addEventListener('click', async function () {
      var yeniTip = d.tip === 'pulsuz' ? 'pullu' : 'pulsuz';
      await BirlikdeAdmin.api('POST', '/kurye/' + kuryeId + '/abunelik/tip', { tip: yeniTip, sebeb: sebeb() });
      abunelikBolmesiYukle(kuryeId);
    });
    document.getElementById('dayandirBtn').addEventListener('click', async function () {
      await BirlikdeAdmin.api('POST', '/kurye/' + kuryeId + '/abunelik/aktivlik', { aktiv: '0', sebeb: sebeb() });
      abunelikBolmesiYukle(kuryeId);
    });
    document.getElementById('aktivEtBtn').addEventListener('click', async function () {
      await BirlikdeAdmin.api('POST', '/kurye/' + kuryeId + '/abunelik/aktivlik', { aktiv: '1', sebeb: sebeb() });
      abunelikBolmesiYukle(kuryeId);
    });
  }

  async function detalGoster(id) {
    var res = await BirlikdeAdmin.api('GET', '/istifadeci/' + id);
    if (!res.ok) return;
    var u = res.data.data;

    var html = '<h2 style="margin-top:0;">' + BirlikdeAdmin.escapeHtml(u.ad) + ' ' + BirlikdeAdmin.escapeHtml(u.soyad) + '</h2>';
    html += '<p>' + BirlikdeAdmin.escapeHtml(u.telefon) + ' — ' + statusBadge(u.status) + '</p>';
    html += '<p style="color:var(--text-dim); font-size:13px;">Rol: ' + BirlikdeAdmin.escapeHtml(u.rol) + '</p>';
    if (u.neqliyyat) html += '<p style="color:var(--text-dim); font-size:13px;">Nəqliyyat: ' + BirlikdeAdmin.escapeHtml(u.neqliyyat) + '</p>';
    if (u.olculer) html += '<p style="color:var(--text-dim); font-size:13px;">Ölçülər: ' + BirlikdeAdmin.escapeHtml(u.olculer.join(', ')) + '</p>';
    html += '<p style="color:var(--text-dim); font-size:13px;">Tamamlanan: ' + BirlikdeAdmin.escapeHtml(u.tamamlanan) + '</p>';
    html += '<p style="color:var(--text-dim); font-size:13px;">Qeydiyyat: ' + BirlikdeAdmin.escapeHtml(u.created_at) + '</p>';

    html += '<div class="field"><label>' + BirlikdeAdmin.escapeHtml(SEBEB_METNI) + '</label>';
    html += '<input type="text" id="sebebInput"></div>';

    if (u.status === 'bloklu') {
      html += '<button class="btn btn-success" id="aksiyaBtn" data-aksiya="aktiv">' + BirlikdeAdmin.escapeHtml(BLOKDAN_CIXAR_METNI) + '</button>';
    } else {
      html += '<button class="btn btn-danger" id="aksiyaBtn" data-aksiya="blokla">' + BirlikdeAdmin.escapeHtml(BLOKLA_METNI) + '</button>';
    }
    html += ' <button class="btn btn-ghost" id="modalBaglaBtn">' + BirlikdeAdmin.escapeHtml(BAGLA_METNI) + '</button>';
    html += '<hr style="border-color:var(--glass-border); margin:16px 0;">';
    html += '<div id="abunelikBolmesi"></div>';

    document.getElementById('modalIcerik').innerHTML = html;
    document.getElementById('modalOverlay').classList.add('visible');

    document.getElementById('modalBaglaBtn').addEventListener('click', modalBagla);
    document.getElementById('aksiyaBtn').addEventListener('click', async function (event) {
      var aksiya = event.target.dataset.aksiya;
      var sebeb = document.getElementById('sebebInput').value;
      var yol = aksiya === 'aktiv' ? '/istifadeci/' + id + '/blokdan-cixar' : '/istifadeci/' + id + '/blokla';
      var res2 = await BirlikdeAdmin.api('POST', yol, { sebeb: sebeb });
      if (res2.ok) {
        modalBagla();
        siyahiYukle();
      }
    });

    abunelikBolmesiYukle(u.kurye_id);
  }

  function modalBagla() {
    document.getElementById('modalOverlay').classList.remove('visible');
  }

  document.getElementById('modalOverlay').addEventListener('click', function (event) {
    if (event.target.id === 'modalOverlay') modalBagla();
  });

  document.getElementById('axtarInput').addEventListener('input', function () {
    clearTimeout(axtarTimeout);
    axtarTimeout = setTimeout(function () { page = 1; siyahiYukle(); }, 350);
  });

  siyahiYukle();
})();
</script>
<?php require __DIR__ . '/partials/shell_foot.php'; ?>
