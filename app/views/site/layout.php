<!doctype html>
<html lang="<?= View::e(Lang::current()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= View::e($title ?? Lang::t('app.name')) ?></title>
<meta name="description" content="<?= View::e($description ?? Lang::t('app.tagline')) ?>">
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#14332A">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/"><?= View::e(Lang::t('app.name')) ?></a>
    <nav class="lang-switch">
        <a href="?lang=az" hreflang="az">AZ</a>
        <a href="?lang=ru" hreflang="ru">RU</a>
        <a href="?lang=en" hreflang="en">EN</a>
    </nav>
</header>
<main><?= $content ?></main>
<footer class="site-footer">
    <a href="/haqqinda"><?= View::e(Lang::t('footer.about')) ?></a>
    <a href="/sertler"><?= View::e(Lang::t('footer.terms')) ?></a>
    <a href="/mexfilik"><?= View::e(Lang::t('footer.privacy')) ?></a>
    <p>&copy; <?= date('Y') ?> <?= View::e(Lang::t('app.name')) ?>. <?= View::e(Lang::t('footer.rights')) ?></p>
</footer>
<script src="/assets/js/app.js"></script>
</body>
</html>
