<?php
/**
 * Admin — Messages (Contact Form Inbox)
 */
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Messages';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfValidate()) {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        switch ($action) {
            case 'mark_read':
                dbExecute("UPDATE contact_messages SET is_read = 1 WHERE id = ?", [$id]);
                break;

            case 'mark_unread':
                dbExecute("UPDATE contact_messages SET is_read = 0 WHERE id = ?", [$id]);
                break;

            case 'delete':
                dbExecute("DELETE FROM contact_messages WHERE id = ?", [$id]);
                setFlash('success', 'Message deleted.');
                break;
        }
    }

    // Mark all as read
    if ($action === 'mark_all_read') {
        dbExecute("UPDATE contact_messages SET is_read = 1 WHERE is_read = 0");
        setFlash('success', 'All messages marked as read.');
    }

    header('Location: ' . SITE_URL . '/admin/messages.php');
    exit;
}

// Fetch messages
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$filter = $_GET['filter'] ?? 'all';

$whereClause = '';
if ($filter === 'unread') {
    $whereClause = 'WHERE is_read = 0';
} elseif ($filter === 'read') {
    $whereClause = 'WHERE is_read = 1';
}

$total = dbCount("SELECT COUNT(*) FROM contact_messages {$whereClause}");
$pagination = paginate($total, $perPage, $page);

$messages = dbFetchAll(
    "SELECT * FROM contact_messages {$whereClause}
     ORDER BY created_at DESC
     LIMIT ? OFFSET ?",
    [$perPage, $pagination['offset']]
);

$unreadCount = dbCount("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");

// Viewing a single message?
$viewMessage = null;
if (isset($_GET['view'])) {
    $viewMessage = dbFetchOne(
        "SELECT * FROM contact_messages WHERE id = ?",
        [(int) $_GET['view']]
    );
    if ($viewMessage && !$viewMessage['is_read']) {
        dbExecute("UPDATE contact_messages SET is_read = 1 WHERE id = ?", [$viewMessage['id']]);
        $viewMessage['is_read'] = 1;
    }
}

include __DIR__ . '/../includes/admin-header.php';
?>

<?php if ($viewMessage): ?>
    <!-- ─── Single Message View ────────────── -->
    <div class="page-header">
        <div class="page-header-left">
            <a href="<?= SITE_URL ?>/admin/messages.php" class="back-link">← Back to Messages</a>
            <h1>Message from <?= h($viewMessage['sender_name']) ?></h1>
        </div>
        <div>
            <form method="POST" class="inline-form"
                  onsubmit="return confirm('Delete this message?')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $viewMessage['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
        </div>
    </div>

    <div class="form-card message-detail">
        <div class="message-detail-header">
            <div>
                <strong><?= h($viewMessage['sender_name']) ?></strong>
                <span class="message-email">
                    &lt;<a href="mailto:<?= h($viewMessage['sender_email']) ?>"><?= h($viewMessage['sender_email']) ?></a>&gt;
                </span>
            </div>
            <span class="message-date">
                <?= date('F j, Y \a\t g:ia', strtotime($viewMessage['created_at'])) ?>
            </span>
        </div>
        <div class="message-body">
            <?= nl2br(h($viewMessage['message'])) ?>
        </div>
        <div class="message-reply">
            <a href="https://mail.google.com/mail/?view=cm&to=<?= urlencode($viewMessage['sender_email']) ?>&su=<?= urlencode('Re: Message from ' . SITE_NAME) ?>"
               class="btn btn-primary" target="_blank" rel="noopener">Reply via Gmail</a>
        </div>
    </div>

<?php else: ?>
    <!-- ─── Message List View ──────────────── -->
    <div class="page-header">
        <h1>Messages</h1>
        <?php if ($unreadCount > 0): ?>
            <form method="POST" class="inline-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn btn-outline">Mark All Read</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">
            All (<?= dbCount("SELECT COUNT(*) FROM contact_messages") ?>)
        </a>
        <a href="?filter=unread" class="filter-tab <?= $filter === 'unread' ? 'active' : '' ?>">
            Unread (<?= $unreadCount ?>)
        </a>
        <a href="?filter=read" class="filter-tab <?= $filter === 'read' ? 'active' : '' ?>">
            Read
        </a>
    </div>

    <?php if (empty($messages)): ?>
        <div class="empty-state-large">
            <span class="empty-icon">💬</span>
            <h2>No messages<?= $filter !== 'all' ? ' (' . $filter . ')' : '' ?></h2>
            <p>Messages from your contact form will appear here.</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="admin-table messages-table">
                <thead>
                    <tr>
                        <th style="width: 30%;">From</th>
                        <th>Message</th>
                        <th style="width: 120px;">Date</th>
                        <th class="th-actions" style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                        <tr class="<?= !$msg['is_read'] ? 'row-unread' : '' ?>">
                            <td>
                                <a href="<?= SITE_URL ?>/admin/messages.php?view=<?= $msg['id'] ?>"
                                   class="table-title-link">
                                    <?= !$msg['is_read'] ? '<strong>' : '' ?>
                                    <?= h($msg['sender_name']) ?>
                                    <?= !$msg['is_read'] ? '</strong>' : '' ?>
                                </a>
                                <span class="panel-item-meta"><?= h($msg['sender_email']) ?></span>
                            </td>
                            <td>
                                <a href="<?= SITE_URL ?>/admin/messages.php?view=<?= $msg['id'] ?>"
                                   class="message-preview-link">
                                    <?= h(mb_strimwidth($msg['message'], 0, 100, '...')) ?>
                                </a>
                            </td>
                            <td><?= date('M j, Y', strtotime($msg['created_at'])) ?></td>
                            <td class="td-actions">
                                <form method="POST" class="inline-form"
                                      onsubmit="return confirm('Delete this message?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $msg['id'] ?>">
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
                    <a href="?filter=<?= h($filter) ?>&page=<?= $pagination['current'] - 1 ?>"
                       class="btn btn-sm btn-outline">← Previous</a>
                <?php endif; ?>
                <span class="pagination-info">
                    Page <?= $pagination['current'] ?> of <?= $pagination['total_pages'] ?>
                </span>
                <?php if ($pagination['has_next']): ?>
                    <a href="?filter=<?= h($filter) ?>&page=<?= $pagination['current'] + 1 ?>"
                       class="btn btn-sm btn-outline">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
