<?php
/**
 * Admin — Site Settings
 *
 * Edit about page content, social links, footer text, etc.
 */
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Site Settings';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValidate()) {
        setFlash('error', 'Invalid request. Please try again.');
    } else {
        $settings = [
            'site_name'        => trim($_POST['site_name'] ?? ''),
            'site_tagline'     => trim($_POST['site_tagline'] ?? ''),
            'about_content'    => trim($_POST['about_content'] ?? ''),
            'social_youtube'   => trim($_POST['social_youtube'] ?? ''),
            'social_instagram' => trim($_POST['social_instagram'] ?? ''),
            'social_tiktok'    => trim($_POST['social_tiktok'] ?? ''),
            'footer_text'      => trim($_POST['footer_text'] ?? ''),
        ];

        foreach ($settings as $key => $value) {
            setSetting($key, $value);
        }

        setFlash('success', 'Settings saved!');
    }

    header('Location: ' . SITE_URL . '/admin/settings.php');
    exit;
}

// Load current values
$settings = [
    'site_name'        => getSetting('site_name', 'Chef Jon Chung'),
    'site_tagline'     => getSetting('site_tagline'),
    'about_content'    => getSetting('about_content'),
    'social_youtube'   => getSetting('social_youtube'),
    'social_instagram' => getSetting('social_instagram'),
    'social_tiktok'    => getSetting('social_tiktok'),
    'footer_text'      => getSetting('footer_text'),
];

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Site Settings</h1>
</div>

<form method="POST">
    <?= csrfField() ?>

    <div class="settings-layout">
        <!-- General -->
        <div class="form-card">
            <h2 class="form-card-title">General</h2>

            <div class="form-group">
                <label for="site_name">Site Name</label>
                <input type="text" id="site_name" name="site_name"
                       value="<?= h($settings['site_name']) ?>"
                       placeholder="Chef Jon Chung">
            </div>

            <div class="form-group">
                <label for="site_tagline">Tagline</label>
                <input type="text" id="site_tagline" name="site_tagline"
                       value="<?= h($settings['site_tagline']) ?>"
                       placeholder="Authentic Korean Recipes & More">
                <span class="form-hint">Shown below the site name on the homepage.</span>
            </div>

            <div class="form-group">
                <label for="footer_text">Footer Text</label>
                <input type="text" id="footer_text" name="footer_text"
                       value="<?= h($settings['footer_text']) ?>"
                       placeholder="© 2026 Chef Jon Chung. All rights reserved.">
            </div>
        </div>

        <!-- About Page -->
        <div class="form-card">
            <h2 class="form-card-title">About Page</h2>

            <div class="form-group">
                <label for="about_content">About Content</label>
                <textarea id="about_content" name="about_content" rows="12"
                          placeholder="Write about Chef Jon — his story, his passion for cooking, his Korean heritage..."><?= h($settings['about_content']) ?></textarea>
                <span class="form-hint">This text appears on the About page. Use blank lines for paragraph breaks.</span>
            </div>
        </div>

        <!-- Social Links -->
        <div class="form-card">
            <h2 class="form-card-title">Social Media Links</h2>

            <div class="form-group">
                <label for="social_youtube">YouTube Channel URL</label>
                <input type="url" id="social_youtube" name="social_youtube"
                       value="<?= h($settings['social_youtube']) ?>"
                       placeholder="https://youtube.com/@chefjonchung">
            </div>

            <div class="form-group">
                <label for="social_instagram">Instagram URL</label>
                <input type="url" id="social_instagram" name="social_instagram"
                       value="<?= h($settings['social_instagram']) ?>"
                       placeholder="https://instagram.com/chefjonchung">
            </div>

            <div class="form-group">
                <label for="social_tiktok">TikTok URL</label>
                <input type="url" id="social_tiktok" name="social_tiktok"
                       value="<?= h($settings['social_tiktok']) ?>"
                       placeholder="https://tiktok.com/@chefjonchung">
            </div>
        </div>

        <!-- Save -->
        <div class="form-save-bar">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </div>
</form>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
