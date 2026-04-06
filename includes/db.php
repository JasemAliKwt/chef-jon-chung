<?php
/**
 * Database Connection & Helper Functions
 * 
 * All queries use PDO prepared statements for security.
 */

require_once __DIR__ . '/config.php';

/**
 * Get PDO database connection (singleton pattern)
 */
function getDB(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('Database connection failed. Please check your configuration.');
        }
    }
    
    return $pdo;
}

/**
 * Execute a query and return all results
 */
function dbFetchAll(string $sql, array $params = []): array {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Execute a query and return a single row
 */
function dbFetchOne(string $sql, array $params = []): ?array {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Execute an INSERT/UPDATE/DELETE and return affected row count
 */
function dbExecute(string $sql, array $params = []): int {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Insert a row and return the new ID
 */
function dbInsert(string $sql, array $params = []): int {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return (int) getDB()->lastInsertId();
}

/**
 * Get a count from a query
 */
function dbCount(string $sql, array $params = []): int {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

// ── Slug Helper ───────────────────────────────

/**
 * Generate a URL-friendly slug from a string
 */
function createSlug(string $text): string {
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Generate a unique slug for a given table
 */
function uniqueSlug(string $table, string $text, ?int $excludeId = null): string {
    // Whitelist allowed table names to prevent SQL injection
    $allowedTables = ['recipes', 'blog_posts', 'categories'];
    if (!in_array($table, $allowedTables, true)) {
        throw new InvalidArgumentException("Invalid table name: {$table}");
    }
    
    $slug = createSlug($text);
    $base = $slug;
    $counter = 1;
    
    while (true) {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        if (dbCount($sql, $params) === 0) {
            break;
        }
        
        $slug = $base . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

// ── CSRF Protection ───────────────────────────

/**
 * Generate a CSRF token and store in session
 */
function csrfToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Output a hidden CSRF input field
 */
function csrfField(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrfToken() . '">';
}

/**
 * Validate the submitted CSRF token
 */
function csrfValidate(): bool {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (empty($token) || empty($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    $valid = hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    // Regenerate token after validation
    unset($_SESSION[CSRF_TOKEN_NAME]);
    return $valid;
}

// ── Sanitization ──────────────────────────────

/**
 * Sanitize a string for HTML output
 */
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize output, returning empty string for null
 */
function hs(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// ── Flash Messages ────────────────────────────

/**
 * Set a flash message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear the flash message
 */
function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// ── YouTube Helpers ───────────────────────────

/**
 * Extract YouTube video ID from various URL formats
 */
function extractYouTubeId(?string $url): ?string {
    if (empty($url)) return null;
    
    $patterns = [
        '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/live\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/',
        '/^([a-zA-Z0-9_-]{11})$/'  // Just the ID itself
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

/**
 * Get YouTube thumbnail URL from a video URL
 */
function youTubeThumbnail(?string $url): ?string {
    $id = extractYouTubeId($url);
    return $id ? "https://img.youtube.com/vi/{$id}/maxresdefault.jpg" : null;
}

/**
 * Get YouTube embed URL from a video URL
 */
function youTubeEmbed(?string $url): ?string {
    $id = extractYouTubeId($url);
    return $id ? "https://www.youtube.com/embed/{$id}" : null;
}

// ── Pagination Helper ─────────────────────────

/**
 * Calculate pagination values
 */
function paginate(int $totalItems, int $perPage = 12, int $currentPage = 1): array {
    $totalPages = max(1, (int) ceil($totalItems / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total'       => $totalItems,
        'per_page'    => $perPage,
        'current'     => $currentPage,
        'total_pages' => $totalPages,
        'offset'      => $offset,
        'has_prev'    => $currentPage > 1,
        'has_next'    => $currentPage < $totalPages,
    ];
}

// ── Site Settings Helper ──────────────────────

/**
 * Get a site setting value
 */
function getSetting(string $key, string $default = ''): string {
    $row = dbFetchOne("SELECT setting_value FROM site_settings WHERE setting_key = ?", [$key]);
    return $row ? ($row['setting_value'] ?? $default) : $default;
}

/**
 * Update a site setting value
 */
function setSetting(string $key, string $value): void {
    dbExecute(
        "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
        [$key, $value]
    );
}

// ── URL Helpers (Clean URLs) ──────────────

function recipeUrl(string $slug): string {
    return SITE_URL . '/recipe/' . $slug;
}

function postUrl(string $slug): string {
    return SITE_URL . '/blog/' . $slug;
}

function categoryUrl(string $slug): string {
    return SITE_URL . '/category/' . $slug;
}

function pageUrl(string $page): string {
    return SITE_URL . '/' . $page;
}

// ── Analytics Tracking ────────────────────

function trackPageView(string $pageType, ?int $pageId = null): void {
    try {
        dbInsert(
            "INSERT INTO page_views (page_type, page_id, visitor_ip, user_agent) VALUES (?, ?, ?, ?)",
            [$pageType, $pageId, $_SERVER['REMOTE_ADDR'] ?? '', mb_strimwidth($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)]
        );
    } catch (Exception $e) {
        // Silently fail — don't break the page for analytics
    }
}

// ── Recipe Ratings ────────────────────────

function getRecipeRating(int $recipeId): array {
    $row = dbFetchOne(
        "SELECT COUNT(*) as count, COALESCE(AVG(rating), 0) as average FROM recipe_ratings WHERE recipe_id = ?",
        [$recipeId]
    );
    return [
        'count'   => (int) $row['count'],
        'average' => round((float) $row['average'], 1),
    ];
}

function hasVisitorRated(int $recipeId): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return dbCount(
        "SELECT COUNT(*) FROM recipe_ratings WHERE recipe_id = ? AND visitor_ip = ?",
        [$recipeId, $ip]
    ) > 0;
}

// ── Allergen Helpers ──────────────────────

function getAllergenOptions(): array {
    return [
        'gluten'    => 'Gluten',
        'dairy'     => 'Dairy',
        'eggs'      => 'Eggs',
        'soy'       => 'Soy',
        'shellfish' => 'Shellfish',
        'fish'      => 'Fish',
        'peanuts'   => 'Peanuts',
        'tree_nuts' => 'Tree Nuts',
        'sesame'    => 'Sesame',
    ];
}

function getDietaryOptions(): array {
    return [
        'vegan'       => 'Vegan',
        'vegetarian'  => 'Vegetarian',
        'gluten_free' => 'Gluten-Free',
        'dairy_free'  => 'Dairy-Free',
        'keto'        => 'Keto',
        'halal'       => 'Halal',
    ];
}

function formatAllergens(?string $allergensJson): array {
    if (empty($allergensJson)) return [];
    $allergens = json_decode($allergensJson, true);
    return is_array($allergens) ? $allergens : [];
}

// ── Spice Level Helper ────────────────────

function spiceLevelIcon(string $level): string {
    $icons = [
        'None'      => '',
        'Mild'      => '🌶️',
        'Medium'    => '🌶️🌶️',
        'Hot'       => '🌶️🌶️🌶️',
        'Extra Hot' => '🌶️🌶️🌶️🌶️',
    ];
    return $icons[$level] ?? '';
}
