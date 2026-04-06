<?php
/**
 * Homepage
 */
$pageTitle = 'Home';

require_once __DIR__ . '/includes/header.php';

trackPageView('home');

// Featured recipe
$featured = dbFetchOne(
    "SELECT r.*, c.name as category_name
     FROM recipes r
     LEFT JOIN categories c ON r.category_id = c.id
     WHERE r.is_published = 1 AND r.is_featured = 1
     ORDER BY r.updated_at DESC LIMIT 1"
);

// Latest recipes
$latestRecipes = dbFetchAll(
    "SELECT r.*, c.name as category_name
     FROM recipes r
     LEFT JOIN categories c ON r.category_id = c.id
     WHERE r.is_published = 1
     ORDER BY r.created_at DESC LIMIT 6"
);

// Latest blog posts
$latestPosts = dbFetchAll(
    "SELECT * FROM blog_posts WHERE is_published = 1
     ORDER BY created_at DESC LIMIT 3"
);

// Categories for browsing
$categories = dbFetchAll(
    "SELECT c.*, COUNT(r.id) as recipe_count
     FROM categories c
     INNER JOIN recipes r ON r.category_id = c.id AND r.is_published = 1
     GROUP BY c.id
     ORDER BY c.sort_order"
);
?>

<!-- ─── Hero ───────────────────────────────── -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <p class="hero-kicker">Welcome to</p>
            <h1 class="hero-title"><?= h($siteName) ?></h1>
            <p class="hero-subtitle"><?= h($siteTagline) ?></p>
            <a href="<?= pageUrl('recipes') ?>" class="btn btn-hero">Explore Recipes</a>
        </div>
    </div>
    <div class="hero-pattern"></div>
</section>

<?php if ($featured): ?>
<!-- ─── Featured Recipe ────────────────────── -->
<section class="section featured-section">
    <div class="container">
        <h2 class="section-title">Featured Recipe</h2>
        <div class="featured-card">
            <?php
                $thumb = $featured['thumbnail_url'] ?: youTubeThumbnail($featured['youtube_url']);
                if ($thumb):
            ?>
                <div class="featured-image">
                    <a href="<?= recipeUrl($featured['slug']) ?>">
                        <img src="<?= h($thumb) ?>" alt="<?= h($featured['title']) ?>">
                    </a>
                    <?php if ($featured['category_name']): ?>
                        <span class="recipe-category-badge"><?= h($featured['category_name']) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="featured-info">
                <h3>
                    <a href="<?= recipeUrl($featured['slug']) ?>">
                        <?= h($featured['title']) ?>
                    </a>
                </h3>
                <?php if ($featured['description']): ?>
                    <p><?= h(mb_strimwidth($featured['description'], 0, 200, '...')) ?></p>
                <?php endif; ?>
                <div class="recipe-meta-row">
                    <?php if ($featured['cook_time_minutes']): ?>
                        <span class="recipe-meta-item">⏱ <?= $featured['cook_time_minutes'] ?> min</span>
                    <?php endif; ?>
                    <?php if ($featured['servings']): ?>
                        <span class="recipe-meta-item">🍽 <?= $featured['servings'] ?> servings</span>
                    <?php endif; ?>
                    <span class="recipe-meta-item">📊 <?= h($featured['difficulty']) ?></span>
                </div>
                <a href="<?= recipeUrl($featured['slug']) ?>" class="btn btn-primary">
                    View Recipe →
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ─── Latest Recipes ─────────────────────── -->
<?php if (!empty($latestRecipes)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Latest Recipes</h2>
            <a href="<?= pageUrl('recipes') ?>" class="section-link">View All →</a>
        </div>
        <div class="recipe-grid">
            <?php foreach ($latestRecipes as $recipe): ?>
                <?php include __DIR__ . '/includes/recipe-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ─── Browse by Category ─────────────────── -->
<?php if (!empty($categories)): ?>
<section class="section section-alt">
    <div class="container">
        <h2 class="section-title center">Browse by Category</h2>
        <div class="category-pills">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= categoryUrl($cat['slug']) ?>" class="category-pill">
                    <?= h($cat['name']) ?>
                    <span class="pill-count"><?= $cat['recipe_count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ─── Latest Blog Posts ──────────────────── -->
<?php if (!empty($latestPosts)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">From the Blog</h2>
            <a href="<?= pageUrl('blog') ?>" class="section-link">Read More →</a>
        </div>
        <div class="blog-grid">
            <?php foreach ($latestPosts as $post): ?>
                <?php include __DIR__ . '/includes/blog-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
