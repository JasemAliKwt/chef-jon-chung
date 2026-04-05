<?php
/**
 * Admin Panel Header
 * 
 * Included at the top of every admin page.
 * Expects $pageTitle to be set before including.
 */
requireAuth();
$flash = getFlash();
$unreadCount = dbCount("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'Admin') ?> — <?= h(SITE_NAME) ?> Admin</title>
    <link rel="icon" type="image/x-icon" href="<?= SITE_URL ?>/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">

<!-- ─── Top Bar ─────────────────────────────── -->
<header class="admin-topbar">
    <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <span></span><span></span><span></span>
        </button>
        <a href="<?= SITE_URL ?>/admin/" class="topbar-brand">
            <span><?= h(SITE_NAME) ?></span>
        </a>
    </div>
    <div class="topbar-right">
        <a href="<?= SITE_URL ?>/" class="topbar-link" target="_blank">View Site ↗</a>
        <span class="topbar-user"><?= h(currentUserName()) ?></span>
        <a href="<?= SITE_URL ?>/admin/logout.php" class="topbar-link topbar-logout">Log Out</a>
    </div>
</header>

<!-- ─── Sidebar ─────────────────────────────── -->
<aside class="admin-sidebar" id="adminSidebar">
    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-label">Main</span>
            <a href="<?= SITE_URL ?>/admin/" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'active' : '' ?>">
                <span class="nav-icon">📊</span> Dashboard
            </a>
            <a href="<?= SITE_URL ?>/admin/recipes.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'recipes.php' || basename($_SERVER['SCRIPT_NAME']) === 'recipe-edit.php' ? 'active' : '' ?>">
                <span class="nav-icon">🍜</span> Recipes
            </a>
            <a href="<?= SITE_URL ?>/admin/blog-posts.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'blog-posts.php' || basename($_SERVER['SCRIPT_NAME']) === 'blog-edit.php' ? 'active' : '' ?>">
                <span class="nav-icon">📝</span> Blog Posts
            </a>
            <a href="<?= SITE_URL ?>/admin/messages.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'messages.php' ? 'active' : '' ?>">
                <span class="nav-icon">💬</span> Messages
                <?php if ($unreadCount > 0): ?>
                    <span class="nav-badge"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="nav-section">
            <span class="nav-label">⚙️ Settings</span>
            <a href="<?= SITE_URL ?>/admin/categories.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'categories.php' ? 'active' : '' ?>">
                <span class="nav-icon">🏷️</span> Categories
            </a>
            <a href="<?= SITE_URL ?>/admin/settings.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'settings.php' ? 'active' : '' ?>">
                <span class="nav-icon">🔧</span> Site Settings
            </a>
            <a href="<?= SITE_URL ?>/admin/account.php" class="nav-item <?= basename($_SERVER['SCRIPT_NAME']) === 'account.php' ? 'active' : '' ?>">
                <span class="nav-icon">👤</span> My Account
            </a>
        </div>
    </nav>
</aside>

<!-- ─── Main Content Area ──────────────────── -->
<main class="admin-main" id="adminMain">
    <?php if ($flash): ?>
        <div class="flash-message flash-<?= h($flash['type']) ?>">
            <?= h($flash['message']) ?>
            <button class="flash-close" onclick="this.parentElement.remove()">×</button>
        </div>
    <?php endif; ?>
