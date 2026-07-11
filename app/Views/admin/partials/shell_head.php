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
<meta name="theme-color" content="#000000">
<link rel="icon" href="/assets/icons/icon-192.png">
<link rel="stylesheet" href="/assets/css/admin.css">
<script src="/assets/js/admin.js"></script>
</head>
<body>
<div class="admin-shell">
  <div class="admin-nav glass">
    <div class="brand"><?= htmlspecialchars($t('ortaq.app_adi')) ?></div>
    <a href="/panel" class="<?= $aktivSehife === 'dashboard' ? 'active' : '' ?>"><?= htmlspecialchars($t('admin.dashboard')) ?></a>
    <a href="/panel/musteriler" class="<?= $aktivSehife === 'musteriler' ? 'active' : '' ?>"><?= htmlspecialchars($t('admin.musteriler')) ?></a>
    <a href="/panel/kuryerler" class="<?= $aktivSehife === 'kuryerler' ? 'active' : '' ?>"><?= htmlspecialchars($t('admin.kuryerler')) ?></a>
    <a href="/panel/sifarisler" class="<?= $aktivSehife === 'sifarisler' ? 'active' : '' ?>"><?= htmlspecialchars($t('admin.sifarisler')) ?></a>
    <a href="/panel/bannerler" class="<?= $aktivSehife === 'bannerler' ? 'active' : '' ?>"><?= htmlspecialchars($t('admin.bannerler')) ?></a>
    <a href="/panel/erazi" class="<?= $aktivSehife === 'erazi' ? 'active' : '' ?>"><?= htmlspecialchars($t('admin.erazi')) ?></a>
    <a href="#" id="adminCixisBtn"><?= htmlspecialchars($t('ortaq.cixis')) ?></a>
  </div>
  <div class="admin-main">
