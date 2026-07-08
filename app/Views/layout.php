<!DOCTYPE html>
<html lang="az">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title ?? 'Birlikdə Bots') ?></title>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#000000">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php $isAuthPage = !($currentUser ?? null); ?>
<?php if ($isAuthPage): ?>
    <main class="auth-wrap">
        <?php if (!empty($flashSuccess)): ?><div class="flash flash-success"><?= e($flashSuccess) ?></div><?php endif; ?>
        <?php if (!empty($flashError)): ?><div class="flash flash-error"><?= e($flashError) ?></div><?php endif; ?>
        <?= $content ?>
    </main>
<?php else: ?>
    <div class="app-shell">
        <aside class="sidebar glass">
            <div class="brand">Birlikdə <span>Bots</span></div>
            <nav>
                <a href="/" class="<?= (($_SERVER['REQUEST_URI'] ?? '') === '/') ? 'active' : '' ?>">Dashboard</a>
                <a href="/bots" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/bots') ? 'active' : '' ?>">Botlarım</a>
            </nav>
            <?php if (!empty($bot)): ?>
            <div class="bot-context glass">
                <div class="bot-context-name"><?= e($bot['name']) ?></div>
                <div class="bot-sub-nav">
                    <a href="/bots/<?= (int) $bot['id'] ?>/nodes">Node-lar</a>
                    <a href="/bots/<?= (int) $bot['id'] ?>/subscribers">Abunəçilər</a>
                    <a href="/bots/<?= (int) $bot['id'] ?>/keywords">Açar sözlər</a>
                    <a href="/bots/<?= (int) $bot['id'] ?>/tags">Tag-lar</a>
                    <a href="/bots/<?= (int) $bot['id'] ?>/fields">Sahələr</a>
                    <a href="/bots/<?= (int) $bot['id'] ?>/broadcasts">Broadcast</a>
                    <a href="/bots/<?= (int) $bot['id'] ?>/sequences">Sequence-lər</a>
                    <a href="/bots/<?= (int) $bot['id'] ?>/edit">Bot ayarları</a>
                </div>
            </div>
            <?php endif; ?>
            <div class="sidebar-footer">
                <form method="post" action="/logout">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="btn-link">Çıxış</button>
                </form>
            </div>
        </aside>
        <main class="content">
            <header class="topbar glass">
                <div class="topbar-title"><?= e($title ?? '') ?></div>
                <div class="topbar-user"><?= e($currentUser['name'] ?? '') ?></div>
            </header>
            <?php if (!empty($flashSuccess)): ?><div class="flash flash-success"><?= e($flashSuccess) ?></div><?php endif; ?>
            <?php if (!empty($flashError)): ?><div class="flash flash-error"><?= e($flashError) ?></div><?php endif; ?>
            <div class="page-body">
                <?= $content ?>
            </div>
        </main>
    </div>
<?php endif; ?>
<script src="/assets/js/app.js"></script>
</body>
</html>
