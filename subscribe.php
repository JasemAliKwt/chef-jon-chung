<?php
/**
 * Newsletter Subscription Handler
 */
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValidate()) {
    header('Location: ' . SITE_URL . '/');
    exit;
}

$name  = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');

if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Check if already subscribed
    $existing = dbFetchOne("SELECT id, is_active FROM newsletter_subscribers WHERE email = ?", [$email]);

    if ($existing) {
        if (!$existing['is_active']) {
            dbExecute("UPDATE newsletter_subscribers SET is_active = 1, name = ? WHERE id = ?", [$name, $existing['id']]);
        }
    } else {
        dbInsert(
            "INSERT INTO newsletter_subscribers (email, name) VALUES (?, ?)",
            [$email, $name]
        );
    }
}

// Redirect back with success
setFlash('success', 'subscribed');
$referer = $_SERVER['HTTP_REFERER'] ?? SITE_URL . '/';
header('Location: ' . $referer);
exit;
