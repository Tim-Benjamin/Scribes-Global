<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="<?= $pageDescription ?? 'Scribes Global - A creative community of poets, worship leaders, and artists spreading the Gospel through creative arts.' ?>">
    <meta name="keywords" content="<?= $pageKeywords ?? 'Scribes Global, Christian Poetry, Worship, Creative Arts, Ministry' ?>">
    <meta name="author" content="Scribes Global">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= $pageTitle ?? 'Scribes Global' ?>">
    <meta property="og:description" content="<?= $pageDescription ?? 'A creative community spreading the Gospel through arts.' ?>">
    <meta property="og:image" content="<?= ASSETS_PATH ?>images/og-image.jpg">
    <meta property="og:url" content="<?= SITE_URL ?>">
    <meta property="og:type" content="website">

    <title><?= $pageTitle ?? 'Scribes Global' ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= ASSETS_PATH ?>images/logo/favicon.png">

    <!-- Main CSS -->
    <link rel="stylesheet" href="<?= ASSETS_PATH ?>css/main.css">

    <!-- Page Specific CSS -->
    <?php if (isset($pageCSS)): ?>
        <link rel="stylesheet" href="<?= ASSETS_PATH ?>css/pages/<?= $pageCSS ?>.css">
    <?php endif; ?>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

    <!-- AOS (Animate on Scroll) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <!-- Swiper.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!-- Leaflet.js for Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


    <!-- Leaflet CSS & JS (for chapters map) -->
    <?php if ($pageCSS === 'chapters' || $pageCSS === 'chapter-detail'): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <?php endif; ?>
</head>

<body>
    <?php if (!isset($noSplash) || !$noSplash): ?>
        <?php include __DIR__ . '/splash.php'; ?>
    <?php endif; ?>

    <?php if (!isset($noNav) || !$noNav): ?>
        <?php include __DIR__ . '/nav.php'; ?>
    <?php endif; ?>