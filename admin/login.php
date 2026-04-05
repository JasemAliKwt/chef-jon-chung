<?php
/**
 * Admin Login Page
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/admin/');
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValidate()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $result = attemptLogin($username, $password);
            if ($result === true) {
                header('Location: ' . SITE_URL . '/admin/');
                exit;
            } elseif (is_string($result)) {
                // Rate limiting message
                $error = $result;
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In — <?= h(SITE_NAME) ?> Admin</title>
    <link rel="icon" type="image/x-icon" href="<?= SITE_URL ?>/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                
                <h1><?= h(SITE_NAME) ?></h1>
                <p>Admin Panel</p>
            </div>
            
            <?php if ($error): ?>
                <div class="flash-message flash-error"><?= h($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <?= csrfField() ?>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?= h($_POST['username'] ?? '') ?>"
                        autocomplete="username"
                        autofocus
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">Log In</button>
            </form>
        </div>
        <p class="login-footer">
            <a href="<?= SITE_URL ?>/">← Back to site</a>
        </p>
    </div>
</body>
</html>
