<?php
/**
 * @var callable $t
 */
$aktivSehife = 'bannerler';
require __DIR__ . '/partials/shell_head.php';
?>
<div class="admin-topbar">
  <h1><?= htmlspecialchars($t('admin.bannerler')) ?></h1>
</div>

<div class="glass" style="padding:20px; margin-bottom:20px;">
  <h3 style="margin-top:0;"><?= htmlspecialchars($t('admin.yarat')) ?></h3>
  <div class="error-box" id="errBox"></div>
  <form id="bannerForm">
    <div class="field">
      <label>Başlıq</label>
      <input type="text" name="baslik">
    </div>
    <div class="field">
      <label>Link (opsional)</label>
      <input type="text" name="link">
    </div>
    <div class="field">
      <label>Hədəf</label>
      <select name="hedef">
        <option value="hamisi">Hamısı</option>
        <option value="musteri">Müştəri</option>
        <option value="kurye">Kuryer</option>
      </select>
    </div>
    <div class="field">
      <label>Başlama tarixi</label>
      <input type="date" name="baslama" required>
    </div>
    <div class="field">
      <label>Bitmə tarixi</label>
      <input type="date" name="bitme" required>
    </div>
    <div class="field">
      <label>Sıra</label>
      <input type="number" name="sira" value="0">
    </div>
    <div class="field">
      <label>Şəkil (tövsiyə olunan ölçü: 1080×300, JPEG/PNG/WEBP, maks 5MB)</label>
      <input type="file" name="sekil" accept="image/jpeg,image/png,image/webp" required>
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t('admin.yarat')) ?></button>
  </form>
</div>

<div id="bannerSiyahi" class="stat-grid"></div>

<script>
(function () {
  var errBox = document.getElementById('errBox');

  async function siyahiYukle() {
    var res = await BirlikdeAdmin.api('GET', '/bannerler');
    if (BirlikdeAdmin.redirectIfUnauthorized(res.status)) return;

    var container = document.getElementById('bannerSiyahi');
    container.innerHTML = res.data.data.map(function (b) {
      return '<div class="glass" style="padding:12px;">' +
        '<img src="/banner-sekil/' + BirlikdeAdmin.escapeHtml(b.sekil_yol) + '" style="width:100%; border-radius:10px; margin-bottom:8px;">' +
        '<div>' + BirlikdeAdmin.escapeHtml(b.baslik || '—') + '</div>' +
        '<div style="font-size:12px; color:var(--text-dim);">' + BirlikdeAdmin.escapeHtml(b.baslama) + ' — ' + BirlikdeAdmin.escapeHtml(b.bitme) + '</div>' +
        '<button class="btn btn-small ' + (b.aktiv ? 'btn-danger' : 'btn-success') + '" data-id="' + b.id + '" data-aktiv="' + (b.aktiv ? '0' : '1') + '" style="margin-top:8px;">' +
        (b.aktiv ? 'Deaktiv et' : 'Aktivləşdir') + '</button>' +
        '</div>';
    }).join('');

    Array.from(container.querySelectorAll('button[data-id]')).forEach(function (btn) {
      btn.addEventListener('click', async function () {
        await BirlikdeAdmin.api('POST', '/banner/' + btn.dataset.id + '/aktivlik', { aktiv: btn.dataset.aktiv });
        siyahiYukle();
      });
    });
  }

  document.getElementById('bannerForm').addEventListener('submit', async function (event) {
    event.preventDefault();
    BirlikdeAdmin.hideError(errBox);
    var submitBtn = event.target.querySelector('button[type=submit]');
    submitBtn.disabled = true;

    var formData = new FormData(event.target);
    var res = await BirlikdeAdmin.api('POST', '/banner', formData);
    submitBtn.disabled = false;

    if (!res.ok) {
      BirlikdeAdmin.showError(errBox, (res.data && res.data.error) || 'Xəta baş verdi');
      return;
    }

    event.target.reset();
    siyahiYukle();
  });

  siyahiYukle();
})();
</script>
<?php require __DIR__ . '/partials/shell_foot.php'; ?>
