<?php ob_start(); ?>

<div class="page-header">
    <h1><?= e($brush['name']) ?></h1>
    <div class="page-header-actions">
        <a href="<?= url('/brushes/' . $brush['id'] . '/edit') ?>" class="btn btn-outline">Edit</a>
        <form action="<?= url('/brushes/' . $brush['id'] . '/delete') ?>" method="POST" style="display: inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger" data-confirm="Are you sure you want to delete this brush?">Delete</button>
        </form>
    </div>
</div>

<!-- Hero Image -->
<div class="detail-hero">
    <?php if ($brush['hero_image']): ?>
    <img src="<?= upload_url("users/{$_SESSION['user_id']}/brushes/{$brush['hero_image']}") ?>"
         alt="<?= e($brush['name']) ?>">
    <?php else: ?>
    <div class="placeholder-image">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
    </div>
    <?php endif; ?>
</div>

<div class="grid-2">
    <div>
        <!-- Specifications -->
        <div class="detail-section">
            <h3>Specifications</h3>
            <dl class="spec-list">
                <?php if ($brush['brand']): ?>
                <div class="spec-item">
                    <dt>Brand</dt>
                    <dd><?= e($brush['brand']) ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($brush['bristle_type']): ?>
                <div class="spec-item">
                    <dt>Bristle Type</dt>
                    <dd><?= e($brush['bristle_type']) ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($brush['knot_size']): ?>
                <div class="spec-item">
                    <dt>Knot Size</dt>
                    <dd><?= e($brush['knot_size']) ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($brush['loft']): ?>
                <div class="spec-item">
                    <dt>Loft</dt>
                    <dd><?= e($brush['loft']) ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($brush['handle_material']): ?>
                <div class="spec-item">
                    <dt>Handle Material</dt>
                    <dd><?= e($brush['handle_material']) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>

        <!-- Description -->
        <?php if ($brush['description']): ?>
        <div class="detail-section">
            <h3>Description</h3>
            <p><?= nl2br(e($brush['description'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <?php if ($brush['notes']): ?>
        <div class="detail-section">
            <h3>Notes</h3>
            <p><?= nl2br(e($brush['notes'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Related URLs -->
        <div class="detail-section">
            <h3>Related URLs</h3>
            <?php if (!empty($urls)): ?>
            <ul class="url-list">
                <?php foreach ($urls as $url): ?>
                <li>
                    <div class="url-info">
                        <a href="<?= e($url['url']) ?>" target="_blank" rel="noopener" class="url-link"><?= e($url['url']) ?></a>
                        <?php if ($url['description']): ?>
                        <span class="url-desc"><?= e($url['description']) ?></span>
                        <?php endif; ?>
                    </div>
                    <form action="<?= url('/brushes/' . $brush['id'] . '/urls/' . $url['id'] . '/delete') ?>" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline" data-confirm="Delete this URL?">Delete</button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="text-muted">No URLs added yet.</p>
            <?php endif; ?>

            <form action="<?= url('/brushes/' . $brush['id'] . '/urls') ?>" method="POST" class="mt-3">
                <?= csrf_field() ?>
                <div class="d-flex gap-2 flex-wrap">
                    <input type="url" name="url" placeholder="https://example.com" class="form-input" style="flex: 2; min-width: 200px;" required>
                    <input type="text" name="url_description" placeholder="Description (optional)" class="form-input" style="flex: 1; min-width: 150px;">
                    <button type="submit" class="btn btn-outline">Add URL</button>
                </div>
            </form>
        </div>
    </div>

    <div>
        <!-- Usage Tracking -->
        <div class="detail-section">
            <h3>Usage</h3>
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="text-lg"><strong><?= $brush['use_count'] ?></strong> total uses</span>
                <form action="<?= url('/brushes/' . $brush['id'] . '/use') ?>" method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary">Record Use</button>
                </form>
            </div>
            <?php if ($brush['last_used_at']): ?>
            <p class="text-muted">Last used: <?= date('M j, Y', strtotime($brush['last_used_at'])) ?></p>
            <?php endif; ?>
        </div>

        <!-- Images -->
        <div class="detail-section">
            <h3>Images</h3>
            <?php if (!empty($images)): ?>
            <div class="image-gallery">
                <?php foreach ($images as $image): ?>
                <div class="image-gallery-item <?= $image['filename'] === $brush['hero_image'] ? 'is-hero' : '' ?>">
                    <img src="<?= upload_url("users/{$_SESSION['user_id']}/brushes/" . str_replace('.', '_thumb.', $image['filename'])) ?>"
                         alt="Additional image"
                         loading="lazy">
                    <?php if ($image['filename'] === $brush['hero_image']): ?>
                    <span class="hero-badge">Tile</span>
                    <?php endif; ?>
                    <div class="image-actions">
                        <?php if ($image['filename'] !== $brush['hero_image']): ?>
                        <form action="<?= url('/brushes/' . $brush['id'] . '/images/' . $image['id'] . '/hero') ?>" method="POST" style="display:inline;">
                            <?= csrf_field() ?>
                            <button type="submit" class="hero-btn" title="Set as tile image">&#9733;</button>
                        </form>
                        <?php endif; ?>
                        <form action="<?= url('/brushes/' . $brush['id'] . '/images/' . $image['id'] . '/delete') ?>" method="POST" style="display:inline;">
                            <?= csrf_field() ?>
                            <button type="submit" class="delete-btn" data-confirm="Delete this image?">&times;</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted">No images uploaded yet.</p>
            <?php endif; ?>

            <form action="<?= url('/brushes/' . $brush['id'] . '/images') ?>" method="POST" enctype="multipart/form-data" class="mt-3">
                <?= csrf_field() ?>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <input type="file" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" class="form-input" style="flex: 1;" multiple required>
                    <button type="submit" class="btn btn-outline">Upload Images</button>
                </div>
                <p class="form-hint mt-1">You can select multiple images at once.</p>
            </form>
        </div>
    </div>
</div>

<div class="mt-4">
    <a href="<?= url('/brushes') ?>" class="btn btn-outline">&larr; Back to Brushes</a>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
