<?php
/**
 * Admin — Manage Recipes (List View)
 */
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Manage Recipes';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (csrfValidate()) {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            dbExecute("DELETE FROM recipes WHERE id = ?", [$id]);
            setFlash('success', 'Recipe deleted successfully.');
        }
    } else {
        setFlash('error', 'Invalid request.');
    }
    header('Location: ' . SITE_URL . '/admin/recipes.php');
    exit;
}

// Handle publish/unpublish toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_publish') {
    if (csrfValidate()) {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            dbExecute("UPDATE recipes SET is_published = NOT is_published WHERE id = ?", [$id]);
            setFlash('success', 'Recipe status updated.');
        }
    }
    header('Location: ' . SITE_URL . '/admin/recipes.php');
    exit;
}

// Fetch recipes with category info
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$total = dbCount("SELECT COUNT(*) FROM recipes");
$pagination = paginate($total, $perPage, $page);

$recipes = dbFetchAll(
    "SELECT r.*, c.name as category_name 
     FROM recipes r 
     LEFT JOIN categories c ON r.category_id = c.id 
     ORDER BY r.created_at DESC 
     LIMIT ? OFFSET ?",
    [$perPage, $pagination['offset']]
);

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Recipes</h1>
    <a href="<?= SITE_URL ?>/admin/recipe-edit.php" class="btn btn-primary">+ New Recipe</a>
</div>

<?php if (empty($recipes)): ?>
    <div class="empty-state-large">
        <span class="empty-icon">🍳</span>
        <h2>No recipes yet</h2>
        <p>Start by adding your first recipe!</p>
        <a href="<?= SITE_URL ?>/admin/recipe-edit.php" class="btn btn-primary">+ Add Recipe</a>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Difficulty</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="th-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recipes as $recipe): ?>
                    <tr>
                        <td>
                            <div class="table-title-cell">
                                <?php 
                                    $thumb = $recipe['thumbnail_url'] ?: youTubeThumbnail($recipe['youtube_url']);
                                    if ($thumb):
                                ?>
                                    <img src="<?= h($thumb) ?>" alt="" class="table-thumb">
                                <?php endif; ?>
                                <div>
                                    <a href="<?= SITE_URL ?>/admin/recipe-edit.php?id=<?= $recipe['id'] ?>" class="table-title-link">
                                        <?= h($recipe['title']) ?>
                                    </a>
                                    <?php if ($recipe['is_featured']): ?>
                                        <span class="badge badge-featured">★ Featured</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?= hs($recipe['category_name'] ?? '—') ?></td>
                        <td><?= h($recipe['difficulty']) ?></td>
                        <td>
                            <form method="POST" class="inline-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle_publish">
                                <input type="hidden" name="id" value="<?= $recipe['id'] ?>">
                                <button type="submit" class="status-badge <?= $recipe['is_published'] ? 'status-published' : 'status-draft' ?>">
                                    <?= $recipe['is_published'] ? 'Published' : 'Draft' ?>
                                </button>
                            </form>
                        </td>
                        <td><?= date('M j, Y', strtotime($recipe['created_at'])) ?></td>
                        <td class="td-actions">
                            <a href="<?= SITE_URL ?>/admin/recipe-edit.php?id=<?= $recipe['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                            <form method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this recipe?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $recipe['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="pagination">
            <?php if ($pagination['has_prev']): ?>
                <a href="?page=<?= $pagination['current'] - 1 ?>" class="btn btn-sm btn-outline">← Previous</a>
            <?php endif; ?>
            
            <span class="pagination-info">
                Page <?= $pagination['current'] ?> of <?= $pagination['total_pages'] ?>
            </span>
            
            <?php if ($pagination['has_next']): ?>
                <a href="?page=<?= $pagination['current'] + 1 ?>" class="btn btn-sm btn-outline">Next →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
