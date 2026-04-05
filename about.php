<?php
/**
 * About Page
 */
$pageTitle = 'About';

require_once __DIR__ . '/includes/db.php';

$aboutContent = getSetting('about_content', 'Welcome to my kitchen!');
$socialYT  = getSetting('social_youtube');
$socialIG  = getSetting('social_instagram');
$socialTT  = getSetting('social_tiktok');

// Convert plain text to HTML paragraphs
function aboutToHtml(string $text): string {
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

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero-sm">
    <div class="container">
        <h1 class="page-hero-title">About</h1>
    </div>
</section>

<section class="section">
    <div class="container blog-container">
        <div class="about-content prose">
            <?= aboutToHtml($aboutContent) ?>
        </div>

        <?php if ($socialYT || $socialIG || $socialTT): ?>
            <div class="about-social">
                <h2>Find Me Online</h2>
                <div class="social-links">
                    <?php if ($socialYT): ?>
                        <a href="<?= h($socialYT) ?>" target="_blank" rel="noopener" class="social-link">
                            YouTube
                        </a>
                    <?php endif; ?>
                    <?php if ($socialIG): ?>
                        <a href="<?= h($socialIG) ?>" target="_blank" rel="noopener" class="social-link">
                            Instagram
                        </a>
                    <?php endif; ?>
                    <?php if ($socialTT): ?>
                        <a href="<?= h($socialTT) ?>" target="_blank" rel="noopener" class="social-link">
                            TikTok
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
