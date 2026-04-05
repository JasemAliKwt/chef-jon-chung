<?php
/**
 * Admin — Manage Blog Posts (List View)
 */
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Manage Blog Posts';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (csrfValidate()) {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            dbExecute("DELETE FROM blog_posts WHERE id = ?", [$id]);
            setFlash('success', 'Blog post deleted.');
        }
    }
    header('Location: ' . SITE_URL . '/admin/blog-posts.php');
    exit;
}

// Handle publish toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_publish') {
    if (csrfValidate()) {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            dbExecute("UPDATE blog_posts SET is_published = NOT is_published WHERE id = ?", [$id]);
            setFlash('success', 'Post status updated.');
        }
    }
    header('Location: ' . SITE_URL . '/admin/blog-posts.php');
    exit;
}

// Fetch posts
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$total = dbCount("SELECT COUNT(*) FROM blog_posts");
$pagination = paginate($total, $perPage, $page);

$posts = dbFetchAll(
    "SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT ? OFFSET ?",
    [$perPage, $pagination['offset']]
);

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Blog Posts</h1>
    <a href="<?= SITE_URL ?>/admin/blog-edit.php" class="btn btn-primary">+ New Post</a>
</div>

<?php if (empty($posts)): ?>
    <div class="empty-state-large">
        <span class="empty-icon">📝</span>
        <h2>No blog posts yet</h2>
        <p>Share cooking tips, stories, and more!</p>
        <a href="<?= SITE_URL ?>/admin/blog-edit.php" class="btn btn-primary">+ Write a Post</a>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="th-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td>
                            <a href="<?= SITE_URL ?>/admin/blog-edit.php?id=<?= $post['id'] ?>" class="table-title-link">
                                <?= h($post['title']) ?>
                            </a>
                            <?php if ($post['excerpt']): ?>
                                <span class="panel-item-meta"><?= h(mb_strimwidth($post['excerpt'], 0, 80, '...')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" class="inline-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle_publish">
                                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                <button type="submit" class="status-badge <?= $post['is_published'] ? 'status-published' : 'status-draft' ?>">
                                    <?= $post['is_published'] ? 'Published' : 'Draft' ?>
                                </button>
                            </form>
                        </td>
                        <td><?= date('M j, Y', strtotime($post['created_at'])) ?></td>
                        <td class="td-actions">
                            <a href="<?= SITE_URL ?>/admin/blog-edit.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                            <form method="POST" class="inline-form" onsubmit="return confirm('Delete this blog post?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="pagination">
            <?php if ($pagination['has_prev']): ?>
                <a href="?page=<?= $pagination['current'] - 1 ?>" class="btn btn-sm btn-outline">← Previous</a>
            <?php endif; ?>
            <span class="pagination-info">Page <?= $pagination['current'] ?> of <?= $pagination['total_pages'] ?></span>
            <?php if ($pagination['has_next']): ?>
                <a href="?page=<?= $pagination['current'] + 1 ?>" class="btn btn-sm btn-outline">Next →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
