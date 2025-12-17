<?php ob_start(); ?>

<div class="page-header">
    <h1><?= e($razor['name']) ?></h1>
    <div class="page-header-actions">
        <a href="/razors/<?= $razor['id'] ?>/edit" class="btn btn-outline">Edit</a>
        <form action="/razors/<?= $razor['id'] ?>/delete" method="POST" style="display: inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger" data-confirm="Are you sure you want to delete this razor?">Delete</button>
        </form>
    </div>
</div>

<!-- Hero Image -->
<div class="detail-hero">
    <?php if ($razor['hero_image']): ?>
    <img src="<?= upload_url("users/{$_SESSION['user_id']}/razors/{$razor['hero_image']}") ?>"
         alt="<?= e($razor['name']) ?>">
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
        <!-- Description -->
        <?php if ($razor['description']): ?>
        <div class="detail-section">
            <h3>Description</h3>
            <p><?= nl2br(e($razor['description'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <?php if ($razor['notes']): ?>
        <div class="detail-section">
            <h3>Notes</h3>
            <p><?= nl2br(e($razor['notes'])) ?></p>
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
                    <form action="/razors/<?= $razor['id'] ?>/urls/<?= $url['id'] ?>/delete" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline" data-confirm="Delete this URL?">Delete</button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="text-muted">No URLs added yet.</p>
            <?php endif; ?>

            <form action="/razors/<?= $razor['id'] ?>/urls" method="POST" class="mt-3">
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
        <!-- Blade Usage -->
        <div class="detail-section">
            <h3>Blade Usage</h3>
            <?php if (!empty($bladeUsage)): ?>
            <div class="mb-3">
                <?php foreach ($bladeUsage as $usage): ?>
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2" style="padding: 0.5rem; background: var(--color-bg); border-radius: var(--radius-md);">
                    <span><?= e($usage['blade_name']) ?></span>
                    <form action="/razors/<?= $razor['id'] ?>/usage" method="POST" class="usage-counter" id="blade-usage-form-<?= $usage['blade_id'] ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="blade_id" value="<?= $usage['blade_id'] ?>">
                        <input type="hidden" name="count" value="<?= $usage['count'] ?>">
                        <button type="button" class="decrement">-</button>
                        <span class="count" id="blade-usage-display-<?= $usage['blade_id'] ?>"><?= $usage['count'] ?></span>
                        <button type="button" class="increment">+</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($allBlades)): ?>
            <form action="/razors/<?= $razor['id'] ?>/usage" method="POST">
                <?= csrf_field() ?>
                <div class="d-flex gap-2 flex-wrap">
                    <select name="blade_id" class="form-select" style="flex: 1; min-width: 150px;" required>
                        <option value="">Select blade...</option>
                        <?php foreach ($allBlades as $blade): ?>
                        <option value="<?= $blade['id'] ?>"><?= e($blade['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="count" value="1" min="0" class="form-input" style="width: 80px;">
                    <button type="submit" class="btn btn-outline">Add Usage</button>
                </div>
            </form>
            <?php else: ?>
            <p class="text-muted">Add some blades first to track usage.</p>
            <a href="/blades/new" class="btn btn-sm btn-outline">Add Blade</a>
            <?php endif; ?>
        </div>

        <!-- Additional Images -->
        <div class="detail-section">
            <h3>Additional Images</h3>
            <?php if (!empty($images)): ?>
            <div class="image-gallery">
                <?php foreach ($images as $image): ?>
                <div class="image-gallery-item">
                    <img src="<?= upload_url("users/{$_SESSION['user_id']}/razors/" . str_replace('.', '_thumb.', $image['filename'])) ?>"
                         alt="Additional image"
                         loading="lazy">
                    <form action="/razors/<?= $razor['id'] ?>/images/<?= $image['id'] ?>/delete" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" class="delete-btn" data-confirm="Delete this image?">&times;</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form action="/razors/<?= $razor['id'] ?>/images" method="POST" enctype="multipart/form-data" class="mt-3">
                <?= csrf_field() ?>
                <div class="d-flex gap-2 flex-wrap">
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" class="form-input" style="flex: 1;" required>
                    <button type="submit" class="btn btn-outline">Upload Image</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="mt-4">
    <a href="/razors" class="btn btn-outline">&larr; Back to Razors</a>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
