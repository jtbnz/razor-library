<?php ob_start(); ?>

<div class="page-header">
    <h1>Add Blade</h1>
</div>

<form action="/blades" method="POST" enctype="multipart/form-data" class="form-container">
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" class="form-input" value="<?= e(old('name')) ?>" required>
    </div>

    <div class="form-group">
        <label for="brand" class="form-label">Brand</label>
        <input type="text" id="brand" name="brand" class="form-input" value="<?= e(old('brand')) ?>">
    </div>

    <div class="form-group">
        <label for="description" class="form-label">Description</label>
        <textarea id="description" name="description" class="form-input" rows="3"><?= e(old('description')) ?></textarea>
    </div>

    <div class="form-group">
        <label for="notes" class="form-label">Notes</label>
        <textarea id="notes" name="notes" class="form-input" rows="3"><?= e(old('notes')) ?></textarea>
    </div>

    <div class="form-group">
        <label for="hero_image" class="form-label">Hero Image</label>
        <input type="file" id="hero_image" name="hero_image" class="form-input" accept="image/jpeg,image/png,image/gif,image/webp">
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Add Blade</button>
        <a href="/blades" class="btn btn-outline">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
