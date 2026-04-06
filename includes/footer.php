
</main><!-- /.site-main -->

<!-- ─── Footer ─────────────────────────────── -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-top">
            <div class="footer-brand">
                
                <span class="footer-name"><?= h($siteName) ?></span>
                <p class="footer-tagline"><?= h($siteTagline) ?></p>
            </div>

            <div class="footer-links">
                <div class="footer-col">
                    <h4>Explore</h4>
                    <a href="<?= pageUrl('recipes') ?>">Recipes</a>
                    <a href="<?= pageUrl('blog') ?>">Blog & Tips</a>
                    <a href="<?= pageUrl('about') ?>">About</a>
                    <a href="<?= pageUrl('contact') ?>">Contact</a>
                </div>

                <?php if ($socialYT || $socialIG || $socialTT): ?>
                <div class="footer-col">
                    <h4>Follow</h4>
                    <?php if ($socialYT): ?>
                        <a href="<?= h($socialYT) ?>" target="_blank" rel="noopener">YouTube</a>
                    <?php endif; ?>
                    <?php if ($socialIG): ?>
                        <a href="<?= h($socialIG) ?>" target="_blank" rel="noopener">Instagram</a>
                    <?php endif; ?>
                    <?php if ($socialTT): ?>
                        <a href="<?= h($socialTT) ?>" target="_blank" rel="noopener">TikTok</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Newsletter Signup -->
        <div class="footer-newsletter">
            <h4>Stay Updated</h4>
            <p>Get notified when new recipes are posted.</p>
            <form class="newsletter-form" method="POST" action="<?= SITE_URL ?>/subscribe.php">
                <?= csrfField() ?>
                <input type="text" name="name" placeholder="Your name" class="newsletter-input" required>
                <input type="email" name="email" placeholder="Your email" class="newsletter-input" required>
                <button type="submit" class="btn btn-primary newsletter-btn">Subscribe</button>
            </form>
        </div>

        <div class="footer-bottom">
            <p><?= h(getSetting('footer_text', '© ' . date('Y') . ' ' . $siteName)) ?></p>
        </div>
    </div>

    <!-- Korean-inspired decorative border -->
    <div class="footer-pattern"></div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
