<?php
/**
 * Single Recipe Page
 */
require_once __DIR__ . '/includes/db.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header('Location: ' . pageUrl('recipes'));
    exit;
}

$recipe = dbFetchOne(
    "SELECT r.*, c.name as category_name, c.slug as category_slug
     FROM recipes r
     LEFT JOIN categories c ON r.category_id = c.id
     WHERE r.slug = ? AND r.is_published = 1",
    [$slug]
);

if (!$recipe) {
    http_response_code(404);
    $pageTitle = 'Recipe Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="empty-public"><span>🔍</span><h2>Recipe not found</h2><p>It may have been removed or isn\'t published yet.</p><a href="' . SITE_URL . '/recipes.php" class="btn btn-primary">Browse Recipes</a></div></div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $recipe['title'];
$pageDescription = $recipe['description'] ?: "Recipe for {$recipe['title']}";
$ogImage = $recipe['thumbnail_url'] ?: youTubeThumbnail($recipe['youtube_url']);

$ingredients = json_decode($recipe['ingredients'], true) ?: [];
$steps = json_decode($recipe['steps'], true) ?: [];
$embedUrl = youTubeEmbed($recipe['youtube_url']);

// Related recipes (same category, excluding this one)
$related = [];
if ($recipe['category_id']) {
    $related = dbFetchAll(
        "SELECT r.*, c.name as category_name
         FROM recipes r
         LEFT JOIN categories c ON r.category_id = c.id
         WHERE r.is_published = 1 AND r.category_id = ? AND r.id != ?
         ORDER BY RAND() LIMIT 3",
        [$recipe['category_id'], $recipe['id']]
    );
}

require_once __DIR__ . '/includes/header.php';
?>

<article class="recipe-page">
    <!-- ─── Recipe Header ──────────────────── -->
    <section class="recipe-header">
        <div class="container">
            <div class="recipe-breadcrumb">
                <a href="<?= pageUrl('recipes') ?>">Recipes</a>
                <?php if ($recipe['category_name']): ?>
                    <span class="breadcrumb-sep">→</span>
                    <a href="<?= categoryUrl($recipe['category_slug']) ?>">
                        <?= h($recipe['category_name']) ?>
                    </a>
                <?php endif; ?>
            </div>
            <h1 class="recipe-page-title"><?= h($recipe['title']) ?></h1>
            <?php if ($recipe['description']): ?>
                <p class="recipe-page-desc"><?= h($recipe['description']) ?></p>
            <?php endif; ?>

            <div class="recipe-meta-bar">
                <?php if ($recipe['cook_time_minutes']): ?>
                    <div class="meta-block">
                        <span class="meta-label">Cook Time</span>
                        <span class="meta-value"><?= $recipe['cook_time_minutes'] ?> min</span>
                    </div>
                <?php endif; ?>
                <?php if ($recipe['servings']): ?>
                    <div class="meta-block">
                        <span class="meta-label">Servings</span>
                        <span class="meta-value"><?= $recipe['servings'] ?></span>
                    </div>
                <?php endif; ?>
                <div class="meta-block">
                    <span class="meta-label">Difficulty</span>
                    <span class="meta-value"><?= h($recipe['difficulty']) ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── Video ──────────────────────────── -->
    <?php if ($embedUrl): ?>
    <section class="recipe-video-section">
        <div class="container">
            <div class="video-wrapper">
                <iframe src="<?= h($embedUrl) ?>" frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ─── Ingredients & Steps ────────────── -->
    <section class="section">
        <div class="container">
            <div class="recipe-content-grid">
                <!-- Ingredients -->
                <?php if (!empty($ingredients)): ?>
                <aside class="recipe-ingredients">
                    <h2>Ingredients</h2>
                    <ul class="ingredients-list">
                        <?php foreach ($ingredients as $item): ?>
                            <li>
                                <label class="ingredient-check">
                                    <input type="checkbox">
                                    <span><?= h($item) ?></span>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </aside>
                <?php endif; ?>

                <!-- Steps -->
                <?php if (!empty($steps)): ?>
                <div class="recipe-steps">
                    <h2>Instructions</h2>
                    <ol class="steps-list">
                        <?php foreach ($steps as $i => $step): ?>
                            <li>
                                <div class="step-number"><?= $i + 1 ?></div>
                                <div class="step-text"><?= h($step) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ─── Related Recipes ────────────────── -->
    <?php if (!empty($related)): ?>
    <section class="section section-alt">
        <div class="container">
            <h2 class="section-title">More <?= h($recipe['category_name']) ?> Recipes</h2>
            <div class="recipe-grid recipe-grid-3">
                <?php foreach ($related as $recipe): ?>
                    <?php include __DIR__ . '/includes/recipe-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</article>

<?php include __DIR__ . '/includes/footer.php'; ?>
