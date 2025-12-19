<?php ob_start(); ?>

<div class="page-header">
    <h1>Edit Blade</h1>
</div>

<form action="/blades/<?= $blade['id'] ?>" method="POST" enctype="multipart/form-data" class="form-container">
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" class="form-input" value="<?= e($blade['name']) ?>" required>
    </div>

    <div class="form-group">
        <label for="brand" class="form-label">Brand</label>
        <input type="text" id="brand" name="brand" class="form-input" value="<?= e($blade['brand'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="description" class="form-label">Description</label>
        <textarea id="description" name="description" class="form-input" rows="3"><?= e($blade['description'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label for="notes" class="form-label">Notes</label>
        <textarea id="notes" name="notes" class="form-input" rows="3"><?= e($blade['notes'] ?? '') ?></textarea>
    </div>

    <p class="text-muted mb-3">To manage images, go to the <a href="/blades/<?= $blade['id'] ?>">blade detail page</a> where you can upload multiple images and select the tile image.</p>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update Blade</button>
        <a href="/blades/<?= $blade['id'] ?>" class="btn btn-outline">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
