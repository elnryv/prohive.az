<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if (admin_session_current() !== null) {
    header('Location: dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim((string) ($_POST['identity'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    $admin = admin_login($identity, $password);
    if ($admin === null) {
        $error = 'İstifadəçi adı/e-poçt və ya şifrə yanlışdır, ya da hesab müvəqqəti bloklanıb.';
    } else {
        admin_session_create((int) $admin['id'], $remember);
        audit_log((int) $admin['id'], 'admin_login', 'admin_users', (int) $admin['id']);
        header('Location: dashboard.php');
        exit;
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="az">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Giriş — Yük.Birlikdə Admin</title>
<link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <h1>Admin girişi</h1>
    <?php if ($error): ?><div class="flash flash-error"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
      <div class="field">
        <label>İstifadəçi adı və ya E-poçt</label>
        <input class="input" type="text" name="identity" required autofocus>
      </div>
      <div class="field">
        <label>Şifrə</label>
        <input class="input" type="password" name="password" required>
      </div>
      <div class="field">
        <label style="display:flex;align-items:center;gap:8px;font-weight:400;">
          <input type="checkbox" name="remember" style="width:auto;"> Məni xatırla
        </label>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">Daxil ol</button>
    </form>
  </div>
</div>
</body>
</html>
