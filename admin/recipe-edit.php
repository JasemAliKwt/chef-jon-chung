<?php
/**
 * Admin — Add / Edit Recipe
 * 
 * Handles both creating new recipes and editing existing ones.
 * If ?id=X is present, we're editing. Otherwise, creating.
 */
require_once __DIR__ . '/../includes/auth.php';

$editId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $editId !== null;
$pageTitle = $isEdit ? 'Edit Recipe' : 'Add Recipe';

// Load existing recipe data if editing
$recipe = null;
if ($isEdit) {
    $recipe = dbFetchOne("SELECT * FROM recipes WHERE id = ?", [$editId]);
    if (!$recipe) {
        setFlash('error', 'Recipe not found.');
        header('Location: ' . SITE_URL . '/admin/recipes.php');
        exit;
    }
}

// Load categories for the dropdown
$categories = dbFetchAll("SELECT * FROM categories ORDER BY sort_order, name");

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValidate()) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Gather form data
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $youtube_url = trim($_POST['youtube_url'] ?? '');
        $category_id = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
        $cook_time   = !empty($_POST['cook_time_minutes']) ? (int) $_POST['cook_time_minutes'] : null;
        $servings    = !empty($_POST['servings']) ? (int) $_POST['servings'] : null;
        $difficulty  = in_array($_POST['difficulty'] ?? '', ['Easy', 'Medium', 'Hard']) 
                       ? $_POST['difficulty'] : 'Easy';
        $is_featured  = isset($_POST['is_featured']) ? 1 : 0;
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        
        // Process ingredients (one per line)
        $ingredientsRaw = trim($_POST['ingredients'] ?? '');
        $ingredients = array_values(array_filter(
            array_map('trim', explode("\n", $ingredientsRaw))
        ));
        
        // Process steps (one per line)
        $stepsRaw = trim($_POST['steps'] ?? '');
        $steps = array_values(array_filter(
            array_map('trim', explode("\n", $stepsRaw))
        ));
        
        // Handle thumbnail upload or auto-pull from YouTube
        $thumbnail_url = $recipe['thumbnail_url'] ?? '';
        if (!empty($_FILES['thumbnail']['tmp_name']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = mime_content_type($_FILES['thumbnail']['tmp_name']);
            
            if (!in_array($fileType, $allowed)) {
                $errors[] = 'Thumbnail must be a JPG, PNG, or WebP image.';
            } elseif ($_FILES['thumbnail']['size'] > MAX_UPLOAD_SIZE) {
                $errors[] = 'Thumbnail must be under 5MB.';
            } else {
                $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
                $filename = 'recipe-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
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
            // Auto-pull YouTube thumbnail
            $thumbnail_url = youTubeThumbnail($youtube_url) ?? '';
        }
        
        // Validation
        if (empty($title)) {
            $errors[] = 'Recipe title is required.';
        }
        
        if (empty($errors)) {
            $slug = uniqueSlug('recipes', $title, $editId);
            $ingredientsJson = json_encode($ingredients);
            $stepsJson = json_encode($steps);
            
            if ($isEdit) {
                dbExecute(
                    "UPDATE recipes SET 
                        title = ?, slug = ?, description = ?, youtube_url = ?,
                        thumbnail_url = ?, ingredients = ?, steps = ?,
                        cook_time_minutes = ?, servings = ?, difficulty = ?,
                        category_id = ?, is_featured = ?, is_published = ?
                     WHERE id = ?",
                    [
                        $title, $slug, $description, $youtube_url,
                        $thumbnail_url, $ingredientsJson, $stepsJson,
                        $cook_time, $servings, $difficulty,
                        $category_id, $is_featured, $is_published,
                        $editId
                    ]
                );
                setFlash('success', 'Recipe updated successfully!');
            } else {
                $newId = dbInsert(
                    "INSERT INTO recipes 
                        (title, slug, description, youtube_url, thumbnail_url, 
                         ingredients, steps, cook_time_minutes, servings, difficulty, 
                         category_id, is_featured, is_published)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $title, $slug, $description, $youtube_url,
                        $thumbnail_url, $ingredientsJson, $stepsJson,
                        $cook_time, $servings, $difficulty,
                        $category_id, $is_featured, $is_published
                    ]
                );
                setFlash('success', 'Recipe created successfully!');
                header('Location: ' . SITE_URL . '/admin/recipe-edit.php?id=' . $newId);
                exit;
            }
            
            header('Location: ' . SITE_URL . '/admin/recipe-edit.php?id=' . $editId);
            exit;
        }
    }
}

// Prepare form values (use POST data on error, or existing recipe on edit, or empty for new)
$form = [
    'title'             => $_POST['title']             ?? ($recipe['title'] ?? ''),
    'description'       => $_POST['description']       ?? ($recipe['description'] ?? ''),
    'youtube_url'       => $_POST['youtube_url']       ?? ($recipe['youtube_url'] ?? ''),
    'category_id'       => $_POST['category_id']       ?? ($recipe['category_id'] ?? ''),
    'cook_time_minutes' => $_POST['cook_time_minutes'] ?? ($recipe['cook_time_minutes'] ?? ''),
    'servings'          => $_POST['servings']          ?? ($recipe['servings'] ?? ''),
    'difficulty'        => $_POST['difficulty']         ?? ($recipe['difficulty'] ?? 'Easy'),
    'is_featured'       => isset($_POST['is_featured']) ? 1 : ($recipe['is_featured'] ?? 0),
    'is_published'      => isset($_POST['is_published']) ? 1 : ($recipe['is_published'] ?? 1),
    'thumbnail_url'     => $recipe['thumbnail_url'] ?? '',
];

// Decode ingredients and steps for textarea display
if (isset($_POST['ingredients'])) {
    $form['ingredients'] = $_POST['ingredients'];
} elseif ($recipe && $recipe['ingredients']) {
    $decoded = json_decode($recipe['ingredients'], true);
    $form['ingredients'] = is_array($decoded) ? implode("\n", $decoded) : '';
} else {
    $form['ingredients'] = '';
}

if (isset($_POST['steps'])) {
    $form['steps'] = $_POST['steps'];
} elseif ($recipe && $recipe['steps']) {
    $decoded = json_decode($recipe['steps'], true);
    $form['steps'] = is_array($decoded) ? implode("\n", $decoded) : '';
} else {
    $form['steps'] = '';
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <a href="<?= SITE_URL ?>/admin/recipes.php" class="back-link">← Back to Recipes</a>
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

<form method="POST" enctype="multipart/form-data" class="recipe-form">
    <?= csrfField() ?>
    
    <div class="form-layout">
        <!-- ─── Main Column ────────────────── -->
        <div class="form-main">
            <div class="form-card">
                <div class="form-group">
                    <label for="title">Recipe Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" value="<?= h($form['title']) ?>" 
                           placeholder="e.g., Classic Kimchi Jjigae" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" 
                              placeholder="A short description of this recipe..."><?= h($form['description']) ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="youtube_url">YouTube Video URL</label>
                    <input type="url" id="youtube_url" name="youtube_url" value="<?= h($form['youtube_url']) ?>"
                           placeholder="https://www.youtube.com/watch?v=...">
                    <span class="form-hint">Paste any YouTube link — the video will be embedded automatically.</span>
                    
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
            
            <div class="form-card">
                <h2 class="form-card-title">Ingredients</h2>
                <div class="form-group">
                    <label for="ingredients">One ingredient per line</label>
                    <textarea id="ingredients" name="ingredients" rows="10"
                              placeholder="2 cups rice&#10;1 tbsp sesame oil&#10;3 cloves garlic, minced&#10;..."><?= h($form['ingredients']) ?></textarea>
                    <span class="form-hint">Each line becomes one ingredient in the list.</span>
                </div>
            </div>
            
            <div class="form-card">
                <h2 class="form-card-title">Instructions</h2>
                <div class="form-group">
                    <label for="steps">One step per line</label>
                    <textarea id="steps" name="steps" rows="10"
                              placeholder="Rinse the rice until water runs clear.&#10;Heat sesame oil in a large pot over medium heat.&#10;Add garlic and cook until fragrant, about 1 minute.&#10;..."><?= h($form['steps']) ?></textarea>
                    <span class="form-hint">Each line becomes a numbered step.</span>
                </div>
            </div>
        </div>
        
        <!-- ─── Sidebar Column ─────────────── -->
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
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_featured" value="1"
                               <?= $form['is_featured'] ? 'checked' : '' ?>>
                        ★ Featured on homepage
                    </label>
                </div>
                <div class="form-actions-card">
                    <button type="submit" class="btn btn-primary btn-full">
                        <?= $isEdit ? 'Update Recipe' : 'Create Recipe' ?>
                    </button>
                </div>
            </div>
            
            <div class="form-card">
                <h2 class="form-card-title">Details</h2>
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">— Select —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $form['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= h($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cook_time_minutes">Cook Time (min)</label>
                        <input type="number" id="cook_time_minutes" name="cook_time_minutes" 
                               value="<?= h($form['cook_time_minutes']) ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label for="servings">Servings</label>
                        <input type="number" id="servings" name="servings" 
                               value="<?= h($form['servings']) ?>" min="1">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="difficulty">Difficulty</label>
                    <select id="difficulty" name="difficulty">
                        <?php foreach (['Easy', 'Medium', 'Hard'] as $level): ?>
                            <option value="<?= $level ?>" <?= $form['difficulty'] === $level ? 'selected' : '' ?>>
                                <?= $level ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-card">
                <h2 class="form-card-title">Thumbnail</h2>
                <?php if (!empty($form['thumbnail_url'])): ?>
                    <div class="current-thumb">
                        <img src="<?= h($form['thumbnail_url']) ?>" alt="Current thumbnail">
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="thumbnail">Upload Image</label>
                    <input type="file" id="thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/webp">
                    <span class="form-hint">JPG, PNG, or WebP. Max 5MB. Leave empty to auto-use YouTube thumbnail.</span>
                </div>
            </div>
        </div>
    </div>
</form>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
