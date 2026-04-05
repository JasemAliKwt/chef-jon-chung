<?php
/**
 * Recipe Card Partial
 * Expects $recipe variable to be set with recipe data + category_name.
 */
$thumb = $recipe['thumbnail_url'] ?: youTubeThumbnail($recipe['youtube_url']);
?>
<article class="recipe-card">
    <a href="<?= recipeUrl($recipe['slug']) ?>" class="card-image-link">
        <?php if ($thumb): ?>
            <img src="<?= h($thumb) ?>" alt="<?= h($recipe['title']) ?>" loading="lazy">
        <?php else: ?>
            <div class="card-placeholder">🍳</div>
        <?php endif; ?>
        <?php if (!empty($recipe['category_name'])): ?>
            <span class="recipe-category-badge"><?= h($recipe['category_name']) ?></span>
        <?php endif; ?>
    </a>
    <div class="card-body">
        <h3 class="card-title">
            <a href="<?= recipeUrl($recipe['slug']) ?>">
                <?= h($recipe['title']) ?>
            </a>
        </h3>
        <?php if ($recipe['description']): ?>
            <p class="card-excerpt"><?= h(mb_strimwidth($recipe['description'], 0, 120, '...')) ?></p>
        <?php endif; ?>
        <div class="recipe-meta-row">
            <?php if ($recipe['cook_time_minutes']): ?>
                <span class="recipe-meta-item">⏱ <?= $recipe['cook_time_minutes'] ?> min</span>
            <?php endif; ?>
            <span class="recipe-meta-item">📊 <?= h($recipe['difficulty']) ?></span>
        </div>
    </div>
</article>
