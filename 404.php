<?php
/**
 * 404 — Page Not Found
 */
$pageTitle = 'Page Not Found';

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="empty-public">
            <span>🔍</span>
            <h2>Page Not Found</h2>
            <p>The page you're looking for doesn't exist or has been moved.</p>
            <div style="display: flex; gap: 12px; justify-content: center; margin-top: 8px;">
                <a href="<?= SITE_URL ?>/" class="btn btn-primary">Go Home</a>
                <a href="<?= pageUrl('recipes') ?>" class="btn btn-outline">Browse Recipes</a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
