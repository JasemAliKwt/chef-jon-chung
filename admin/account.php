<?php
/**
 * Admin — My Account
 *
 * Change password and display name.
 */
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'My Account';

$errors = [];
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValidate()) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $displayName = trim($_POST['display_name'] ?? '');
            if (empty($displayName)) {
                $errors[] = 'Display name cannot be empty.';
            } else {
                dbExecute(
                    "UPDATE users SET display_name = ? WHERE id = ?",
                    [$displayName, $userId]
                );
                $_SESSION['display_name'] = $displayName;
                setFlash('success', 'Profile updated!');
                header('Location: ' . SITE_URL . '/admin/account.php');
                exit;
            }
        }

        if ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword     = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword)) {
                $errors[] = 'Please enter your current password.';
            } elseif (!verifyCurrentPassword($userId, $currentPassword)) {
                $errors[] = 'Current password is incorrect.';
            }

            if (empty($newPassword)) {
                $errors[] = 'Please enter a new password.';
            } elseif (strlen($newPassword) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'New passwords don\'t match.';
            }

            if (empty($errors)) {
                updatePassword($userId, $newPassword);
                setFlash('success', 'Password changed successfully!');
                header('Location: ' . SITE_URL . '/admin/account.php');
                exit;
            }
        }
    }
}

// Load current user info
$user = dbFetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="page-header">
    <h1>My Account</h1>
</div>

<?php if (!empty($errors)): ?>
    <div class="flash-message flash-error">
        <ul class="error-list">
            <?php foreach ($errors as $err): ?>
                <li><?= h($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="settings-layout">
    <!-- Profile -->
    <div class="form-card">
        <h2 class="form-card-title">Profile</h2>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_profile">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" value="<?= h($user['username']) ?>" disabled>
                <span class="form-hint">Username cannot be changed.</span>
            </div>

            <div class="form-group">
                <label for="display_name">Display Name</label>
                <input type="text" id="display_name" name="display_name"
                       value="<?= h($user['display_name']) ?>"
                       placeholder="Chef Jon" required>
                <span class="form-hint">This name appears in the admin panel greeting.</span>
            </div>

            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="form-card">
        <h2 class="form-card-title">Change Password</h2>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="change_password">

            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password"
                       autocomplete="current-password" required>
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password"
                       autocomplete="new-password" required minlength="8">
                <span class="form-hint">Minimum 8 characters.</span>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password"
                       autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>

    <!-- Account Info -->
    <div class="form-card">
        <h2 class="form-card-title">Account Info</h2>
        <p class="form-meta">Account created: <?= date('F j, Y', strtotime($user['created_at'])) ?></p>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
