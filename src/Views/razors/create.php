<?php ob_start(); ?>

<div class="page-header">
    <h1>Add Razor</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?= url('/razors') ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="name" class="form-label">Name *</label>
                <input type="text" id="name" name="name" class="form-input"
                       value="<?= e(old('name')) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="images" class="form-label">Images</label>
                <input type="file" id="images" name="images[]" class="form-input"
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       multiple>
                <span class="form-hint">Max 10MB per image. JPEG, PNG, GIF, or WebP. You can select multiple images. The first image will be set as the tile/hero image.</span>
                <div id="image-previews" class="image-preview-grid mt-2"></div>
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
                <a href="<?= url('/razors') ?>" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
