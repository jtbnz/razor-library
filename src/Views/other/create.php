<?php ob_start(); ?>

<div class="page-header">
    <h1>Add Item</h1>
</div>

<form action="<?= url('/other') ?>" method="POST" enctype="multipart/form-data" class="form-container" id="other-form">
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
        <select id="category" name="category" class="form-select" required>
            <?php foreach ($categories as $key => $label): ?>
            <option value="<?= $key ?>" <?= $selectedCategory === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" class="form-input" value="<?= e(old('name')) ?>" required>
    </div>

    <div class="form-group">
        <label for="brand" class="form-label">Brand</label>
        <input type="text" id="brand" name="brand" class="form-input" value="<?= e(old('brand')) ?>">
    </div>

    <!-- Dynamic attributes based on category -->
    <div id="category-attributes">
        <!-- Bowl attributes -->
        <div class="category-fields" data-category="bowl" style="display: none;">
            <div class="grid-2">
                <div class="form-group">
                    <label for="bowl-material" class="form-label">Material</label>
                    <input type="text" id="bowl-material" name="material" class="form-input" value="<?= e(old('material')) ?>" placeholder="e.g., Ceramic, Stainless Steel">
                </div>
                <div class="form-group">
                    <label for="bowl-size" class="form-label">Size</label>
                    <input type="text" id="bowl-size" name="size" class="form-input" value="<?= e(old('size')) ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="bowl-color" class="form-label">Color</label>
                <input type="text" id="bowl-color" name="color" class="form-input" value="<?= e(old('color')) ?>">
            </div>
        </div>

        <!-- Soap attributes -->
        <div class="category-fields" data-category="soap" style="display: none;">
            <div class="grid-2">
                <div class="form-group">
                    <label for="soap-base" class="form-label">Base/Formula</label>
                    <input type="text" id="soap-base" name="base" class="form-input" value="<?= e(old('base')) ?>" placeholder="e.g., Tallow, Vegan">
                </div>
                <div class="form-group">
                    <label for="soap-size" class="form-label">Size</label>
                    <input type="text" id="soap-size" name="size" class="form-input" value="<?= e(old('size')) ?>" placeholder="e.g., 4oz, 150g">
                </div>
            </div>
            <div class="form-group">
                <label for="soap-scent-strength" class="form-label">Scent Strength</label>
                <select id="soap-scent-strength" name="scent_strength" class="form-select">
                    <option value="">Select...</option>
                    <option value="Light" <?= old('scent_strength') === 'Light' ? 'selected' : '' ?>>Light</option>
                    <option value="Medium" <?= old('scent_strength') === 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="Strong" <?= old('scent_strength') === 'Strong' ? 'selected' : '' ?>>Strong</option>
                </select>
            </div>
        </div>

        <!-- Balm attributes -->
        <div class="category-fields" data-category="balm" style="display: none;">
            <div class="grid-2">
                <div class="form-group">
                    <label for="balm-size" class="form-label">Size</label>
                    <input type="text" id="balm-size" name="size" class="form-input" value="<?= e(old('size')) ?>" placeholder="e.g., 100ml, 3.4oz">
                </div>
                <div class="form-group">
                    <label for="balm-skin-type" class="form-label">Skin Type</label>
                    <select id="balm-skin-type" name="skin_type" class="form-select">
                        <option value="">Select...</option>
                        <option value="All Skin Types" <?= old('skin_type') === 'All Skin Types' ? 'selected' : '' ?>>All Skin Types</option>
                        <option value="Sensitive" <?= old('skin_type') === 'Sensitive' ? 'selected' : '' ?>>Sensitive</option>
                        <option value="Oily" <?= old('skin_type') === 'Oily' ? 'selected' : '' ?>>Oily</option>
                        <option value="Dry" <?= old('skin_type') === 'Dry' ? 'selected' : '' ?>>Dry</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Splash attributes -->
        <div class="category-fields" data-category="splash" style="display: none;">
            <div class="grid-2">
                <div class="form-group">
                    <label for="splash-size" class="form-label">Size</label>
                    <input type="text" id="splash-size" name="size" class="form-input" value="<?= e(old('size')) ?>" placeholder="e.g., 100ml, 3.4oz">
                </div>
                <div class="form-group">
                    <label for="splash-alcohol" class="form-label">Alcohol Content</label>
                    <input type="text" id="splash-alcohol" name="alcohol_content" class="form-input" value="<?= e(old('alcohol_content')) ?>" placeholder="e.g., Alcohol-free, 50%">
                </div>
            </div>
        </div>

        <!-- Fragrance attributes -->
        <div class="category-fields" data-category="fragrance" style="display: none;">
            <div class="grid-2">
                <div class="form-group">
                    <label for="fragrance-type" class="form-label">Type</label>
                    <select id="fragrance-type" name="type" class="form-select">
                        <option value="">Select...</option>
                        <option value="Eau de Toilette" <?= old('type') === 'Eau de Toilette' ? 'selected' : '' ?>>Eau de Toilette</option>
                        <option value="Eau de Parfum" <?= old('type') === 'Eau de Parfum' ? 'selected' : '' ?>>Eau de Parfum</option>
                        <option value="Eau de Cologne" <?= old('type') === 'Eau de Cologne' ? 'selected' : '' ?>>Eau de Cologne</option>
                        <option value="Parfum" <?= old('type') === 'Parfum' ? 'selected' : '' ?>>Parfum</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="fragrance-size" class="form-label">Size</label>
                    <input type="text" id="fragrance-size" name="size" class="form-input" value="<?= e(old('size')) ?>" placeholder="e.g., 50ml, 100ml">
                </div>
            </div>
            <div class="form-group">
                <label for="fragrance-concentration" class="form-label">Concentration</label>
                <input type="text" id="fragrance-concentration" name="concentration" class="form-input" value="<?= e(old('concentration')) ?>" placeholder="e.g., 15-20%">
            </div>
        </div>
    </div>

    <div class="form-group">
        <label for="scent_notes" class="form-label">Scent Notes</label>
        <textarea id="scent_notes" name="scent_notes" class="form-input" rows="2" placeholder="Describe the scent profile..."><?= e(old('scent_notes')) ?></textarea>
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
        <span class="form-hint">Max 10MB per image. JPEG, PNG, GIF, or WebP. You can select multiple images. The first image will be set as the tile/hero image.</span>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Add Item</button>
        <a href="<?= url('/other') ?>" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category');
    const categoryFields = document.querySelectorAll('.category-fields');

    function showCategoryFields() {
        const selected = categorySelect.value;
        categoryFields.forEach(function(fields) {
            if (fields.dataset.category === selected) {
                fields.style.display = 'block';
            } else {
                fields.style.display = 'none';
            }
        });
    }

    categorySelect.addEventListener('change', showCategoryFields);
    showCategoryFields();
});
</script>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
