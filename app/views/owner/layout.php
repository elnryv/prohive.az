<?php
$__path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$__langUrl = static function (string $lang) use ($__path): string {
    $q = $_GET;
    $q['lang'] = $lang;
    return $__path . '?' . http_build_query($q);
};
$__loggedIn = Auth::check();
?>
<!doctype html>
<html lang="<?= View::e(Lang::current()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= View::e($title ?? Lang::t('app.name')) ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#14332A">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;600;800&display=swap">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="owner-body">
<?php include APP_ROOT . '/app/views/shared/splash.php'; ?>
<header class="site-header">
    <a class="brand" href="/"><?= View::e(Lang::t('app.name')) ?></a>
    <?php if ($__loggedIn): ?>
    <nav class="main-nav">
        <a href="/sahib/panel"><?= View::e(Lang::t('owner.nav_panel')) ?></a>
        <a href="/sahib/teqvim"><?= View::e(Lang::t('owner.nav_calendar')) ?></a>
        <a href="/sahib/abune"><?= View::e(Lang::t('owner.nav_billing')) ?></a>
        <a href="/sahib/ev/yeni" class="nav-cta"><?= View::e(Lang::t('owner.nav_new_house')) ?></a>
        <a href="/sahib/cixis"><?= View::e(Lang::t('owner.nav_logout')) ?></a>
    </nav>
    <?php else: ?>
    <nav class="main-nav">
        <a href="/"><?= View::e(Lang::t('owner.nav_back_to_site')) ?></a>
    </nav>
    <?php endif; ?>
    <nav class="lang-switch">
        <a href="<?= View::e($__langUrl('az')) ?>" hreflang="az" class="<?= Lang::current() === 'az' ? 'is-active' : '' ?>">AZ</a>
        <a href="<?= View::e($__langUrl('ru')) ?>" hreflang="ru" class="<?= Lang::current() === 'ru' ? 'is-active' : '' ?>">RU</a>
        <a href="<?= View::e($__langUrl('en')) ?>" hreflang="en" class="<?= Lang::current() === 'en' ? 'is-active' : '' ?>">EN</a>
    </nav>
</header>
<main class="owner-main"><?= $content ?></main>

<?php if ($__loggedIn): ?>
<div id="toast-container" class="toast-container" aria-live="polite"
     data-sse-url="/sse"
     data-t-house-approved="<?= View::e(Lang::t('owner.toast_house_approved')) ?>"
     data-t-house-rejected="<?= View::e(Lang::t('owner.toast_house_rejected')) ?>"
     data-t-payment-ok="<?= View::e(Lang::t('owner.toast_payment_ok')) ?>"></div>
<?php endif; ?>

<?php $installMode = 'owner'; include APP_ROOT . '/app/views/shared/install_prompt.php'; ?>
<script src="/assets/js/app.js"></script>
</body>
</html>
