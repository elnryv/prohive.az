<?php
$__path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$__langUrl = static function (string $lang) use ($__path): string {
    $q = $_GET;
    $q['lang'] = $lang;
    return $__path . '?' . http_build_query($q);
};
?>
<!doctype html>
<html lang="<?= View::e(Lang::current()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= View::e($title ?? Lang::t('app.name')) ?></title>
<meta name="description" content="<?= View::e($description ?? Lang::t('app.tagline')) ?>">
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#14332A">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;600;800&display=swap">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/"><?= View::e(Lang::t('app.name')) ?></a>
    <nav class="main-nav">
        <a href="/#bolgeler"><?= View::e(Lang::t('nav.regions')) ?></a>
        <a href="/ev-sahibi-ol" class="nav-cta"><?= View::e(Lang::t('footer.add_house')) ?></a>
        <a href="/sahib/giris"><?= View::e(Lang::t('nav.owner_login')) ?></a>
    </nav>
    <nav class="lang-switch">
        <a href="<?= View::e($__langUrl('az')) ?>" hreflang="az" class="<?= Lang::current() === 'az' ? 'is-active' : '' ?>">AZ</a>
        <a href="<?= View::e($__langUrl('ru')) ?>" hreflang="ru" class="<?= Lang::current() === 'ru' ? 'is-active' : '' ?>">RU</a>
        <a href="<?= View::e($__langUrl('en')) ?>" hreflang="en" class="<?= Lang::current() === 'en' ? 'is-active' : '' ?>">EN</a>
    </nav>
</header>
<main><?= $content ?></main>
<footer class="site-footer">
    <div class="site-footer__cta">
        <a class="btn btn--primary" href="/ev-sahibi-ol"><?= View::e(Lang::t('footer.add_house')) ?></a>
    </div>
    <nav class="site-footer__links">
        <a href="/haqqinda"><?= View::e(Lang::t('footer.about')) ?></a>
        <a href="/sertler"><?= View::e(Lang::t('footer.terms')) ?></a>
        <a href="/mexfilik"><?= View::e(Lang::t('footer.privacy')) ?></a>
    </nav>
    <p>&copy; <?= date('Y') ?> <?= View::e(Lang::t('app.name')) ?>. <?= View::e(Lang::t('footer.rights')) ?></p>
</footer>
<script src="/assets/js/app.js"></script>
</body>
</html>
