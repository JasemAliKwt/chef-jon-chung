<?php
/**
 * Authentication & Authorization
 * 
 * Handles admin login, logout, and route protection.
 */

require_once __DIR__ . '/db.php';

/**
 * Attempt to log in with username and password
 * Includes basic rate limiting (5 attempts per 15 minutes)
 */
function attemptLogin(string $username, string $password): bool|string {
    // Rate limiting
    $maxAttempts = 5;
    $lockoutMinutes = 15;
    
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_first_attempt'] = time();
    }
    
    // Reset counter if lockout period has passed
    if (time() - ($_SESSION['login_first_attempt'] ?? 0) > ($lockoutMinutes * 60)) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_first_attempt'] = time();
    }
    
    if ($_SESSION['login_attempts'] >= $maxAttempts) {
        $remaining = ceil(($lockoutMinutes * 60 - (time() - $_SESSION['login_first_attempt'])) / 60);
        return "Too many login attempts. Please try again in {$remaining} minute(s).";
    }
    
    $user = dbFetchOne(
        "SELECT id, username, password_hash, display_name FROM users WHERE username = ?",
        [$username]
    );
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Reset attempts on success
        unset($_SESSION['login_attempts'], $_SESSION['login_first_attempt']);
        
        // Regenerate session ID to prevent fixation attacks
        session_regenerate_id(true);
        
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['logged_in']    = true;
        
        return true;
    }
    
    $_SESSION['login_attempts']++;
    return false;
}

/**
 * Log out the current user
 */
function logout(): void {
    $_SESSION = [];
    
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    
    session_destroy();
}

/**
 * Check if a user is currently logged in
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
}

/**
 * Require authentication — redirect to login if not logged in
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

/**
 * Get the current logged-in user's display name
 */
function currentUserName(): string {
    return $_SESSION['display_name'] ?? 'Admin';
}

/**
 * Update the current user's password
 */
function updatePassword(int $userId, string $newPassword): bool {
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    return dbExecute(
        "UPDATE users SET password_hash = ? WHERE id = ?",
        [$hash, $userId]
    ) > 0;
}

/**
 * Verify the current user's existing password
 */
function verifyCurrentPassword(int $userId, string $password): bool {
    $user = dbFetchOne("SELECT password_hash FROM users WHERE id = ?", [$userId]);
    return $user && password_verify($password, $user['password_hash']);
}

/**
 * Generate a proper password hash (used for initial setup)
 */
function generatePasswordHash(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}
