<?php
/**
 * Admin — Add / Edit Blog Post
 */
require_once __DIR__ . '/../includes/auth.php';

$editId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $editId !== null;
$pageTitle = $isEdit ? 'Edit Blog Post' : 'New Blog Post';

// Load existing post if editing
$post = null;
if ($isEdit) {
    $post = dbFetchOne("SELECT * FROM blog_posts WHERE id = ?", [$editId]);
    if (!$post) {
        setFlash('error', 'Blog post not found.');
        header('Location: ' . SITE_URL . '/admin/blog-posts.php');
        exit;
    }
}

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValidate()) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $title       = trim($_POST['title'] ?? '');
        $excerpt     = trim($_POST['excerpt'] ?? '');
        $body        = trim($_POST['body'] ?? '');
        $youtube_url = trim($_POST['youtube_url'] ?? '');
        $is_published = isset($_POST['is_published']) ? 1 : 0;

        // Handle thumbnail upload
        $thumbnail_url = $post['thumbnail_url'] ?? '';
        if (!empty($_FILES['thumbnail']['tmp_name']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = mime_content_type($_FILES['thumbnail']['tmp_name']);

            if (!in_array($fileType, $allowed)) {
                $errors[] = 'Thumbnail must be a JPG, PNG, or WebP image.';
            } elseif ($_FILES['thumbnail']['size'] > MAX_UPLOAD_SIZE) {
                $errors[] = 'Thumbnail must be under 5MB.';
            } else {
                $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
                $filename = 'blog-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destPath = UPLOADS_DIR . $filename;

                if (!is_dir(UPLOADS_DIR)) {
                    mkdir(UPLOADS_DIR, 0755, true);
                }

                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $destPath)) {
                    $thumbnail_url = UPLOADS_URL . $filename;
                } else {
                    $errors[] = 'Failed to upload thumbnail.';
                }
            }
        } elseif (empty($thumbnail_url) && !empty($youtube_url)) {
            $thumbnail_url = youTubeThumbnail($youtube_url) ?? '';
        }

        // Validation
        if (empty($title)) {
            $errors[] = 'Post title is required.';
        }
        if (empty($body)) {
            $errors[] = 'Post body cannot be empty.';
        }

        if (empty($errors)) {
            $slug = uniqueSlug('blog_posts', $title, $editId);

            if ($isEdit) {
                dbExecute(
                    "UPDATE blog_posts SET
                        title = ?, slug = ?, excerpt = ?, body = ?,
                        thumbnail_url = ?, youtube_url = ?, is_published = ?
                     WHERE id = ?",
                    [$title, $slug, $excerpt, $body, $thumbnail_url, $youtube_url, $is_published, $editId]
                );
                setFlash('success', 'Blog post updated!');
                header('Location: ' . SITE_URL . '/admin/blog-edit.php?id=' . $editId);
                exit;
            } else {
                $newId = dbInsert(
                    "INSERT INTO blog_posts
                        (title, slug, excerpt, body, thumbnail_url, youtube_url, is_published)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$title, $slug, $excerpt, $body, $thumbnail_url, $youtube_url, $is_published]
                );
                setFlash('success', 'Blog post created!');
                header('Location: ' . SITE_URL . '/admin/blog-edit.php?id=' . $newId);
                exit;
            }
        }
    }
}

// Form values
$form = [
    'title'         => $_POST['title']         ?? ($post['title'] ?? ''),
    'excerpt'       => $_POST['excerpt']       ?? ($post['excerpt'] ?? ''),
    'body'          => $_POST['body']          ?? ($post['body'] ?? ''),
    'youtube_url'   => $_POST['youtube_url']   ?? ($post['youtube_url'] ?? ''),
    'is_published'  => isset($_POST['is_published']) ? 1 : ($post['is_published'] ?? 1),
    'thumbnail_url' => $post['thumbnail_url'] ?? '',
];

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <a href="<?= SITE_URL ?>/admin/blog-posts.php" class="back-link">← Back to Blog Posts</a>
        <h1><?= h($pageTitle) ?></h1>
    </div>
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

<form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>

    <div class="form-layout">
        <!-- Main Column -->
        <div class="form-main">
            <div class="form-card">
                <div class="form-group">
                    <label for="title">Post Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" value="<?= h($form['title']) ?>"
                           placeholder="e.g., 5 Tips for Perfect Rice" required>
                </div>

                <div class="form-group">
                    <label for="excerpt">Excerpt</label>
                    <textarea id="excerpt" name="excerpt" rows="2"
                              placeholder="A short preview shown on the blog listing..."><?= h($form['excerpt']) ?></textarea>
                    <span class="form-hint">If left empty, the first part of the body will be used.</span>
                </div>

                <div class="form-group">
                    <label for="body">Body <span class="required">*</span></label>
                    <textarea id="body" name="body" rows="18"
                              placeholder="Write your blog post here...&#10;&#10;You can use line breaks to create paragraphs."><?= h($form['body']) ?></textarea>
                    <span class="form-hint">Plain text. Blank lines create paragraph breaks.</span>
                </div>

                <div class="form-group">
                    <label for="youtube_url">YouTube Video (optional)</label>
                    <input type="url" id="youtube_url" name="youtube_url" value="<?= h($form['youtube_url']) ?>"
                           placeholder="https://www.youtube.com/watch?v=...">
                    <span class="form-hint">Embed a video at the top of the post.</span>

                    <?php
                        $embedUrl = youTubeEmbed($form['youtube_url']);
                        if ($embedUrl):
                    ?>
                        <div class="video-preview">
                            <iframe src="<?= h($embedUrl) ?>" frameborder="0" allowfullscreen></iframe>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar Column -->
        <div class="form-sidebar">
            <div class="form-card">
                <h2 class="form-card-title">Publish</h2>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_published" value="1"
                               <?= $form['is_published'] ? 'checked' : '' ?>>
                        Published (visible on site)
                    </label>
                </div>
                <div class="form-actions-card">
                    <button type="submit" class="btn btn-primary btn-full">
                        <?= $isEdit ? 'Update Post' : 'Create Post' ?>
                    </button>
                </div>
            </div>

            <div class="form-card">
                <h2 class="form-card-title">Featured Image</h2>
                <?php if (!empty($form['thumbnail_url'])): ?>
                    <div class="current-thumb">
                        <img src="<?= h($form['thumbnail_url']) ?>" alt="Current thumbnail">
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="thumbnail">Upload Image</label>
                    <input type="file" id="thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/webp">
                    <span class="form-hint">JPG, PNG, or WebP. Max 5MB.</span>
                </div>
            </div>

            <?php if ($isEdit && $post): ?>
                <div class="form-card">
                    <h2 class="form-card-title">Info</h2>
                    <p class="form-meta">Created: <?= date('M j, Y g:ia', strtotime($post['created_at'])) ?></p>
                    <p class="form-meta">Updated: <?= date('M j, Y g:ia', strtotime($post['updated_at'])) ?></p>
                    <p class="form-meta">Slug: <code><?= h($post['slug']) ?></code></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
