<?php
/**
 * @var callable $t
 * @var string $dil
 */
?><!doctype html>
<html lang="<?= htmlspecialchars($dil) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($t('admin.giris_basliq')) ?> — Birlikdə</title>
<meta name="theme-color" content="#f5f3fb">
<link rel="icon" href="/assets/icons/icon-192.png">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<div id="splashOverlay">
  <div class="splash-blob" style="width:64px;height:64px;background:var(--accent);"></div>
  <div class="splash-blob" style="width:46px;height:46px;background:var(--accent-warm);"></div>
  <div class="splash-blob" style="width:38px;height:38px;background:var(--pink);"></div>
  <div class="splash-blob" style="width:30px;height:30px;background:var(--warning);"></div>
  <div class="splash-blob" style="width:52px;height:52px;background:var(--success);"></div>
  <div class="splash-center">
    <div class="splash-word"><?= htmlspecialchars($t('ortaq.app_adi')) ?></div>
    <div class="splash-tag"><?= htmlspecialchars($t('admin.giris_basliq')) ?></div>
  </div>
</div>

<div class="login-wrap">
  <div class="login-card glass">
