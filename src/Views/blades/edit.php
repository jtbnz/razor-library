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

    <div class="form-group">
        <label for="hero_image" class="form-label">Hero Image</label>
        <?php if ($blade['hero_image']): ?>
        <div class="current-image mb-2">
            <img src="<?= upload_url("users/{$_SESSION['user_id']}/blades/" . str_replace('.', '_thumb.', $blade['hero_image'])) ?>"
                 alt="Current hero image"
                 style="max-width: 200px; border-radius: var(--radius-md);">
            <p class="text-muted text-sm mt-1">Current image. Upload a new one to replace it.</p>
        </div>
        <?php endif; ?>
        <input type="file" id="hero_image" name="hero_image" class="form-input" accept="image/jpeg,image/png,image/gif,image/webp">
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update Blade</button>
        <a href="/blades/<?= $blade['id'] ?>" class="btn btn-outline">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
