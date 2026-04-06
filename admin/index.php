<?php
/**
 * Admin Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Dashboard';

// Stats
$totalRecipes     = dbCount("SELECT COUNT(*) FROM recipes");
$publishedRecipes = dbCount("SELECT COUNT(*) FROM recipes WHERE is_published = 1");
$draftRecipes     = $totalRecipes - $publishedRecipes;
$totalPosts       = dbCount("SELECT COUNT(*) FROM blog_posts");
$totalMessages    = dbCount("SELECT COUNT(*) FROM contact_messages");
$unreadMessages   = dbCount("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
$totalCategories  = dbCount("SELECT COUNT(*) FROM categories");
$totalSubscribers = dbCount("SELECT COUNT(*) FROM newsletter_subscribers WHERE is_active = 1");

// Analytics — page views
$viewsToday   = dbCount("SELECT COUNT(*) FROM page_views WHERE DATE(viewed_at) = CURDATE()");
$viewsWeek    = dbCount("SELECT COUNT(*) FROM page_views WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$viewsMonth   = dbCount("SELECT COUNT(*) FROM page_views WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");

// 7-day chart data — let MySQL handle dates to avoid timezone mismatch
$chartData = dbFetchAll(
    "SELECT DATE(viewed_at) as day, COUNT(*) as views
     FROM page_views
     WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(viewed_at)
     ORDER BY day ASC"
);

// Build chart keyed by MySQL's dates
$chart = [];
$mysqlDays = dbFetchAll(
    "SELECT DATE(DATE_SUB(NOW(), INTERVAL n DAY)) as day,
            DAYNAME(DATE_SUB(NOW(), INTERVAL n DAY)) as dayname
     FROM (SELECT 6 as n UNION SELECT 5 UNION SELECT 4 UNION SELECT 3 UNION SELECT 2 UNION SELECT 1 UNION SELECT 0) days
     ORDER BY n DESC"
);
foreach ($mysqlDays as $d) {
    $chart[$d['day']] = ['views' => 0, 'label' => substr($d['dayname'], 0, 3)];
}
foreach ($chartData as $row) {
    if (isset($chart[$row['day']])) {
        $chart[$row['day']]['views'] = (int) $row['views'];
    }
}
$chartMax = max(1, max(array_column($chart, 'views')));

// Traffic by section (last 30 days)
$sectionData = dbFetchAll(
    "SELECT page_type, COUNT(*) as views
     FROM page_views
     WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY page_type
     ORDER BY views DESC"
);
$sectionMax = 1;
if (!empty($sectionData)) {
    $sectionMax = max(1, (int) $sectionData[0]['views']);
}
$sectionLabels = [
    'home' => 'Home', 'recipe' => 'Recipes', 'recipes' => 'Browse',
    'blog' => 'Blog', 'about' => 'About', 'contact' => 'Contact',
];

// Popular recipes (by views, last 30 days)
$popularRecipes = dbFetchAll(
    "SELECT r.id, r.title, r.slug, COUNT(pv.id) as view_count
     FROM page_views pv
     INNER JOIN recipes r ON pv.page_id = r.id
     WHERE pv.page_type = 'recipe' AND pv.viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY r.id
     ORDER BY view_count DESC
     LIMIT 5"
);

// Top rated recipes
$topRated = dbFetchAll(
    "SELECT r.id, r.title, r.slug, AVG(rr.rating) as avg_rating, COUNT(rr.id) as rating_count
     FROM recipe_ratings rr
     INNER JOIN recipes r ON rr.recipe_id = r.id
     GROUP BY r.id
     HAVING rating_count >= 1
     ORDER BY avg_rating DESC, rating_count DESC
     LIMIT 5"
);

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
    <h1>Welcome back, <?= h(currentUserName()) ?>!</h1>
    <a href="<?= SITE_URL ?>/admin/recipe-edit.php" class="btn btn-primary">+ New Recipe</a>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-number"><?= $totalRecipes ?></span>
            <span class="stat-label">Recipes</span>
        </div>
        <div class="stat-detail"><?= $publishedRecipes ?> published · <?= $draftRecipes ?> drafts</div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-number"><?= $totalPosts ?></span>
            <span class="stat-label">Blog Posts</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-number"><?= $totalMessages ?></span>
            <span class="stat-label">Messages</span>
        </div>
        <?php if ($unreadMessages > 0): ?>
            <div class="stat-detail stat-highlight"><?= $unreadMessages ?> unread</div>
        <?php endif; ?>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-number"><?= $totalSubscribers ?></span>
            <span class="stat-label">Subscribers</span>
        </div>
    </div>
</div>

<!-- Analytics -->
<div class="stats-grid stats-grid-3">
    <div class="stat-card stat-card-compact">
        <span class="stat-number-sm"><?= $viewsToday ?></span>
        <span class="stat-label">Views Today</span>
    </div>
    <div class="stat-card stat-card-compact">
        <span class="stat-number-sm"><?= $viewsWeek ?></span>
        <span class="stat-label">This Week</span>
    </div>
    <div class="stat-card stat-card-compact">
        <span class="stat-number-sm"><?= $viewsMonth ?></span>
        <span class="stat-label">This Month</span>
    </div>
</div>

<!-- Charts Row -->
<div class="dashboard-grid">
    <!-- 7-Day Views -->
    <div class="dashboard-panel">
        <div class="panel-header">
            <h2>Daily Views</h2>
            <span class="panel-link"><?= $viewsWeek ?> this week</span>
        </div>
        <div class="chart-container">
            <?php foreach ($chart as $date => $data): ?>
                <div class="chart-bar-wrapper">
                    <span class="chart-bar-value"><?= $data['views'] ?></span>
                    <div class="chart-bar" style="height: <?= round(($data['views'] / $chartMax) * 100) ?>%;"></div>
                    <span class="chart-bar-label"><?= $data['label'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Traffic by Section -->
    <div class="dashboard-panel">
        <div class="panel-header">
            <h2>Traffic by Section</h2>
            <span class="panel-link">Last 30 days</span>
        </div>
        <div class="hbar-container">
            <?php if (empty($sectionData)): ?>
                <div class="empty-state"><p>No traffic data yet.</p></div>
            <?php else: ?>
                <?php foreach ($sectionData as $section): ?>
                    <div class="hbar-row">
                        <span class="hbar-label"><?= h($sectionLabels[$section['page_type']] ?? ucfirst($section['page_type'])) ?></span>
                        <div class="hbar-track">
                            <div class="hbar-fill" style="width: <?= round(($section['views'] / $sectionMax) * 100) ?>%;"></div>
                        </div>
                        <span class="hbar-value"><?= $section['views'] ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Content Grid -->
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
            <div class="empty-state"><p>No messages yet.</p></div>
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

<!-- Popular & Top Rated -->
<?php if (!empty($popularRecipes) || !empty($topRated)): ?>
<div class="dashboard-grid">
    <?php if (!empty($popularRecipes)): ?>
    <div class="dashboard-panel">
        <div class="panel-header">
            <h2>Most Viewed (30 days)</h2>
        </div>
        <div class="panel-list">
            <?php foreach ($popularRecipes as $i => $pr): ?>
                <div class="panel-list-item">
                    <div class="panel-item-info">
                        <span class="panel-rank"><?= $i + 1 ?></span>
                        <a href="<?= SITE_URL ?>/admin/recipe-edit.php?id=<?= $pr['id'] ?>" class="panel-item-title">
                            <?= h($pr['title']) ?>
                        </a>
                    </div>
                    <span class="panel-item-stat"><?= $pr['view_count'] ?> views</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($topRated)): ?>
    <div class="dashboard-panel">
        <div class="panel-header">
            <h2>Top Rated</h2>
        </div>
        <div class="panel-list">
            <?php foreach ($topRated as $i => $tr): ?>
                <div class="panel-list-item">
                    <div class="panel-item-info">
                        <span class="panel-rank"><?= $i + 1 ?></span>
                        <a href="<?= SITE_URL ?>/admin/recipe-edit.php?id=<?= $tr['id'] ?>" class="panel-item-title">
                            <?= h($tr['title']) ?>
                        </a>
                    </div>
                    <span class="panel-item-stat"><?= round($tr['avg_rating'], 1) ?> ★ (<?= $tr['rating_count'] ?>)</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
