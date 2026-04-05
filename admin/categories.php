<?php
/**
 * Admin — Manage Categories
 */
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Categories';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfValidate()) {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $name = trim($_POST['name'] ?? '');
            if (!empty($name)) {
                $slug = uniqueSlug('categories', $name);
                $maxOrder = dbFetchOne("SELECT MAX(sort_order) as mx FROM categories");
                $nextOrder = ($maxOrder['mx'] ?? 0) + 1;
                dbInsert(
                    "INSERT INTO categories (name, slug, sort_order) VALUES (?, ?, ?)",
                    [$name, $slug, $nextOrder]
                );
                setFlash('success', 'Category added!');
            } else {
                setFlash('error', 'Category name cannot be empty.');
            }
            break;

        case 'edit':
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            if ($id > 0 && !empty($name)) {
                $slug = uniqueSlug('categories', $name, $id);
                dbExecute(
                    "UPDATE categories SET name = ?, slug = ? WHERE id = ?",
                    [$name, $slug, $id]
                );
                setFlash('success', 'Category updated!');
            }
            break;

        case 'delete':
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                // Check if any recipes use this category
                $recipeCount = dbCount(
                    "SELECT COUNT(*) FROM recipes WHERE category_id = ?", [$id]
                );
                if ($recipeCount > 0) {
                    setFlash('error', "Cannot delete — {$recipeCount} recipe(s) use this category. Reassign them first.");
                } else {
                    dbExecute("DELETE FROM categories WHERE id = ?", [$id]);
                    setFlash('success', 'Category deleted.');
                }
            }
            break;

        case 'reorder':
            $order = $_POST['order'] ?? [];
            if (is_array($order)) {
                foreach ($order as $position => $id) {
                    dbExecute(
                        "UPDATE categories SET sort_order = ? WHERE id = ?",
                        [(int) $position + 1, (int) $id]
                    );
                }
                setFlash('success', 'Order updated!');
            }
            break;
    }

    header('Location: ' . SITE_URL . '/admin/categories.php');
    exit;
}

// Fetch categories with recipe counts
$categories = dbFetchAll(
    "SELECT c.*, COUNT(r.id) as recipe_count
     FROM categories c
     LEFT JOIN recipes r ON r.category_id = c.id
     GROUP BY c.id
     ORDER BY c.sort_order, c.name"
);

// Editing a specific category?
$editCat = null;
if (isset($_GET['edit'])) {
    $editCat = dbFetchOne("SELECT * FROM categories WHERE id = ?", [(int) $_GET['edit']]);
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Categories</h1>
</div>

<div class="categories-layout">
    <!-- Add / Edit Form -->
    <div class="form-card categories-form-card">
        <h2 class="form-card-title"><?= $editCat ? 'Edit Category' : 'Add Category' ?></h2>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= $editCat ? 'edit' : 'add' ?>">
            <?php if ($editCat): ?>
                <input type="hidden" name="id" value="<?= $editCat['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="name">Category Name</label>
                <input type="text" id="name" name="name"
                       value="<?= h($editCat['name'] ?? '') ?>"
                       placeholder="e.g., Korean, Fusion, Desserts"
                       required autofocus>
            </div>

            <div class="form-actions-inline">
                <button type="submit" class="btn btn-primary">
                    <?= $editCat ? 'Update' : 'Add Category' ?>
                </button>
                <?php if ($editCat): ?>
                    <a href="<?= SITE_URL ?>/admin/categories.php" class="btn btn-outline">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Category List -->
    <div class="form-card categories-list-card">
        <h2 class="form-card-title">All Categories (<?= count($categories) ?>)</h2>

        <?php if (empty($categories)): ?>
            <div class="empty-state">
                <p>No categories yet. Add one using the form.</p>
            </div>
        <?php else: ?>
            <div class="category-list">
                <?php foreach ($categories as $cat): ?>
                    <div class="category-item">
                        <div class="category-info">
                            <span class="category-name"><?= h($cat['name']) ?></span>
                            <span class="category-meta">
                                <?= $cat['recipe_count'] ?> recipe<?= $cat['recipe_count'] !== 1 ? 's' : '' ?>
                                · slug: <?= h($cat['slug']) ?>
                            </span>
                        </div>
                        <div class="category-actions">
                            <a href="<?= SITE_URL ?>/admin/categories.php?edit=<?= $cat['id'] ?>"
                               class="btn btn-sm btn-outline">Edit</a>
                            <form method="POST" class="inline-form"
                                  onsubmit="return confirm('Delete the &quot;<?= h($cat['name']) ?>&quot; category?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
