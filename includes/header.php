<?php
/**
 * Public Site Header
 *
 * Expects $pageTitle and optionally $pageDescription to be set before including.
 */
require_once __DIR__ . '/db.php';

$siteName    = getSetting('site_name', 'Chef Jon Chung');
$siteTagline = getSetting('site_tagline', 'Authentic Korean Recipes & More');
$socialYT    = getSetting('social_youtube');
$socialIG    = getSetting('social_instagram');
$socialTT    = getSetting('social_tiktok');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? $siteName) ?> — <?= h($siteName) ?></title>
    <meta name="description" content="<?= h($pageDescription ?? $siteTagline) ?>">

    <!-- Open Graph -->
    <meta property="og:title" content="<?= h($pageTitle ?? $siteName) ?>">
    <meta property="og:description" content="<?= h($pageDescription ?? $siteTagline) ?>">
    <meta property="og:type" content="website">
    <?php if (!empty($ogImage ?? '')): ?>
        <meta property="og:image" content="<?= h($ogImage) ?>">
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= SITE_URL ?>/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= SITE_URL ?>/assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= SITE_URL ?>/assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= SITE_URL ?>/assets/images/apple-touch-icon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- ─── Navigation ─────────────────────────── -->
<header class="site-header">
    <div class="container header-inner">
        <a href="<?= SITE_URL ?>/" class="site-logo">
            
            <span class="logo-text"><?= h($siteName) ?></span>
        </a>

        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu">
            <span></span><span></span><span></span>
        </button>

        <nav class="site-nav" id="siteNav">
            <a href="<?= SITE_URL ?>/" class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' && !isset($_GET['slug']) ? 'active' : '' ?>">Home</a>
            <a href="<?= pageUrl('recipes') ?>" class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'recipes.php' || basename($_SERVER['SCRIPT_NAME']) === 'recipe.php' ? 'active' : '' ?>">Recipes</a>
            <a href="<?= pageUrl('blog') ?>" class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'blog.php' || basename($_SERVER['SCRIPT_NAME']) === 'post.php' ? 'active' : '' ?>">Blog</a>
            <a href="<?= pageUrl('about') ?>" class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'about.php' ? 'active' : '' ?>">About</a>
            <a href="<?= pageUrl('contact') ?>" class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'contact.php' ? 'active' : '' ?>">Contact</a>
            <form class="nav-search" action="<?= pageUrl('recipes') ?>" method="GET">
                <input type="text" name="q" placeholder="Search..." value="<?= h($_GET['q'] ?? '') ?>" class="nav-search-input">
            </form>
        </nav>
    </div>
</header>

<main class="site-main">
