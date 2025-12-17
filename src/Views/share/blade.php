<?php ob_start(); ?>

<div class="page-header">
    <h1><?= e($blade['name']) ?></h1>
</div>

<div class="detail-hero">
    <?php if ($blade['hero_image']): ?>
    <img src="<?= upload_url("users/{$user['id']}/blades/{$blade['hero_image']}") ?>" alt="<?= e($blade['name']) ?>">
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
        <?php if ($blade['brand']): ?>
        <div class="detail-section"><h3>Brand</h3><p><?= e($blade['brand']) ?></p></div>
        <?php endif; ?>
        <?php if ($blade['description']): ?>
        <div class="detail-section"><h3>Description</h3><p><?= nl2br(e($blade['description'])) ?></p></div>
        <?php endif; ?>
        <?php if ($blade['notes']): ?>
        <div class="detail-section"><h3>Notes</h3><p><?= nl2br(e($blade['notes'])) ?></p></div>
        <?php endif; ?>
    </div>
    <div>
        <div class="detail-section">
            <h3>Usage Statistics</h3>
            <p class="text-lg mb-3"><strong><?= $totalUsage ?></strong> total uses</p>
            <?php if (!empty($usage)): ?>
            <div class="usage-breakdown">
                <?php foreach ($usage as $u): ?>
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2" style="padding: 0.5rem; background: var(--color-bg); border-radius: var(--radius-md);">
                    <span><?= e($u['razor_name']) ?></span>
                    <span class="badge badge-secondary"><?= $u['count'] ?> uses</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($images)): ?>
        <div class="detail-section">
            <h3>More Images</h3>
            <div class="image-gallery">
                <?php foreach ($images as $image): ?>
                <div class="image-gallery-item">
                    <img src="<?= upload_url("users/{$user['id']}/blades/" . str_replace('.', '_thumb.', $image['filename'])) ?>" alt="Additional image" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-4"><a href="/share/<?= e($token) ?>/blades" class="btn btn-outline">&larr; Back to Blades</a></div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/share.php';
?>
