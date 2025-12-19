<?php ob_start(); ?>

<div class="page-header">
    <h1>Add Brush</h1>
</div>

<form action="/brushes" method="POST" enctype="multipart/form-data" class="form-container">
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" class="form-input" value="<?= e(old('name')) ?>" required>
    </div>

    <div class="form-group">
        <label for="brand" class="form-label">Brand</label>
        <input type="text" id="brand" name="brand" class="form-input" value="<?= e(old('brand')) ?>">
    </div>

    <div class="grid-2">
        <div class="form-group">
            <label for="bristle_type" class="form-label">Bristle Type</label>
            <select id="bristle_type" name="bristle_type" class="form-select">
                <option value="">Select bristle type...</option>
                <option value="Badger - Silvertip" <?= old('bristle_type') === 'Badger - Silvertip' ? 'selected' : '' ?>>Badger - Silvertip</option>
                <option value="Badger - Best" <?= old('bristle_type') === 'Badger - Best' ? 'selected' : '' ?>>Badger - Best</option>
                <option value="Badger - Super" <?= old('bristle_type') === 'Badger - Super' ? 'selected' : '' ?>>Badger - Super</option>
                <option value="Badger - Pure" <?= old('bristle_type') === 'Badger - Pure' ? 'selected' : '' ?>>Badger - Pure</option>
                <option value="Boar" <?= old('bristle_type') === 'Boar' ? 'selected' : '' ?>>Boar</option>
                <option value="Horse" <?= old('bristle_type') === 'Horse' ? 'selected' : '' ?>>Horse</option>
                <option value="Synthetic" <?= old('bristle_type') === 'Synthetic' ? 'selected' : '' ?>>Synthetic</option>
                <option value="Mixed" <?= old('bristle_type') === 'Mixed' ? 'selected' : '' ?>>Mixed</option>
            </select>
        </div>

        <div class="form-group">
            <label for="handle_material" class="form-label">Handle Material</label>
            <select id="handle_material" name="handle_material" class="form-select">
                <option value="">Select material...</option>
                <option value="Wood" <?= old('handle_material') === 'Wood' ? 'selected' : '' ?>>Wood</option>
                <option value="Resin" <?= old('handle_material') === 'Resin' ? 'selected' : '' ?>>Resin</option>
                <option value="Acrylic" <?= old('handle_material') === 'Acrylic' ? 'selected' : '' ?>>Acrylic</option>
                <option value="Metal" <?= old('handle_material') === 'Metal' ? 'selected' : '' ?>>Metal</option>
                <option value="Bone" <?= old('handle_material') === 'Bone' ? 'selected' : '' ?>>Bone</option>
                <option value="Horn" <?= old('handle_material') === 'Horn' ? 'selected' : '' ?>>Horn</option>
                <option value="Plastic" <?= old('handle_material') === 'Plastic' ? 'selected' : '' ?>>Plastic</option>
            </select>
        </div>
    </div>

    <div class="grid-2">
        <div class="form-group">
            <label for="knot_size" class="form-label">Knot Size (mm)</label>
            <input type="text" id="knot_size" name="knot_size" class="form-input" value="<?= e(old('knot_size')) ?>" placeholder="e.g., 24mm">
        </div>

        <div class="form-group">
            <label for="loft" class="form-label">Loft (mm)</label>
            <input type="text" id="loft" name="loft" class="form-input" value="<?= e(old('loft')) ?>" placeholder="e.g., 50mm">
        </div>
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
        <label for="images" class="form-label">Images</label>
        <input type="file" id="images" name="images[]" class="form-input" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
        <span class="form-hint">Max 10MB per image. You can select multiple images. The first image will be set as the tile/hero image.</span>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Add Brush</button>
        <a href="/brushes" class="btn btn-outline">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
