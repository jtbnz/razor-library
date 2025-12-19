<?php ob_start(); ?>

<div class="page-header">
    <h1><?= e($blade['name']) ?></h1>
    <div class="page-header-actions">
        <a href="<?= url('/blades/' . $blade['id'] . '/edit') ?>" class="btn btn-outline">Edit</a>
        <form action="<?= url('/blades/' . $blade['id'] . '/delete') ?>" method="POST" style="display: inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger" data-confirm="Are you sure you want to delete this blade?">Delete</button>
        </form>
    </div>
</div>

<!-- Hero Image -->
<div class="detail-hero">
    <?php if ($blade['hero_image']): ?>
    <img src="<?= upload_url("users/{$_SESSION['user_id']}/blades/{$blade['hero_image']}") ?>"
         alt="<?= e($blade['name']) ?>">
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
        <!-- Brand -->
        <?php if ($blade['brand']): ?>
        <div class="detail-section">
            <h3>Brand</h3>
            <p><?= e($blade['brand']) ?></p>
        </div>
        <?php endif; ?>

        <!-- Description -->
        <?php if ($blade['description']): ?>
        <div class="detail-section">
            <h3>Description</h3>
            <p><?= nl2br(e($blade['description'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <?php if ($blade['notes']): ?>
        <div class="detail-section">
            <h3>Notes</h3>
            <p><?= nl2br(e($blade['notes'])) ?></p>
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
                    <form action="<?= url('/blades/' . $blade['id'] . '/urls/' . $url['id'] . '/delete') ?>" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline" data-confirm="Delete this URL?">Delete</button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="text-muted">No URLs added yet.</p>
            <?php endif; ?>

            <form action="<?= url('/blades/' . $blade['id'] . '/urls') ?>" method="POST" class="mt-3">
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
        <!-- Last Used Date -->
        <div class="detail-section">
            <h3>Last Used</h3>
            <form action="<?= url('/blades/' . $blade['id'] . '/last-used') ?>" method="POST" class="d-flex gap-2 flex-wrap align-items-center">
                <?= csrf_field() ?>
                <input type="date" name="last_used_at" class="form-input" style="width: auto;" value="<?= $blade['last_used_at'] ? date('Y-m-d', strtotime($blade['last_used_at'])) : '' ?>">
                <button type="submit" class="btn btn-outline">Update</button>
                <?php if ($blade['last_used_at']): ?>
                <span class="text-muted">(<?= date('M j, Y', strtotime($blade['last_used_at'])) ?>)</span>
                <?php endif; ?>
            </form>
        </div>

        <!-- Razors Used With -->
        <div class="detail-section">
            <h3>Used With Razors</h3>
            <?php if (!empty($usage)): ?>
            <div class="mb-3">
                <?php foreach ($usage as $u): ?>
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2" style="padding: 0.5rem; background: var(--color-bg); border-radius: var(--radius-md);">
                    <a href="<?= url('/razors/' . $u['razor_id']) ?>" class="text-link"><?= e($u['razor_name']) ?></a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted">This blade hasn't been linked to any razors yet. Link it from a razor's detail page.</p>
            <?php endif; ?>
        </div>

        <!-- Images -->
        <div class="detail-section">
            <h3>Images</h3>
            <?php if (!empty($images)): ?>
            <div class="image-gallery">
                <?php foreach ($images as $image): ?>
                <div class="image-gallery-item <?= $image['filename'] === $blade['hero_image'] ? 'is-hero' : '' ?>">
                    <img src="<?= upload_url("users/{$_SESSION['user_id']}/blades/" . str_replace('.', '_thumb.', $image['filename'])) ?>"
                         alt="Additional image"
                         loading="lazy">
                    <?php if ($image['filename'] === $blade['hero_image']): ?>
                    <span class="hero-badge">Tile</span>
                    <?php endif; ?>
                    <div class="image-actions">
                        <?php if ($image['filename'] !== $blade['hero_image']): ?>
                        <form action="<?= url('/blades/' . $blade['id'] . '/images/' . $image['id'] . '/hero') ?>" method="POST" style="display:inline;">
                            <?= csrf_field() ?>
                            <button type="submit" class="hero-btn" title="Set as tile image">&#9733;</button>
                        </form>
                        <?php endif; ?>
                        <form action="<?= url('/blades/' . $blade['id'] . '/images/' . $image['id'] . '/delete') ?>" method="POST" style="display:inline;">
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

            <form action="<?= url('/blades/' . $blade['id'] . '/images') ?>" method="POST" enctype="multipart/form-data" class="mt-3">
                <?= csrf_field() ?>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <input type="file" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" class="form-input" style="flex: 1;" multiple required>
                    <button type="submit" class="btn btn-outline">Upload Images</button>
                </div>
                <p class="form-hint mt-1">Max 10MB per image. JPEG, PNG, GIF, or WebP. You can select multiple images.</p>
            </form>
        </div>
    </div>
</div>

<div class="mt-4">
    <a href="<?= url('/blades') ?>" class="btn btn-outline">&larr; Back to Blades</a>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
