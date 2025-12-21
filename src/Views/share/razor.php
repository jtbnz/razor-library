<?php ob_start(); ?>

<div class="page-header">
    <h1><?= e(trim(($razor['year_manufactured'] ? $razor['year_manufactured'] . ' ' : '') . ($razor['brand'] ?? '') . ' ' . $razor['name'])) ?></h1>
</div>

<!-- Hero Image -->
<div class="detail-hero">
    <?php if ($razor['hero_image']): ?>
    <img src="<?= upload_url("users/{$user['id']}/razors/{$razor['hero_image']}") ?>"
         alt="<?= e($razor['name']) ?>"
         class="clickable-image"
         data-gallery="razor-<?= $razor['id'] ?>"
         data-full="<?= upload_url("users/{$user['id']}/razors/{$razor['hero_image']}") ?>">
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
    </div>

    <div>
        <!-- Blade Usage -->
        <?php if (!empty($bladeUsage)): ?>
        <div class="detail-section">
            <h3>Blade Usage</h3>
            <div class="usage-breakdown">
                <?php foreach ($bladeUsage as $usage): ?>
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2" style="padding: 0.5rem; background: var(--color-bg); border-radius: var(--radius-md);">
                    <span><?= e($usage['blade_name']) ?></span>
                    <span class="badge badge-secondary"><?= $usage['count'] ?> uses</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Additional Images -->
        <?php if (!empty($images)): ?>
        <div class="detail-section">
            <h3>More Images</h3>
            <div class="image-gallery">
                <?php foreach ($images as $image): ?>
                <div class="image-gallery-item">
                    <img src="<?= upload_url("users/{$user['id']}/razors/" . str_replace('.', '_thumb.', $image['filename'])) ?>"
                         alt="Additional image"
                         loading="lazy"
                         class="clickable-image"
                         data-gallery="razor-<?= $razor['id'] ?>"
                         data-full="<?= upload_url("users/{$user['id']}/razors/{$image['filename']}") ?>">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-4">
    <a href="<?= url('/share/' . e($token) . '/razors') ?>" class="btn btn-outline">&larr; Back to Razors</a>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/share.php';
?>
