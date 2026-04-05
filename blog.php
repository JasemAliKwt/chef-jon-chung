<?php
/**
 * Blog Listing Page
 */
$pageTitle = 'Blog & Tips';
$pageDescription = 'Cooking tips, stories, and advice';

require_once __DIR__ . '/includes/db.php';

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$total = dbCount("SELECT COUNT(*) FROM blog_posts WHERE is_published = 1");
$pagination = paginate($total, $perPage, $page);

$posts = dbFetchAll(
    "SELECT * FROM blog_posts WHERE is_published = 1
     ORDER BY created_at DESC
     LIMIT ? OFFSET ?",
    [$perPage, $pagination['offset']]
);

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero-sm">
    <div class="container">
        <h1 class="page-hero-title">Blog & Tips</h1>
        <p class="page-hero-sub">Cooking tips, stories, and kitchen wisdom</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (empty($posts)): ?>
            <div class="empty-public">
                <span>📝</span>
                <h2>No posts yet</h2>
                <p>Blog posts and cooking tips are on the way!</p>
            </div>
        <?php else: ?>
            <div class="blog-grid">
                <?php foreach ($posts as $post): ?>
                    <?php include __DIR__ . '/includes/blog-card.php'; ?>
                <?php endforeach; ?>
            </div>

            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="pagination-public">
                    <?php if ($pagination['has_prev']): ?>
                        <a href="?page=<?= $pagination['current'] - 1 ?>" class="btn btn-outline">← Previous</a>
                    <?php endif; ?>
                    <span class="pagination-info">Page <?= $pagination['current'] ?> of <?= $pagination['total_pages'] ?></span>
                    <?php if ($pagination['has_next']): ?>
                        <a href="?page=<?= $pagination['current'] + 1 ?>" class="btn btn-outline">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
