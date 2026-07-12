<?php
/**
 * @var callable $t
 * @var string $dil
 * @var string $aktivSehife
 */
$aktivSehife = $aktivSehife ?? '';
?><!doctype html>
<html lang="<?= htmlspecialchars($dil) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Birlikdə Admin</title>
<meta name="theme-color" content="#f5f3fb">
<link rel="icon" href="/assets/icons/icon-192.png">
<link rel="stylesheet" href="<?= \App\Core\Asset::v('css/admin.css') ?>">
<script src="<?= \App\Core\Asset::v('js/admin.js') ?>"></script>
</head>
<body>
<div class="admin-shell">
  <div class="admin-topbar-mobile">
    <button class="admin-burger" id="adminBurger" type="button" aria-label="Menyu"><span></span><span></span><span></span></button>
    <span class="brand"><?= htmlspecialchars($t('ortaq.app_adi')) ?> Admin</span>
    <span style="width:40px;"></span>
  </div>
  <div class="admin-drawer-scrim" id="adminDrawerScrim"></div>
  <nav class="admin-nav glass" id="adminNav">
    <div class="brand"><?= htmlspecialchars($t('ortaq.app_adi')) ?></div>
    <a href="/panel" class="<?= $aktivSehife === 'dashboard' ? 'active' : '' ?>"><span class="nav-icon">&#128202;</span><?= htmlspecialchars($t('admin.dashboard')) ?></a>
    <a href="/panel/musteriler" class="<?= $aktivSehife === 'musteriler' ? 'active' : '' ?>"><span class="nav-icon">&#128101;</span><?= htmlspecialchars($t('admin.musteriler')) ?></a>
    <a href="/panel/kuryerler" class="<?= $aktivSehife === 'kuryerler' ? 'active' : '' ?>"><span class="nav-icon">&#128757;</span><?= htmlspecialchars($t('admin.kuryerler')) ?></a>
    <a href="/panel/sifarisler" class="<?= $aktivSehife === 'sifarisler' ? 'active' : '' ?>"><span class="nav-icon">&#128230;</span><?= htmlspecialchars($t('admin.sifarisler')) ?></a>
    <a href="/panel/bannerler" class="<?= $aktivSehife === 'bannerler' ? 'active' : '' ?>"><span class="nav-icon">&#128247;</span><?= htmlspecialchars($t('admin.bannerler')) ?></a>
    <a href="/panel/erazi" class="<?= $aktivSehife === 'erazi' ? 'active' : '' ?>"><span class="nav-icon">&#128205;</span><?= htmlspecialchars($t('admin.erazi')) ?></a>
    <a href="#" id="adminCixisBtn"><span class="nav-icon">&#128682;</span><?= htmlspecialchars($t('ortaq.cixis')) ?></a>
  </nav>
  <div class="admin-main">
