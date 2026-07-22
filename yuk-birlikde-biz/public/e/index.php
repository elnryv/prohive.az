<?php
declare(strict_types=1);

// GET /e/{slug} — Hissə 4.5/9: qeydiyyatsız istifadəçi paylaşılan linki açanda
// elanı görür (OG kartı ilə), [Təklif ver] basanda qeydiyyat axınına yönləndirilir.
// Artıq daxil olmuş istifadəçi birbaşa SPA-nın elan detalı ekranına keçir.

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

$config = require __DIR__ . '/../../app/config.php';
$appUrl = rtrim($config['app_url'], '/');
$slug = preg_replace('/[^a-f0-9]/', '', (string) ($_GET['slug'] ?? ''));

$order = null;
if ($slug !== '') {
    $stmt = db()->prepare(
        "SELECT o.*, ct.name AS cargo_type_name FROM orders o
         JOIN cargo_types ct ON ct.id = o.cargo_type_id
         WHERE o.slug = :slug AND o.status != 'cancelled' LIMIT 1"
    );
    $stmt->execute(['slug' => $slug]);
    $order = $stmt->fetch() ?: null;
}

if ($order === null) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!doctype html>
<html lang="az"><head><meta charset="utf-8"><title>Elan tapılmadı — Yük.Birlikdə.biz</title></head>
<body style="font-family:sans-serif;text-align:center;padding:80px 20px;">
<h1>Elan tapılmadı</h1>
<p>Bu elan artıq mövcud deyil və ya keçidi bağlanıb.</p>
<a href="<?= e($appUrl) ?>">Yük.Birlikdə.biz-ə keçid</a>
</body></html>
<?php
    exit;
}

// Artıq daxil olmuş istifadəçi birbaşa SPA-nın elan ekranına yönləndirilir.
$user = session_user();
if ($user !== null) {
    header("Location: {$appUrl}/elan/{$order['id']}");
    exit;
}

$stmt = db()->prepare('SELECT path, thumb_path FROM order_images WHERE order_id = :id ORDER BY sort');
$stmt->execute(['id' => $order['id']]);
$images = $stmt->fetchAll();

$fromLabel = $order['from_city'] . (!empty($order['from_district']) ? ', ' . $order['from_district'] : '');
$toLabel = $order['to_city'] . (!empty($order['to_district']) ? ', ' . $order['to_district'] : '');
$whenLabel = date('d.m.Y, H:i', strtotime($order['date_time']));
$title = "{$order['cargo_type_name']}: {$fromLabel} → {$toLabel}";
$description = "{$whenLabel} · Yük.Birlikdə.biz — müştəriləri və yükdaşıma sürücülərini birləşdirən platforma.";
$ogImage = "{$appUrl}/og/{$order['slug']}.png";
$pageUrl = "{$appUrl}/e/{$order['slug']}";

$statusLabels = ['active' => 'Aktiv', 'waiting' => 'Təklif gözləyir', 'negotiating' => 'Danışıq gedir', 'closed' => 'Bağlanıb', 'expired' => 'Müddəti bitib'];
$acceptingOffers = in_array($order['status'], ['active', 'waiting'], true);

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="az">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> — Yük.Birlikdə.biz</title>
<meta name="description" content="<?= e($description) ?>">

<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:url" content="<?= e($pageUrl) ?>">
<meta property="og:site_name" content="Yük.Birlikdə.biz">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($title) ?>">
<meta name="twitter:description" content="<?= e($description) ?>">
<meta name="twitter:image" content="<?= e($ogImage) ?>">

<link rel="icon" href="/assets/icons/favicon-32.png" sizes="32x32">
<link rel="stylesheet" href="/assets/css/tokens.css">
<style>
  body { display: flex; align-items: flex-start; justify-content: center; padding: 24px; user-select: text; }
  .share-card { max-width: 480px; width: 100%; background: var(--card); border-radius: var(--r-card); box-shadow: var(--shadow-soft); overflow: hidden; }
  .share-card img.cover { width: 100%; height: 220px; object-fit: cover; display: block; }
  .share-body { padding: 24px; }
  .gallery { display: flex; gap: 8px; overflow-x: auto; margin-top: 12px; }
  .gallery img { width: 88px; height: 88px; border-radius: 12px; object-fit: cover; flex-shrink: 0; }
  .cta { display: block; text-align: center; margin-top: 20px; padding: 16px; border-radius: var(--r-button); background: var(--primary); color: #fff; font-weight: 600; text-decoration: none; }
  .cta.disabled { background: var(--border); color: var(--text-muted); pointer-events: none; }
  .badge { display: inline-block; padding: 4px 12px; border-radius: var(--r-chip); font-size: 13px; font-weight: 600; background: var(--primary-soft); color: var(--primary); }
</style>
</head>
<body>
  <div class="share-card">
    <img class="cover" src="<?= e($ogImage) ?>" alt="">
    <div class="share-body">
      <span class="badge"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span>
      <h1 class="h2" style="margin-top:12px;"><?= e($order['cargo_type_name']) ?></h1>
      <p class="body-text" style="margin-top:8px;font-weight:600;"><?= e($fromLabel) ?> → <?= e($toLabel) ?></p>
      <p class="small-text" style="color:var(--text-muted);margin-top:4px;"><?= e($whenLabel) ?></p>
      <?php if (!empty($order['note'])): ?>
        <p class="small-text" style="margin-top:12px;"><?= e($order['note']) ?></p>
      <?php endif; ?>

      <?php if ($images !== []): ?>
      <div class="gallery">
        <?php foreach ($images as $img): ?>
          <img src="/uploads/<?= e($img['thumb_path']) ?>" alt="">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if ($acceptingOffers): ?>
        <a class="cta" href="<?= e($appUrl) ?>/telefon">Təklif ver</a>
        <p class="caption-text" style="text-align:center;margin-top:8px;">Təklif göndərmək üçün qısa qeydiyyat lazımdır.</p>
      <?php else: ?>
        <span class="cta disabled">Bu elan artıq təklif qəbul etmir</span>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
