<?php ob_start(); ?>

<div class="page-header"><h1><?= e($item['name']) ?></h1></div>
<div class="mb-3"><span class="badge badge-primary"><?= e($categories[$item['category']] ?? ucfirst($item['category'])) ?></span></div>

<div class="detail-hero">
    <?php if ($item['hero_image']): ?>
    <img src="<?= upload_url("users/{$user['id']}/other/{$item['hero_image']}") ?>" alt="<?= e($item['name']) ?>">
    <?php else: ?>
    <div class="placeholder-image"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
    <?php endif; ?>
</div>

<div class="grid-2">
    <div>
        <div class="detail-section">
            <h3>Details</h3>
            <dl class="spec-list">
                <?php if ($item['brand']): ?><div class="spec-item"><dt>Brand</dt><dd><?= e($item['brand']) ?></dd></div><?php endif; ?>
                <?php foreach ($attributes as $attr): ?>
                <div class="spec-item"><dt><?= e(ucwords(str_replace('_', ' ', $attr['attribute_name']))) ?></dt><dd><?= e($attr['attribute_value']) ?></dd></div>
                <?php endforeach; ?>
            </dl>
        </div>
        <?php if ($item['scent_notes']): ?><div class="detail-section"><h3>Scent Notes</h3><p><?= nl2br(e($item['scent_notes'])) ?></p></div><?php endif; ?>
        <?php if ($item['description']): ?><div class="detail-section"><h3>Description</h3><p><?= nl2br(e($item['description'])) ?></p></div><?php endif; ?>
        <?php if ($item['notes']): ?><div class="detail-section"><h3>Notes</h3><p><?= nl2br(e($item['notes'])) ?></p></div><?php endif; ?>
    </div>
    <div>
        <?php if (!empty($images)): ?>
        <div class="detail-section">
            <h3>More Images</h3>
            <div class="image-gallery">
                <?php foreach ($images as $image): ?>
                <div class="image-gallery-item"><img src="<?= upload_url("users/{$user['id']}/other/" . str_replace('.', '_thumb.', $image['filename'])) ?>" alt="Additional image" loading="lazy"></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-4"><a href="<?= url('/share/' . e($token) . '/other') ?>" class="btn btn-outline">&larr; Back to Other Items</a></div>

<?php $content = ob_get_clean(); require BASE_PATH . '/src/Views/layouts/share.php'; ?>
