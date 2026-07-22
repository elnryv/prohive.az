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
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="Yük">
  <meta name="description" content="Müştəriləri və yükdaşıma sürücülərini birləşdirən rəqəmsal elan və təklif platforması.">
  <link rel="manifest" href="/manifest.json">
  <link rel="icon" href="/assets/icons/favicon-32.png" sizes="32x32">
  <link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
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
  <div id="viewport" style="position:relative;min-height:100vh;max-width:480px;margin:0 auto;">
    <div id="offline-banner"></div>
    <div id="app" style="position:relative;min-height:100vh;"></div>
  </div>
  <script type="module" src="/assets/js/app.js"></script>
</body>
</html>
