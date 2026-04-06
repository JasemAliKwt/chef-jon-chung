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
    echo '<section class="section"><div class="container"><div class="empty-public"><span>—</span><h2>Recipe not found</h2><p>It may have been removed or isn\'t published yet.</p><a href="' . pageUrl('recipes') . '" class="btn btn-primary">Browse Recipes</a></div></div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Track page view
trackPageView('recipe', $recipe['id']);

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    if (csrfValidate() && !hasVisitorRated($recipe['id'])) {
        $ratingVal = (int) $_POST['rating'];
        if ($ratingVal >= 1 && $ratingVal <= 5) {
            dbInsert(
                "INSERT INTO recipe_ratings (recipe_id, rating, visitor_ip) VALUES (?, ?, ?)",
                [$recipe['id'], $ratingVal, $_SERVER['REMOTE_ADDR'] ?? '']
            );
        }
    }
    header('Location: ' . recipeUrl($recipe['slug']) . '#rating');
    exit;
}

$pageTitle = $recipe['title'];
$pageDescription = $recipe['description'] ?: "Recipe for {$recipe['title']}";
$ogImage = $recipe['thumbnail_url'] ?: youTubeThumbnail($recipe['youtube_url']);

$ingredients = json_decode($recipe['ingredients'], true) ?: [];
$steps = json_decode($recipe['steps'], true) ?: [];
$allergens = formatAllergens($recipe['allergens'] ?? null);
$allergenOptions = getAllergenOptions();
$embedUrl = youTubeEmbed($recipe['youtube_url']);
$rating = getRecipeRating($recipe['id']);
$hasRated = hasVisitorRated($recipe['id']);

// Gallery images
$galleryImages = dbFetchAll(
    "SELECT * FROM recipe_images WHERE recipe_id = ? ORDER BY sort_order",
    [$recipe['id']]
);

// Related recipes — by shared ingredients first, then by category
$related = [];
if (!empty($ingredients)) {
    // Try to find recipes that share ingredients (simple keyword matching on first word)
    $ingredientKeywords = [];
    foreach (array_slice($ingredients, 0, 5) as $ing) {
        $words = explode(' ', strtolower(trim($ing)));
        $lastWord = end($words);
        if (strlen($lastWord) > 3) {
            $ingredientKeywords[] = $lastWord;
        }
    }

    if (!empty($ingredientKeywords)) {
        $likeClauses = [];
        $params = [$recipe['id']];
        foreach ($ingredientKeywords as $kw) {
            $likeClauses[] = "r.ingredients LIKE ?";
            $params[] = "%{$kw}%";
        }
        $likeSQL = implode(' OR ', $likeClauses);
        $params[] = 3;

        $related = dbFetchAll(
            "SELECT r.*, c.name as category_name
             FROM recipes r
             LEFT JOIN categories c ON r.category_id = c.id
             WHERE r.is_published = 1 AND r.id != ? AND ({$likeSQL})
             ORDER BY RAND() LIMIT ?",
            $params
        );
    }
}

// Fallback to category-based if not enough
if (count($related) < 3 && $recipe['category_id']) {
    $excludeIds = array_merge([$recipe['id']], array_column($related, 'id'));
    $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
    $limit = 3 - count($related);
    $params = array_merge([$recipe['category_id']], $excludeIds, [$limit]);

    $morRelated = dbFetchAll(
        "SELECT r.*, c.name as category_name
         FROM recipes r
         LEFT JOIN categories c ON r.category_id = c.id
         WHERE r.is_published = 1 AND r.category_id = ? AND r.id NOT IN ({$placeholders})
         ORDER BY RAND() LIMIT ?",
        $params
    );
    $related = array_merge($related, $morRelated);
}

require_once __DIR__ . '/includes/header.php';
?>

<article class="recipe-page">
    <!-- Recipe Header -->
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
                <?php if ($recipe['spice_level'] && $recipe['spice_level'] !== 'None'): ?>
                    <div class="meta-block">
                        <span class="meta-label">Spice Level</span>
                        <span class="meta-value"><?= spiceLevelIcon($recipe['spice_level']) ?> <?= h($recipe['spice_level']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($rating['count'] > 0): ?>
                    <div class="meta-block">
                        <span class="meta-label">Rating</span>
                        <span class="meta-value"><?= $rating['average'] ?> / 5 <span class="rating-count">(<?= $rating['count'] ?>)</span></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Dietary Tags -->
            <?php
                $dietaryTags = formatAllergens($recipe['dietary'] ?? null);
                $dietaryOptions = getDietaryOptions();
                if (!empty($dietaryTags)):
            ?>
                <div class="dietary-tags">
                    <?php foreach ($dietaryTags as $d): ?>
                        <span class="dietary-tag"><?= h($dietaryOptions[$d] ?? ucfirst($d)) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Allergen Warning -->
            <?php if (!empty($allergens)): ?>
                <div class="allergen-warning">
                    <strong>⚠ Allergen Warning:</strong>
                    <?php
                        $labels = [];
                        foreach ($allergens as $a) {
                            $labels[] = $allergenOptions[$a] ?? ucfirst($a);
                        }
                        echo h(implode(', ', $labels));
                    ?>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="recipe-actions">
                <button onclick="window.print()" class="btn btn-outline btn-sm">Print Recipe</button>
            </div>
        </div>
    </section>

    <!-- Video -->
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

    <!-- Photo Gallery -->
    <?php if (!empty($galleryImages)): ?>
    <section class="section recipe-gallery-section">
        <div class="container">
            <h2 class="section-title-sm">Photos</h2>
            <div class="recipe-gallery">
                <?php foreach ($galleryImages as $img): ?>
                    <a href="<?= h($img['image_url']) ?>" class="gallery-photo" onclick="openLightbox(this.href); return false;">
                        <img src="<?= h($img['image_url']) ?>" alt="<?= h($img['caption'] ?? $recipe['title']) ?>" loading="lazy">
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Ingredients & Steps -->
    <section class="section">
        <div class="container">
            <div class="recipe-content-grid">
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

    <!-- Rating -->
    <section class="section section-alt" id="rating">
        <div class="container">
            <div class="rating-section">
                <h2>Rate This Recipe</h2>
                <?php if ($hasRated): ?>
                    <p class="rating-thanks">Thanks for your rating!</p>
                    <div class="stars-display">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?= $i <= round($rating['average']) ? 'star-filled' : '' ?>">★</span>
                        <?php endfor; ?>
                        <span class="rating-avg"><?= $rating['average'] ?> / 5 (<?= $rating['count'] ?> rating<?= $rating['count'] !== 1 ? 's' : '' ?>)</span>
                    </div>
                <?php else: ?>
                    <p class="rating-prompt">How would you rate this recipe?</p>
                    <form method="POST" class="rating-form">
                        <?= csrfField() ?>
                        <div class="stars-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>">
                                <label for="star<?= $i ?>" class="star-label" title="<?= $i ?> star<?= $i !== 1 ? 's' : '' ?>">★</label>
                            <?php endfor; ?>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Submit Rating</button>
                    </form>
                    <?php if ($rating['count'] > 0): ?>
                        <span class="rating-avg"><?= $rating['average'] ?> / 5 (<?= $rating['count'] ?> rating<?= $rating['count'] !== 1 ? 's' : '' ?>)</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Related Recipes -->
    <?php if (!empty($related)): ?>
    <section class="section">
        <div class="container">
            <h2 class="section-title">You Might Also Like</h2>
            <div class="recipe-grid recipe-grid-3">
                <?php foreach ($related as $recipe): ?>
                    <?php include __DIR__ . '/includes/recipe-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</article>

<!-- Lightbox -->
<div class="lightbox-overlay" id="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
    <img src="" alt="" id="lightboxImg" onclick="event.stopPropagation()">
</div>

<script>
function openLightbox(src) {
    const lb = document.getElementById('lightbox');
    const img = document.getElementById('lightboxImg');
    img.src = src;
    lb.classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
