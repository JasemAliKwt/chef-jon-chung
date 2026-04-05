<?php
/**
 * Site Configuration
 * 
 * Database credentials and global constants.
 * IMPORTANT: Update these values for your hosting environment.
 * NEVER commit real credentials to GitHub — use environment variables
 * or a config.local.php override in production.
 */

// ── Load local config overrides FIRST (so they can set constants before defaults) ─
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// ── Database Credentials (override in config.local.php) ──
if (!defined('DB_HOST'))    define('DB_HOST', 'localhost');
if (!defined('DB_NAME'))    define('DB_NAME', 'chef_jon_chung');
if (!defined('DB_USER'))    define('DB_USER', 'root');
if (!defined('DB_PASS'))    define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// ── Site Settings ─────────────────────────────
if (!defined('SITE_URL'))        define('SITE_URL', 'http://localhost/chef-jon-chung');  // No trailing slash
if (!defined('SITE_NAME'))       define('SITE_NAME', "Chef Jon Chung");
if (!defined('UPLOADS_DIR'))     define('UPLOADS_DIR', __DIR__ . '/../assets/images/uploads/');
if (!defined('UPLOADS_URL'))     define('UPLOADS_URL', SITE_URL . '/assets/images/uploads/');
if (!defined('MAX_UPLOAD_SIZE')) define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// ── Security ──────────────────────────────────
define('CSRF_TOKEN_NAME', 'csrf_token');

// ── Session ───────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Timezone ──────────────────────────────────
date_default_timezone_set('America/Los_Angeles');

// ── Error Reporting (disable in production) ───
ini_set('display_errors', 1);
error_reporting(E_ALL);
