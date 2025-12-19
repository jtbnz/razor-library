<?php ob_start(); ?>

<div class="page-header">
    <h1>Edit Brush</h1>
</div>

<form action="/brushes/<?= $brush['id'] ?>" method="POST" enctype="multipart/form-data" class="form-container">
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" class="form-input" value="<?= e($brush['name']) ?>" required>
    </div>

    <div class="form-group">
        <label for="brand" class="form-label">Brand</label>
        <input type="text" id="brand" name="brand" class="form-input" value="<?= e($brush['brand'] ?? '') ?>">
    </div>

    <div class="grid-2">
        <div class="form-group">
            <label for="bristle_type" class="form-label">Bristle Type</label>
            <select id="bristle_type" name="bristle_type" class="form-select">
                <option value="">Select bristle type...</option>
                <option value="Badger - Silvertip" <?= ($brush['bristle_type'] ?? '') === 'Badger - Silvertip' ? 'selected' : '' ?>>Badger - Silvertip</option>
                <option value="Badger - Best" <?= ($brush['bristle_type'] ?? '') === 'Badger - Best' ? 'selected' : '' ?>>Badger - Best</option>
                <option value="Badger - Super" <?= ($brush['bristle_type'] ?? '') === 'Badger - Super' ? 'selected' : '' ?>>Badger - Super</option>
                <option value="Badger - Pure" <?= ($brush['bristle_type'] ?? '') === 'Badger - Pure' ? 'selected' : '' ?>>Badger - Pure</option>
                <option value="Boar" <?= ($brush['bristle_type'] ?? '') === 'Boar' ? 'selected' : '' ?>>Boar</option>
                <option value="Horse" <?= ($brush['bristle_type'] ?? '') === 'Horse' ? 'selected' : '' ?>>Horse</option>
                <option value="Synthetic" <?= ($brush['bristle_type'] ?? '') === 'Synthetic' ? 'selected' : '' ?>>Synthetic</option>
                <option value="Mixed" <?= ($brush['bristle_type'] ?? '') === 'Mixed' ? 'selected' : '' ?>>Mixed</option>
            </select>
        </div>

        <div class="form-group">
            <label for="handle_material" class="form-label">Handle Material</label>
            <select id="handle_material" name="handle_material" class="form-select">
                <option value="">Select material...</option>
                <option value="Wood" <?= ($brush['handle_material'] ?? '') === 'Wood' ? 'selected' : '' ?>>Wood</option>
                <option value="Resin" <?= ($brush['handle_material'] ?? '') === 'Resin' ? 'selected' : '' ?>>Resin</option>
                <option value="Acrylic" <?= ($brush['handle_material'] ?? '') === 'Acrylic' ? 'selected' : '' ?>>Acrylic</option>
                <option value="Metal" <?= ($brush['handle_material'] ?? '') === 'Metal' ? 'selected' : '' ?>>Metal</option>
                <option value="Bone" <?= ($brush['handle_material'] ?? '') === 'Bone' ? 'selected' : '' ?>>Bone</option>
                <option value="Horn" <?= ($brush['handle_material'] ?? '') === 'Horn' ? 'selected' : '' ?>>Horn</option>
                <option value="Plastic" <?= ($brush['handle_material'] ?? '') === 'Plastic' ? 'selected' : '' ?>>Plastic</option>
            </select>
        </div>
    </div>

    <div class="grid-2">
        <div class="form-group">
            <label for="knot_size" class="form-label">Knot Size (mm)</label>
            <input type="text" id="knot_size" name="knot_size" class="form-input" value="<?= e($brush['knot_size'] ?? '') ?>" placeholder="e.g., 24mm">
        </div>

        <div class="form-group">
            <label for="loft" class="form-label">Loft (mm)</label>
            <input type="text" id="loft" name="loft" class="form-input" value="<?= e($brush['loft'] ?? '') ?>" placeholder="e.g., 50mm">
        </div>
    </div>

    <div class="form-group">
        <label for="description" class="form-label">Description</label>
        <textarea id="description" name="description" class="form-input" rows="3"><?= e($brush['description'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label for="notes" class="form-label">Notes</label>
        <textarea id="notes" name="notes" class="form-input" rows="3"><?= e($brush['notes'] ?? '') ?></textarea>
    </div>

    <p class="text-muted mb-3">To manage images, go to the <a href="/brushes/<?= $brush['id'] ?>">brush detail page</a> where you can upload multiple images and select the tile image.</p>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update Brush</button>
        <a href="/brushes/<?= $brush['id'] ?>" class="btn btn-outline">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
