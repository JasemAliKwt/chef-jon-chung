<?php
/**
 * Admin Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Dashboard';

// Gather stats
$totalRecipes    = dbCount("SELECT COUNT(*) FROM recipes");
$publishedRecipes = dbCount("SELECT COUNT(*) FROM recipes WHERE is_published = 1");
$draftRecipes    = $totalRecipes - $publishedRecipes;
$totalPosts      = dbCount("SELECT COUNT(*) FROM blog_posts");
$totalMessages   = dbCount("SELECT COUNT(*) FROM contact_messages");
$unreadMessages  = dbCount("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
$totalCategories = dbCount("SELECT COUNT(*) FROM categories");

// Recent recipes
$recentRecipes = dbFetchAll(
    "SELECT r.*, c.name as category_name 
     FROM recipes r 
     LEFT JOIN categories c ON r.category_id = c.id 
     ORDER BY r.created_at DESC LIMIT 5"
);

// Recent messages
$recentMessages = dbFetchAll(
    "SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5"
);

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Welcome back, <?= h(currentUserName()) ?>! 👋</h1>
    <a href="<?= SITE_URL ?>/admin/recipe-edit.php" class="btn btn-primary">+ New Recipe</a>
</div>

<!-- ─── Stats Cards ────────────────────────── -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">🍜</div>
        <div class="stat-info">
            <span class="stat-number"><?= $totalRecipes ?></span>
            <span class="stat-label">Recipes</span>
        </div>
        <div class="stat-detail"><?= $publishedRecipes ?> published · <?= $draftRecipes ?> drafts</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📝</div>
        <div class="stat-info">
            <span class="stat-number"><?= $totalPosts ?></span>
            <span class="stat-label">Blog Posts</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">💬</div>
        <div class="stat-info">
            <span class="stat-number"><?= $totalMessages ?></span>
            <span class="stat-label">Messages</span>
        </div>
        <?php if ($unreadMessages > 0): ?>
            <div class="stat-detail stat-highlight"><?= $unreadMessages ?> unread</div>
        <?php endif; ?>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🏷️</div>
        <div class="stat-info">
            <span class="stat-number"><?= $totalCategories ?></span>
            <span class="stat-label">Categories</span>
        </div>
    </div>
</div>

<!-- ─── Recent Activity ────────────────────── -->
<div class="dashboard-grid">
    <!-- Recent Recipes -->
    <div class="dashboard-panel">
        <div class="panel-header">
            <h2>Recent Recipes</h2>
            <a href="<?= SITE_URL ?>/admin/recipes.php" class="panel-link">View All →</a>
        </div>
        <?php if (empty($recentRecipes)): ?>
            <div class="empty-state">
                <p>No recipes yet.</p>
                <a href="<?= SITE_URL ?>/admin/recipe-edit.php" class="btn btn-sm">Add your first recipe</a>
            </div>
        <?php else: ?>
            <div class="panel-list">
                <?php foreach ($recentRecipes as $recipe): ?>
                    <div class="panel-list-item">
                        <div class="panel-item-info">
                            <a href="<?= SITE_URL ?>/admin/recipe-edit.php?id=<?= $recipe['id'] ?>" class="panel-item-title">
                                <?= h($recipe['title']) ?>
                            </a>
                            <span class="panel-item-meta">
                                <?= hs($recipe['category_name'] ?? 'Uncategorized') ?>
                                · <?= date('M j', strtotime($recipe['created_at'])) ?>
                            </span>
                        </div>
                        <span class="status-badge <?= $recipe['is_published'] ? 'status-published' : 'status-draft' ?>">
                            <?= $recipe['is_published'] ? 'Published' : 'Draft' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Messages -->
    <div class="dashboard-panel">
        <div class="panel-header">
            <h2>Recent Messages</h2>
            <a href="<?= SITE_URL ?>/admin/messages.php" class="panel-link">View All →</a>
        </div>
        <?php if (empty($recentMessages)): ?>
            <div class="empty-state">
                <p>No messages yet.</p>
            </div>
        <?php else: ?>
            <div class="panel-list">
                <?php foreach ($recentMessages as $msg): ?>
                    <div class="panel-list-item <?= !$msg['is_read'] ? 'unread' : '' ?>">
                        <div class="panel-item-info">
                            <span class="panel-item-title"><?= h($msg['sender_name']) ?></span>
                            <span class="panel-item-meta">
                                <?= h(mb_strimwidth($msg['message'], 0, 60, '...')) ?>
                            </span>
                        </div>
                        <span class="panel-item-date"><?= date('M j', strtotime($msg['created_at'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
