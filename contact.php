<?php
/**
 * Contact Page
 */
$pageTitle = 'Contact';

require_once __DIR__ . '/includes/db.php';

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValidate()) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name))    $errors[] = 'Please enter your name.';
        if (empty($email))   $errors[] = 'Please enter your email.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
        if (empty($message)) $errors[] = 'Please enter a message.';

        // Simple honeypot spam check
        if (!empty($_POST['website'])) {
            // Bot detected — silently pretend success
            $success = true;
        } elseif (empty($errors)) {
            dbInsert(
                "INSERT INTO contact_messages (sender_name, sender_email, message)
                 VALUES (?, ?, ?)",
                [$name, $email, $message]
            );
            $success = true;
        }
    }
}

require_once __DIR__ . '/includes/header.php';

trackPageView('contact');
?>

<section class="page-hero page-hero-sm">
    <div class="container">
        <h1 class="page-hero-title">Get in Touch</h1>
        <p class="page-hero-sub">Questions, feedback, or just want to say hello?</p>
    </div>
</section>

<section class="section">
    <div class="container blog-container">
        <?php if ($success): ?>
            <div class="contact-success">
                <span class="success-icon">✉️</span>
                <h2>Message Sent!</h2>
                <p>Thanks for reaching out. I'll get back to you soon!</p>
                <a href="<?= SITE_URL ?>/" class="btn btn-primary">Back to Home</a>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="contact-errors">
                    <?php foreach ($errors as $err): ?>
                        <p><?= h($err) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="contact-form">
                <?= csrfField() ?>

                <!-- Honeypot (hidden from real users, bots fill it in) -->
                <div style="position:absolute;left:-9999px;top:-9999px;">
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input type="text" id="name" name="name"
                           value="<?= h($_POST['name'] ?? '') ?>"
                           placeholder="John Doe" required>
                </div>

                <div class="form-group">
                    <label for="email">Your Email</label>
                    <input type="email" id="email" name="email"
                           value="<?= h($_POST['email'] ?? '') ?>"
                           placeholder="john@example.com" required>
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="6"
                              placeholder="What's on your mind?" required><?= h($_POST['message'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
