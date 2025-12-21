<?php ob_start(); ?>

<div class="page-header">
    <h1>Edit Razor</h1>
</div>

<form action="<?= url('/razors/' . $razor['id']) ?>" method="POST" enctype="multipart/form-data" class="form-container">
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="brand" class="form-label">Brand</label>
        <input type="text" id="brand" name="brand" class="form-input" value="<?= e($razor['brand'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" class="form-input" value="<?= e($razor['name']) ?>" required>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="year_manufactured" class="form-label">Year Manufactured</label>
            <input type="number" id="year_manufactured" name="year_manufactured" class="form-input"
                   value="<?= e($razor['year_manufactured'] ?? '') ?>" min="1800" max="<?= date('Y') ?>" placeholder="e.g., 1965">
        </div>

        <div class="form-group">
            <label for="country_manufactured" class="form-label">Country of Manufacture</label>
            <input type="text" id="country_manufactured" name="country_manufactured" class="form-input"
                   value="<?= e($razor['country_manufactured'] ?? '') ?>" placeholder="e.g., Germany, USA, Japan">
        </div>
    </div>

    <div class="form-group">
        <label for="description" class="form-label">Description</label>
        <textarea id="description" name="description" class="form-input" rows="3"><?= e($razor['description'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label for="notes" class="form-label">Notes</label>
        <textarea id="notes" name="notes" class="form-input" rows="3"><?= e($razor['notes'] ?? '') ?></textarea>
    </div>

    <p class="text-muted mb-3">To manage images, go to the <a href="<?= url('/razors/' . $razor['id']) ?>">razor detail page</a> where you can upload multiple images and select the tile image.</p>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update Razor</button>
        <a href="<?= url('/razors/' . $razor['id']) ?>" class="btn btn-outline">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
