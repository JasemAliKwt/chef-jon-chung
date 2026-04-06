<?php
/**
 * Admin — Add / Edit / Duplicate Recipe
 */
require_once __DIR__ . '/../includes/auth.php';

$editId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $editId !== null;

// Handle duplicate
$isDuplicate = isset($_GET['duplicate']);
$sourceRecipe = null;
if ($isDuplicate) {
    $sourceId = (int) $_GET['duplicate'];
    $sourceRecipe = dbFetchOne("SELECT * FROM recipes WHERE id = ?", [$sourceId]);
    if (!$sourceRecipe) {
        setFlash('error', 'Source recipe not found.');
        header('Location: ' . SITE_URL . '/admin/recipes.php');
        exit;
    }
}

$pageTitle = $isDuplicate ? 'Duplicate Recipe' : ($isEdit ? 'Edit Recipe' : 'Add Recipe');

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

// Load categories
$categories = dbFetchAll("SELECT * FROM categories ORDER BY sort_order, name");

// Load existing gallery images if editing
$galleryImages = [];
if ($isEdit) {
    $galleryImages = dbFetchAll(
        "SELECT * FROM recipe_images WHERE recipe_id = ? ORDER BY sort_order",
        [$editId]
    );
}

// Handle gallery image deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_image') {
    if (csrfValidate()) {
        $imgId = (int) ($_POST['image_id'] ?? 0);
        if ($imgId > 0) {
            dbExecute("DELETE FROM recipe_images WHERE id = ? AND recipe_id = ?", [$imgId, $editId]);
            setFlash('success', 'Image removed.');
        }
    }
    header('Location: ' . SITE_URL . '/admin/recipe-edit.php?id=' . $editId);
    exit;
}

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'delete_image') {
    if (!csrfValidate()) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $youtube_url = trim($_POST['youtube_url'] ?? '');
        $category_id = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
        $cook_time   = !empty($_POST['cook_time_minutes']) ? (int) $_POST['cook_time_minutes'] : null;
        $servings    = !empty($_POST['servings']) ? (int) $_POST['servings'] : null;
        $difficulty  = in_array($_POST['difficulty'] ?? '', ['Easy', 'Medium', 'Hard'])
                       ? $_POST['difficulty'] : 'Easy';
        $spice_level = in_array($_POST['spice_level'] ?? '', ['None', 'Mild', 'Medium', 'Hot', 'Extra Hot'])
                       ? $_POST['spice_level'] : 'None';
        $allergens   = $_POST['allergens'] ?? [];
        $dietary     = $_POST['dietary'] ?? [];
        $is_featured  = isset($_POST['is_featured']) ? 1 : 0;
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $sort_order   = (int) ($_POST['sort_order'] ?? 0);

        // Process ingredients and steps
        $ingredientsRaw = trim($_POST['ingredients'] ?? '');
        $ingredients = array_values(array_filter(array_map('trim', explode("\n", $ingredientsRaw))));

        $stepsRaw = trim($_POST['steps'] ?? '');
        $steps = array_values(array_filter(array_map('trim', explode("\n", $stepsRaw))));

        // Handle thumbnail
        $thumbnail_url = $recipe['thumbnail_url'] ?? '';
        if (!empty($_FILES['thumbnail']['tmp_name']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'];

            if (!in_array($ext, $allowedExts)) {
                $errors[] = 'Thumbnail must be a JPG, PNG, or WebP image.';
            } elseif ($_FILES['thumbnail']['size'] > MAX_UPLOAD_SIZE) {
                $errors[] = 'Thumbnail must be under 5MB.';
            } else {
                if (in_array($ext, ['heic', 'heif'])) $ext = 'jpg';
                $filename = 'recipe-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destPath = UPLOADS_DIR . $filename;
                if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);
                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $destPath)) {
                    $thumbnail_url = UPLOADS_URL . $filename;
                } else {
                    $errors[] = 'Failed to upload thumbnail.';
                }
            }
        } elseif (empty($thumbnail_url) && !empty($youtube_url)) {
            $thumbnail_url = youTubeThumbnail($youtube_url) ?? '';
        }

        if (empty($title)) $errors[] = 'Recipe title is required.';

        if (empty($errors)) {
            $slug = uniqueSlug('recipes', $title, $editId);
            $ingredientsJson = json_encode($ingredients);
            $stepsJson = json_encode($steps);
            $allergensJson = json_encode(array_values($allergens));
            $dietaryJson = json_encode(array_values($dietary));

            if ($isEdit) {
                dbExecute(
                    "UPDATE recipes SET
                        title = ?, slug = ?, description = ?, youtube_url = ?,
                        thumbnail_url = ?, ingredients = ?, steps = ?,
                        cook_time_minutes = ?, servings = ?, difficulty = ?,
                        spice_level = ?, allergens = ?, dietary = ?,
                        category_id = ?, is_featured = ?, sort_order = ?, is_published = ?
                     WHERE id = ?",
                    [
                        $title, $slug, $description, $youtube_url,
                        $thumbnail_url, $ingredientsJson, $stepsJson,
                        $cook_time, $servings, $difficulty,
                        $spice_level, $allergensJson, $dietaryJson,
                        $category_id, $is_featured, $sort_order, $is_published,
                        $editId
                    ]
                );
                $savedId = $editId;
                setFlash('success', 'Recipe updated!');
            } else {
                $savedId = dbInsert(
                    "INSERT INTO recipes
                        (title, slug, description, youtube_url, thumbnail_url,
                         ingredients, steps, cook_time_minutes, servings, difficulty,
                         spice_level, allergens, dietary,
                         category_id, is_featured, sort_order, is_published)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $title, $slug, $description, $youtube_url,
                        $thumbnail_url, $ingredientsJson, $stepsJson,
                        $cook_time, $servings, $difficulty,
                        $spice_level, $allergensJson, $dietaryJson,
                        $category_id, $is_featured, $sort_order, $is_published
                    ]
                );
                setFlash('success', 'Recipe created!');
            }

            // Handle gallery image uploads
            $galleryErrors = 0;
            if (isset($_FILES['gallery']) && is_array($_FILES['gallery']['tmp_name'])) {
                $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'];
                $maxGallery = dbCount("SELECT COUNT(*) FROM recipe_images WHERE recipe_id = ?", [$savedId]);

                foreach ($_FILES['gallery']['tmp_name'] as $i => $tmpName) {
                    if (empty($tmpName) || $_FILES['gallery']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    if (!file_exists($tmpName)) continue;
                    if ($_FILES['gallery']['size'][$i] > MAX_UPLOAD_SIZE) { $galleryErrors++; continue; }

                    $ext = strtolower(pathinfo($_FILES['gallery']['name'][$i], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExts)) { $galleryErrors++; continue; }
                    if (in_array($ext, ['heic', 'heif'])) $ext = 'jpg';

                    $filename = 'gallery-' . $savedId . '-' . time() . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
                    $destPath = UPLOADS_DIR . $filename;
                    if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);

                    if (move_uploaded_file($tmpName, $destPath)) {
                        $maxGallery++;
                        dbInsert(
                            "INSERT INTO recipe_images (recipe_id, image_url, sort_order) VALUES (?, ?, ?)",
                            [$savedId, UPLOADS_URL . $filename, $maxGallery]
                        );
                    } else {
                        $galleryErrors++;
                    }
                }
            }

            if ($galleryErrors > 0) {
                setFlash('error', "Recipe saved but {$galleryErrors} image(s) failed to upload.");
            }

            header('Location: ' . SITE_URL . '/admin/recipe-edit.php?id=' . $savedId);
            exit;
        }
    }
}

// Prepare form values — use POST data on error, duplicate source, existing recipe, or empty
$source = $sourceRecipe ?? $recipe;
$form = [
    'title'             => $_POST['title']             ?? ($isDuplicate ? $source['title'] . ' (Copy)' : ($source['title'] ?? '')),
    'description'       => $_POST['description']       ?? ($source['description'] ?? ''),
    'youtube_url'       => $_POST['youtube_url']       ?? ($source['youtube_url'] ?? ''),
    'category_id'       => $_POST['category_id']       ?? ($source['category_id'] ?? ''),
    'cook_time_minutes' => $_POST['cook_time_minutes'] ?? ($source['cook_time_minutes'] ?? ''),
    'servings'          => $_POST['servings']          ?? ($source['servings'] ?? ''),
    'difficulty'        => $_POST['difficulty']         ?? ($source['difficulty'] ?? 'Easy'),
    'spice_level'       => $_POST['spice_level']       ?? ($source['spice_level'] ?? 'None'),
    'is_featured'       => isset($_POST['is_featured']) ? 1 : ($source['is_featured'] ?? 0),
    'is_published'      => isset($_POST['is_published']) ? 1 : ($isDuplicate ? 0 : ($source['is_published'] ?? 1)),
    'sort_order'        => $_POST['sort_order']        ?? ($source['sort_order'] ?? 0),
    'thumbnail_url'     => $recipe['thumbnail_url'] ?? '',
];

// Allergens
if (isset($_POST['allergens'])) {
    $form['allergens'] = $_POST['allergens'];
} elseif ($source && !empty($source['allergens'])) {
    $form['allergens'] = json_decode($source['allergens'], true) ?: [];
} else {
    $form['allergens'] = [];
}

// Dietary
if (isset($_POST['dietary'])) {
    $form['dietary'] = $_POST['dietary'];
} elseif ($source && !empty($source['dietary'])) {
    $form['dietary'] = json_decode($source['dietary'], true) ?: [];
} else {
    $form['dietary'] = [];
}

// Ingredients & steps
if (isset($_POST['ingredients'])) {
    $form['ingredients'] = $_POST['ingredients'];
} elseif ($source && $source['ingredients']) {
    $decoded = json_decode($source['ingredients'], true);
    $form['ingredients'] = is_array($decoded) ? implode("\n", $decoded) : '';
} else {
    $form['ingredients'] = '';
}

if (isset($_POST['steps'])) {
    $form['steps'] = $_POST['steps'];
} elseif ($source && $source['steps']) {
    $decoded = json_decode($source['steps'], true);
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
    <?php if ($isEdit): ?>
        <div class="page-header-actions">
            <a href="<?= SITE_URL ?>/admin/recipe-edit.php?duplicate=<?= $editId ?>" class="btn btn-sm btn-outline">Duplicate</a>
        </div>
    <?php endif; ?>
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

<!-- Hidden form for gallery image deletion (outside main form to avoid nesting) -->
<?php if ($isEdit): ?>
<form method="POST" id="deleteImageForm" style="display:none;">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
    <input type="hidden" name="action" value="delete_image">
    <input type="hidden" name="image_id" value="" id="deleteImageId">
</form>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="recipe-form">
    <?= csrfField() ?>

    <div class="form-layout">
        <!-- Main Column -->
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
                    <?php $embedUrl = youTubeEmbed($form['youtube_url']); if ($embedUrl): ?>
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
                              placeholder="2 cups rice&#10;1 tbsp sesame oil&#10;3 cloves garlic, minced"><?= h($form['ingredients']) ?></textarea>
                </div>
            </div>

            <div class="form-card">
                <h2 class="form-card-title">Instructions</h2>
                <div class="form-group">
                    <label for="steps">One step per line</label>
                    <textarea id="steps" name="steps" rows="10"
                              placeholder="Rinse the rice until water runs clear.&#10;Heat sesame oil in a large pot."><?= h($form['steps']) ?></textarea>
                </div>
            </div>

            <!-- Photo Gallery -->
            <div class="form-card">
                <h2 class="form-card-title">Photo Gallery</h2>

                <?php if (!empty($galleryImages)): ?>
                    <div class="gallery-grid">
                        <?php foreach ($galleryImages as $img): ?>
                            <div class="gallery-item" id="gallery-item-<?= $img['id'] ?>">
                                <img src="<?= h($img['image_url']) ?>" alt="">
                                <button type="button" class="gallery-delete-btn" title="Remove image"
                                        onclick="deleteGalleryImage(<?= $img['id'] ?>)">×</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="gallery">Add Photos</label>
                    <input type="file" id="gallery" name="gallery[]" accept="image/jpeg,image/png,image/webp" multiple>
                    <span class="form-hint">JPG, PNG, or WebP. Max 5MB each. You can select multiple files.</span>
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
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_featured" value="1"
                               <?= $form['is_featured'] ? 'checked' : '' ?>>
                        Featured on homepage
                    </label>
                </div>
                <div class="form-group">
                    <label for="sort_order">Homepage Order</label>
                    <input type="number" id="sort_order" name="sort_order"
                           value="<?= h($form['sort_order']) ?>" min="0">
                    <span class="form-hint">Lower number = shown first. 0 = default.</span>
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

                <div class="form-group">
                    <label for="spice_level">Spice Level</label>
                    <select id="spice_level" name="spice_level">
                        <?php foreach (['None', 'Mild', 'Medium', 'Hot', 'Extra Hot'] as $level): ?>
                            <option value="<?= $level ?>" <?= $form['spice_level'] === $level ? 'selected' : '' ?>>
                                <?= $level ?> <?= spiceLevelIcon($level) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-card">
                <h2 class="form-card-title">Allergen Warnings</h2>
                <div class="form-group allergen-grid">
                    <?php foreach (getAllergenOptions() as $key => $label): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="allergens[]" value="<?= $key ?>"
                                   <?= in_array($key, $form['allergens']) ? 'checked' : '' ?>>
                            <?= $label ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <span class="form-hint">Select all that apply. Shown as a warning on the recipe page.</span>
            </div>

            <div class="form-card">
                <h2 class="form-card-title">Dietary Info</h2>
                <div class="form-group allergen-grid">
                    <?php foreach (getDietaryOptions() as $key => $label): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="dietary[]" value="<?= $key ?>"
                                   <?= in_array($key, $form['dietary']) ? 'checked' : '' ?>>
                            <?= $label ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <span class="form-hint">Dietary labels shown as tags on the recipe page.</span>
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
                    <span class="form-hint">Max 5MB. Leave empty to auto-use YouTube thumbnail.</span>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function deleteGalleryImage(imageId) {
    if (confirm('Remove this image?')) {
        document.getElementById('deleteImageId').value = imageId;
        document.getElementById('deleteImageForm').submit();
    }
}
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
