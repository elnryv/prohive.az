<?php
/** @var array $banners */
use App\Core\Csrf;
?>
<h1 style="display:flex;align-items:center;gap:8px"><?= icon('camera') ?> Bannerlər</h1>

<?php if (isset($_GET['xeta'])): ?>
  <div class="banner banner-error">Xəta: <?= e((string) $_GET['xeta']) ?></div>
<?php endif; ?>

<form method="post" action="/bannerler" enctype="multipart/form-data" class="card">
  <?= Csrf::field() ?>
  <div class="field">
    <label>Şəkil</label>
    <label class="upload-tile" for="banner_image">
      <span class="upload-tile-icon"><?= icon('camera', 'icon', 18) ?></span>
      <span id="banner_image_text">Şəkil seç</span>
      <input type="file" id="banner_image" name="image" accept="image/*" required
        onchange="document.getElementById('banner_image_text').textContent = this.files.length ? this.files[0].name : 'Şəkil seç'">
    </label>
  </div>
  <div class="field">
    <label>Başlıq (AZ, könüllü)</label>
    <input type="text" name="title_az">
  </div>
  <div class="field">
    <label>Başlıq (RU, könüllü)</label>
    <input type="text" name="title_ru">
  </div>
  <div class="field">
    <label>Başlıq (EN, könüllü)</label>
    <input type="text" name="title_en">
  </div>
  <div class="field">
    <label>Keçid linki (könüllü)</label>
    <input type="url" name="link_url" placeholder="https://...">
  </div>
  <div class="field">
    <label>Sıra nömrəsi</label>
    <input type="number" name="sort_order" value="0" min="0">
  </div>
  <button type="submit" class="btn btn-amber btn-block">Banner əlavə et</button>
</form>

<h2 style="margin-top:20px">Mövcud bannerlər</h2>
<?php if ($banners === []): ?>
  <div class="empty-state"><p>Hələ banner yoxdur</p></div>
<?php else: ?>
<div class="admin-list">
  <?php foreach ($banners as $b): ?>
    <div class="admin-row">
      <div style="display:flex;gap:12px;align-items:center">
        <img src="/uploads/banners/<?= e($b['image']) ?>" style="width:64px;height:44px;object-fit:cover;border-radius:8px;flex:none">
        <div style="min-width:0;flex:1">
          <div class="admin-row-title"><?= e($b['title_az'] ?? '(başlıqsız)') ?></div>
          <div class="admin-row-meta">
            <span><?= icon('list', 'icon', 14) ?> Sıra: <?= (int) $b['sort_order'] ?></span>
            <span class="chip <?= (int) $b['is_active'] === 1 ? 'chip-ok' : 'chip-muted' ?>"><?= (int) $b['is_active'] === 1 ? 'Aktiv' : 'Söndürülüb' ?></span>
          </div>
        </div>
      </div>
      <div style="display:flex;gap:8px;margin-top:10px">
        <form method="post" action="/bannerler/<?= (int) $b['id'] ?>/aktivlik">
          <?= Csrf::field() ?>
          <button type="submit" class="btn btn-outline btn-sm"><?= (int) $b['is_active'] === 1 ? 'Söndür' : 'Aktivləşdir' ?></button>
        </form>
        <form method="post" action="/bannerler/<?= (int) $b['id'] ?>/sil" onsubmit="return confirm('Bu banner silinsin?')">
          <?= Csrf::field() ?>
          <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger);border-color:var(--danger)"><?= icon('trash', 'icon', 14) ?> Sil</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
