<?php
/**
 * Admin — Newsletter Subscribers
 */
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Subscribers';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfValidate()) {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        switch ($action) {
            case 'deactivate':
                dbExecute("UPDATE newsletter_subscribers SET is_active = 0 WHERE id = ?", [$id]);
                setFlash('success', 'Subscriber deactivated.');
                break;
            case 'activate':
                dbExecute("UPDATE newsletter_subscribers SET is_active = 1 WHERE id = ?", [$id]);
                setFlash('success', 'Subscriber reactivated.');
                break;
            case 'delete':
                dbExecute("DELETE FROM newsletter_subscribers WHERE id = ?", [$id]);
                setFlash('success', 'Subscriber deleted.');
                break;
        }
    }

    // Export CSV
    if ($action === 'export_csv') {
        $subs = dbFetchAll("SELECT name, email, is_active, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC");
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="subscribers-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Name', 'Email', 'Active', 'Subscribed']);
        foreach ($subs as $s) {
            fputcsv($out, [$s['name'], $s['email'], $s['is_active'] ? 'Yes' : 'No', $s['subscribed_at']]);
        }
        fclose($out);
        exit;
    }

    header('Location: ' . SITE_URL . '/admin/subscribers.php');
    exit;
}

// Fetch subscribers
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$filter = $_GET['filter'] ?? 'active';

$whereClause = '';
if ($filter === 'active') {
    $whereClause = 'WHERE is_active = 1';
} elseif ($filter === 'inactive') {
    $whereClause = 'WHERE is_active = 0';
}

$total = dbCount("SELECT COUNT(*) FROM newsletter_subscribers {$whereClause}");
$pagination = paginate($total, $perPage, $page);

$subscribers = dbFetchAll(
    "SELECT * FROM newsletter_subscribers {$whereClause}
     ORDER BY subscribed_at DESC LIMIT ? OFFSET ?",
    [$perPage, $pagination['offset']]
);

$activeCount = dbCount("SELECT COUNT(*) FROM newsletter_subscribers WHERE is_active = 1");
$inactiveCount = dbCount("SELECT COUNT(*) FROM newsletter_subscribers WHERE is_active = 0");
$totalCount = $activeCount + $inactiveCount;

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Subscribers</h1>
    <div class="page-header-actions">
        <form method="POST" class="inline-form">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="export_csv">
            <button type="submit" class="btn btn-sm btn-outline">Export CSV</button>
        </form>
    </div>
</div>

<!-- Filter Tabs -->
<div class="filter-tabs">
    <a href="?filter=active" class="filter-tab <?= $filter === 'active' ? 'active' : '' ?>">
        Active (<?= $activeCount ?>)
    </a>
    <a href="?filter=inactive" class="filter-tab <?= $filter === 'inactive' ? 'active' : '' ?>">
        Inactive (<?= $inactiveCount ?>)
    </a>
    <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">
        All (<?= $totalCount ?>)
    </a>
</div>

<?php if (empty($subscribers)): ?>
    <div class="empty-state-large">
        <span class="empty-icon">—</span>
        <h2>No subscribers<?= $filter !== 'all' ? ' (' . $filter . ')' : '' ?></h2>
        <p>Newsletter signups from the site footer will appear here.</p>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Subscribed</th>
                    <th class="th-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscribers as $sub): ?>
                    <tr>
                        <td><strong><?= h($sub['name'] ?: '—') ?></strong></td>
                        <td><?= h($sub['email']) ?></td>
                        <td>
                            <span class="status-badge <?= $sub['is_active'] ? 'status-published' : 'status-draft' ?>">
                                <?= $sub['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td><?= date('M j, Y', strtotime($sub['subscribed_at'])) ?></td>
                        <td class="td-actions">
                            <?php if ($sub['is_active']): ?>
                                <form method="POST" class="inline-form">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline">Deactivate</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" class="inline-form">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="activate">
                                    <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline">Activate</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" class="inline-form" onsubmit="return confirm('Delete this subscriber?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $sub['id'] ?>">
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
                <a href="?filter=<?= h($filter) ?>&page=<?= $pagination['current'] - 1 ?>" class="btn btn-sm btn-outline">← Previous</a>
            <?php endif; ?>
            <span class="pagination-info">Page <?= $pagination['current'] ?> of <?= $pagination['total_pages'] ?></span>
            <?php if ($pagination['has_next']): ?>
                <a href="?filter=<?= h($filter) ?>&page=<?= $pagination['current'] + 1 ?>" class="btn btn-sm btn-outline">Next →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
