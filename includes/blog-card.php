<?php
/**
 * Blog Card Partial
 * Expects $post variable to be set with blog post data.
 */
$blogThumb = $post['thumbnail_url'] ?: youTubeThumbnail($post['youtube_url']);
$postExcerpt = $post['excerpt'] ?: mb_strimwidth($post['body'], 0, 150, '...');
?>
<article class="blog-card">
    <?php if ($blogThumb): ?>
        <a href="<?= postUrl($post['slug']) ?>" class="card-image-link blog-card-image">
            <img src="<?= h($blogThumb) ?>" alt="<?= h($post['title']) ?>" loading="lazy">
        </a>
    <?php endif; ?>
    <div class="card-body">
        <span class="blog-date"><?= date('F j, Y', strtotime($post['created_at'])) ?></span>
        <h3 class="card-title">
            <a href="<?= postUrl($post['slug']) ?>">
                <?= h($post['title']) ?>
            </a>
        </h3>
        <p class="card-excerpt"><?= h($postExcerpt) ?></p>
        <a href="<?= postUrl($post['slug']) ?>" class="read-more">Read More →</a>
    </div>
</article>
