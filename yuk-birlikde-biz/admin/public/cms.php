<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$session = require_admin();
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_validate($session);
    $slug = (string) ($_POST['slug'] ?? '');
    $title = trim((string) ($_POST['title'] ?? ''));
    $content = (string) ($_POST['content'] ?? '');

    if ($title !== '') {
        $db->prepare('UPDATE pages SET title = :title, content = :content WHERE slug = :slug')
            ->execute(['title' => $title, 'content' => $content, 'slug' => $slug]);
        audit_log((int) $session['admin_id'], 'cms_update', 'pages', null, ['slug' => $slug]);
    }

    redirect_flash('cms.php?slug=' . urlencode($slug), 'Səhifə yeniləndi.');
}

$pages = $db->query('SELECT slug, title FROM pages ORDER BY slug')->fetchAll();
$activeSlug = (string) ($_GET['slug'] ?? ($pages[0]['slug'] ?? ''));

$stmt = $db->prepare('SELECT * FROM pages WHERE slug = :slug');
$stmt->execute(['slug' => $activeSlug]);
$page = $stmt->fetch();

admin_header('Səhifələr (CMS)', $session);
?>
<div class="tabs">
  <?php foreach ($pages as $p): ?>
    <a href="cms.php?slug=<?= h($p['slug']) ?>" class="<?= $p['slug'] === $activeSlug ? 'active' : '' ?>"><?= h($p['title']) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($page): ?>
<div class="card">
  <form method="post">
    <input type="hidden" name="slug" value="<?= h($page['slug']) ?>">
    <?= admin_csrf_field($session) ?>
    <div class="field"><label>Başlıq</label><input class="input" name="title" value="<?= h($page['title']) ?>"></div>
    <div class="field"><label>Məzmun (HTML)</label><textarea name="content" rows="14"><?= h($page['content']) ?></textarea></div>
    <button class="btn btn-primary" type="submit">Yadda saxla</button>
  </form>
</div>
<?php else: ?>
<div class="empty-state">Səhifə tapılmadı.</div>
<?php endif; ?>
<?php
admin_footer();
