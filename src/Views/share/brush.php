<?php ob_start(); ?>

<div class="page-header"><h1><?= e($brush['name']) ?></h1></div>

<div class="detail-hero">
    <?php if ($brush['hero_image']): ?>
    <img src="<?= upload_url("users/{$user['id']}/brushes/{$brush['hero_image']}") ?>" alt="<?= e($brush['name']) ?>">
    <?php else: ?>
    <div class="placeholder-image"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
    <?php endif; ?>
</div>

<div class="grid-2">
    <div>
        <div class="detail-section">
            <h3>Specifications</h3>
            <dl class="spec-list">
                <?php if ($brush['brand']): ?><div class="spec-item"><dt>Brand</dt><dd><?= e($brush['brand']) ?></dd></div><?php endif; ?>
                <?php if ($brush['bristle_type']): ?><div class="spec-item"><dt>Bristle Type</dt><dd><?= e($brush['bristle_type']) ?></dd></div><?php endif; ?>
                <?php if ($brush['knot_size']): ?><div class="spec-item"><dt>Knot Size</dt><dd><?= e($brush['knot_size']) ?></dd></div><?php endif; ?>
                <?php if ($brush['loft']): ?><div class="spec-item"><dt>Loft</dt><dd><?= e($brush['loft']) ?></dd></div><?php endif; ?>
                <?php if ($brush['handle_material']): ?><div class="spec-item"><dt>Handle Material</dt><dd><?= e($brush['handle_material']) ?></dd></div><?php endif; ?>
                <?php if ($brush['use_count'] > 0): ?><div class="spec-item"><dt>Total Uses</dt><dd><?= $brush['use_count'] ?></dd></div><?php endif; ?>
            </dl>
        </div>
        <?php if ($brush['description']): ?><div class="detail-section"><h3>Description</h3><p><?= nl2br(e($brush['description'])) ?></p></div><?php endif; ?>
        <?php if ($brush['notes']): ?><div class="detail-section"><h3>Notes</h3><p><?= nl2br(e($brush['notes'])) ?></p></div><?php endif; ?>
    </div>
    <div>
        <?php if (!empty($images)): ?>
        <div class="detail-section">
            <h3>More Images</h3>
            <div class="image-gallery">
                <?php foreach ($images as $image): ?>
                <div class="image-gallery-item"><img src="<?= upload_url("users/{$user['id']}/brushes/" . str_replace('.', '_thumb.', $image['filename'])) ?>" alt="Additional image" loading="lazy"></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-4"><a href="/share/<?= e($token) ?>/brushes" class="btn btn-outline">&larr; Back to Brushes</a></div>

<?php $content = ob_get_clean(); require BASE_PATH . '/src/Views/layouts/share.php'; ?>
