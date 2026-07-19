<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/settings.php';

header('Content-Type: text/html; charset=utf-8');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'");

$siteName = settings_get('site_name', 'Yük Birlikdə');
?><!doctype html>
<html lang="az">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#2563EB">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <title><?= htmlspecialchars($siteName) ?></title>
  <link rel="stylesheet" href="/assets/css/tokens.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/screens/splash.css">
  <link rel="stylesheet" href="/assets/css/screens/phone.css">
  <link rel="stylesheet" href="/assets/css/screens/pin.css">
  <link rel="stylesheet" href="/assets/css/screens/register.css">
  <link rel="stylesheet" href="/assets/css/screens/home.css">
</head>
<body>
  <div id="app" style="position:relative;min-height:100vh;max-width:480px;margin:0 auto;"></div>
  <script type="module" src="/assets/js/app.js"></script>
</body>
</html>
