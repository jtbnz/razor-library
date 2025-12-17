<?php ob_start(); ?>

<div class="page-header">
    <h1>Add Razor</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="/razors" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="name" class="form-label">Name *</label>
                <input type="text" id="name" name="name" class="form-input"
                       value="<?= e(old('name')) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="hero_image" class="form-label">Hero Image</label>
                <input type="file" id="hero_image" name="hero_image" class="form-input"
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       data-preview="#image-preview">
                <span class="form-hint">Max 10MB. JPEG, PNG, GIF, or WebP.</span>
                <img id="image-preview" src="" alt="" style="display: none; max-width: 300px; margin-top: 1rem; border-radius: 8px;">
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-textarea"><?= e(old('description')) ?></textarea>
            </div>

            <div class="form-group">
                <label for="notes" class="form-label">Notes</label>
                <textarea id="notes" name="notes" class="form-textarea"><?= e(old('notes')) ?></textarea>
                <span class="form-hint">Personal notes about this razor.</span>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Razor</button>
                <a href="/razors" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
