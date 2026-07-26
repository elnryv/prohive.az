<?php
declare(strict_types=1);

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function db_scalar(PDO $db, string $sql, array $params = []): mixed
{
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

const ADMIN_NAV = [
    ['group' => null, 'items' => [
        ['dashboard.php', 'Dashboard'],
    ]],
    ['group' => 'İstifadəçilər', 'items' => [
        ['customers.php', 'Müştərilər'],
        ['drivers.php', 'Sürücülər'],
    ]],
    ['group' => 'Platforma', 'items' => [
        ['orders.php', 'Elanlar'],
        ['offers.php', 'Təkliflər'],
        ['complaints.php', 'Şikayətlər'],
    ]],
    ['group' => 'Abunə', 'items' => [
        ['subscription-settings.php', 'Abunə parametrləri'],
        ['payments.php', 'Ödənişlər'],
    ]],
    ['group' => 'Məzmun', 'items' => [
        ['banners.php', 'Bannerlər'],
        ['broadcast.php', 'Bildiriş göndər'],
        ['cms.php', 'Səhifələr'],
    ]],
    ['group' => 'Sistem', 'items' => [
        ['analytics.php', 'Analitika'],
        ['settings.php', 'Parametrlər'],
        ['site-config.php', 'Sayt konfiqurasiyası'],
        ['backup.php', 'Backup'],
        ['error-log.php', 'Xəta jurnalı'],
        ['audit-log.php', 'Audit jurnalı'],
        ['search.php', 'Axtarış'],
    ]],
];

function admin_header(string $title, array $session): void
{
    $current = basename($_SERVER['SCRIPT_NAME']);
    header('Content-Type: text/html; charset=utf-8');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self' data:");
    ?>
<!doctype html>
<html lang="az">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?> — Yük.Birlikdə Admin</title>
<link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<div class="admin-shell">
  <div class="sidebar">
    <div class="brand">Yük.Birlikdə Admin</div>
    <nav>
      <?php foreach (ADMIN_NAV as $section): ?>
        <?php if ($section['group']): ?><div class="group-label"><?= h($section['group']) ?></div><?php endif; ?>
        <?php foreach ($section['items'] as [$href, $label]): ?>
          <a href="<?= h($href) ?>" class="<?= $current === $href ? 'active' : '' ?>"><?= h($label) ?></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
  </div>
  <div class="main">
    <div class="topbar">
      <h1 style="margin:0;font-size:18px;"><?= h($title) ?></h1>
      <div style="display:flex;align-items:center;gap:16px;">
        <span class="small-text" style="color:var(--text-muted);font-size:13px;"><?= h($session['username']) ?></span>
        <form method="post" action="logout.php"><?= admin_csrf_field($session) ?><button type="submit" class="btn btn-sm">Çıxış</button></form>
      </div>
    </div>
    <div class="content">
      <?php if (isset($_GET['flash'])): ?>
        <div class="flash flash-<?= $_GET['flash_type'] === 'error' ? 'error' : 'success' ?>"><?= h($_GET['flash']) ?></div>
      <?php endif; ?>
<?php
}

function admin_footer(): void
{
    ?>
    </div>
  </div>
</div>
</body>
</html>
<?php
}

function redirect_flash(string $to, string $message, string $type = 'success'): never
{
    $sep = str_contains($to, '?') ? '&' : '?';
    header("Location: {$to}{$sep}flash=" . rawurlencode($message) . "&flash_type={$type}");
    exit;
}

function badge(string $label, string $type = 'muted'): string
{
    return '<span class="badge badge-' . h($type) . '">' . h($label) . '</span>';
}

function paginate(int $page, int $totalPages, string $baseUrl): void
{
    if ($totalPages <= 1) {
        return;
    }
    echo '<div class="pagination">';
    for ($p = 1; $p <= $totalPages; $p++) {
        $sep = str_contains($baseUrl, '?') ? '&' : '?';
        if ($p === $page) {
            echo '<span class="current">' . $p . '</span>';
        } else {
            echo '<a href="' . h($baseUrl . $sep . 'page=' . $p) . '">' . $p . '</a>';
        }
    }
    echo '</div>';
}
