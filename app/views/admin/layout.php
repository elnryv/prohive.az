<?php $loggedIn = AdminAuth::check(); ?>
<!doctype html>
<html lang="az">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= View::e($title ?? 'Admin — Birlikdə Getdik') ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="/assets/admin.css">
</head>
<body>
<?php if ($loggedIn): ?>
<header class="admin-header">
    <a class="admin-brand" href="/">Birlikdə Getdik — Admin</a>
    <nav class="admin-nav">
        <a href="/">Dashboard</a>
        <a href="/tesdiq">Təsdiq növbəsi</a>
        <a href="/owners">Ev sahibləri</a>
        <a href="/payments">Ödənişlər</a>
        <a href="/regions">Bölgələr</a>
        <a href="/amenities">Şəraitlər</a>
        <a href="/settings">Parametrlər</a>
        <a href="/logs">Loglar</a>
        <a href="/cixis" class="admin-nav__logout">Çıxış</a>
    </nav>
</header>
<?php endif; ?>
<main class="admin-main"><?= $content ?></main>
<?php if ($loggedIn): ?>
<script src="/assets/admin.js"></script>
<?php endif; ?>
</body>
</html>
