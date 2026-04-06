<?php
/**
 * Single Blog Post Page
 */
require_once __DIR__ . '/includes/db.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header('Location: ' . pageUrl('blog'));
    exit;
}

$post = dbFetchOne(
    "SELECT * FROM blog_posts WHERE slug = ? AND is_published = 1",
    [$slug]
);

if (!$post) {
    http_response_code(404);
    $pageTitle = 'Post Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="empty-public"><span>🔍</span><h2>Post not found</h2><p><a href="' . SITE_URL . '/blog.php" class="btn btn-primary">Back to Blog</a></p></div></div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $post['title'];
$pageDescription = $post['excerpt'] ?: mb_strimwidth($post['body'], 0, 160, '...');
$ogImage = $post['thumbnail_url'] ?: youTubeThumbnail($post['youtube_url']);
$embedUrl = youTubeEmbed($post['youtube_url']);

// Convert plain text body to HTML paragraphs
function textToHtml(string $text): string {
    $paragraphs = preg_split('/\n{2,}/', trim($text));
    $html = '';
    foreach ($paragraphs as $p) {
        $p = trim($p);
        if (!empty($p)) {
            $html .= '<p>' . nl2br(htmlspecialchars($p, ENT_QUOTES, 'UTF-8')) . '</p>';
        }
    }
    return $html;
}

trackPageView('blog', $post['id']);

require_once __DIR__ . '/includes/header.php';
?>

<article class="blog-post-page">
    <section class="section">
        <div class="container blog-container">
            <div class="blog-post-header">
                <a href="<?= pageUrl('blog') ?>" class="back-link-public">← Back to Blog</a>
                <h1><?= h($post['title']) ?></h1>
                <time class="blog-post-date"><?= date('F j, Y', strtotime($post['created_at'])) ?></time>
            </div>

            <?php if ($embedUrl): ?>
                <div class="video-wrapper blog-video">
                    <iframe src="<?= h($embedUrl) ?>" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                </div>
            <?php elseif ($post['thumbnail_url']): ?>
                <div class="blog-post-hero-image">
                    <img src="<?= h($post['thumbnail_url']) ?>" alt="<?= h($post['title']) ?>">
                </div>
            <?php endif; ?>

            <div class="blog-post-body prose">
                <?= textToHtml($post['body']) ?>
            </div>
        </div>
    </section>
</article>

<?php include __DIR__ . '/includes/footer.php'; ?>
