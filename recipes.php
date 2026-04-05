<?php
/**
 * Recipes Listing Page
 */
require_once __DIR__ . '/includes/db.php';

// Category filter
$categorySlug = $_GET['category'] ?? null;
$activeCategory = null;

if ($categorySlug) {
    $activeCategory = dbFetchOne("SELECT * FROM categories WHERE slug = ?", [$categorySlug]);
}

$pageTitle = $activeCategory ? h($activeCategory['name']) . ' Recipes' : 'Recipes';
$pageDescription = $activeCategory
    ? "Browse our {$activeCategory['name']} recipes"
    : 'Explore all our cooking recipes';

// Build query
$whereClause = "WHERE r.is_published = 1";
$params = [];

if ($activeCategory) {
    $whereClause .= " AND r.category_id = ?";
    $params[] = $activeCategory['id'];
}

// Search
$search = trim($_GET['q'] ?? '');
if (!empty($search)) {
    $whereClause .= " AND (r.title LIKE ? OR r.description LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$total = dbCount("SELECT COUNT(*) FROM recipes r {$whereClause}", $params);
$pagination = paginate($total, $perPage, $page);

$paginationParams = array_merge($params, [$perPage, $pagination['offset']]);
$recipes = dbFetchAll(
    "SELECT r.*, c.name as category_name
     FROM recipes r
     LEFT JOIN categories c ON r.category_id = c.id
     {$whereClause}
     ORDER BY r.created_at DESC
     LIMIT ? OFFSET ?",
    $paginationParams
);

// All categories for filter
$categories = dbFetchAll(
    "SELECT c.*, COUNT(r.id) as recipe_count
     FROM categories c
     INNER JOIN recipes r ON r.category_id = c.id AND r.is_published = 1
     GROUP BY c.id
     ORDER BY c.sort_order"
);

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero-sm">
    <div class="container">
        <h1 class="page-hero-title"><?= h($pageTitle) ?></h1>
        <?php if ($activeCategory): ?>
            <p class="page-hero-sub">
                <a href="<?= pageUrl('recipes') ?>">All Recipes</a> → <?= h($activeCategory['name']) ?>
            </p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <!-- Filters -->
        <div class="recipes-toolbar">
            <div class="category-pills">
                <a href="<?= pageUrl('recipes') ?>"
                   class="category-pill <?= !$activeCategory ? 'active' : '' ?>">All</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="<?= categoryUrl($cat['slug']) ?>"
                       class="category-pill <?= $activeCategory && $activeCategory['id'] === $cat['id'] ? 'active' : '' ?>">
                        <?= h($cat['name']) ?>
                        <span class="pill-count"><?= $cat['recipe_count'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <form class="search-form" method="GET" action="<?= pageUrl('recipes') ?>">
                <?php if ($activeCategory): ?>
                    <input type="hidden" name="category" value="<?= h($activeCategory['slug']) ?>">
                <?php endif; ?>
                <input type="text" name="q" value="<?= h($search) ?>"
                       placeholder="Search recipes..." class="search-input">
                <button type="submit" class="search-btn">Search</button>
            </form>
        </div>

        <?php if (!empty($search)): ?>
            <p class="search-results-info">
                <?= $total ?> result<?= $total !== 1 ? 's' : '' ?> for "<?= h($search) ?>"
                · <a href="<?= pageUrl('recipes') ?><?= $activeCategory ? '?category=' . h($activeCategory['slug']) : '' ?>">Clear search</a>
            </p>
        <?php endif; ?>

        <?php if (empty($recipes)): ?>
            <div class="empty-public">
                <span>🍳</span>
                <h2>No recipes found</h2>
                <p>Check back soon — new recipes are on the way!</p>
            </div>
        <?php else: ?>
            <div class="recipe-grid">
                <?php foreach ($recipes as $recipe): ?>
                    <?php include __DIR__ . '/includes/recipe-card.php'; ?>
                <?php endforeach; ?>
            </div>

            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="pagination-public">
                    <?php
                        $baseUrl = pageUrl('recipes') . '?';
                        if ($activeCategory) $baseUrl .= 'category=' . urlencode($activeCategory['slug']) . '&';
                        if ($search) $baseUrl .= 'q=' . urlencode($search) . '&';
                    ?>
                    <?php if ($pagination['has_prev']): ?>
                        <a href="<?= $baseUrl ?>page=<?= $pagination['current'] - 1 ?>" class="btn btn-outline">← Previous</a>
                    <?php endif; ?>
                    <span class="pagination-info">Page <?= $pagination['current'] ?> of <?= $pagination['total_pages'] ?></span>
                    <?php if ($pagination['has_next']): ?>
                        <a href="<?= $baseUrl ?>page=<?= $pagination['current'] + 1 ?>" class="btn btn-outline">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
