<?php ob_start(); ?>

<div class="page-header"><h1>Brushes</h1></div>

<?php if (!empty($brushes)): ?>
<div class="tile-grid">
    <?php foreach ($brushes as $brush): ?>
    <a href="/share/<?= e($token) ?>/brushes/<?= $brush['id'] ?>" class="tile-card">
        <div class="tile-image">
            <?php if ($brush['hero_image']): ?>
            <img src="<?= upload_url("users/{$user['id']}/brushes/" . str_replace('.', '_thumb.', $brush['hero_image'])) ?>" alt="<?= e($brush['name']) ?>" loading="lazy">
            <?php else: ?>
            <div class="placeholder-image"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
            <?php endif; ?>
        </div>
        <div class="tile-content">
            <h3 class="tile-title"><?= e($brush['name']) ?></h3>
            <?php if ($brush['bristle_type']): ?><p class="tile-subtitle"><?= e($brush['bristle_type']) ?></p><?php endif; ?>
            <?php if ($brush['use_count'] > 0): ?><span class="badge badge-secondary"><?= $brush['use_count'] ?> uses</span><?php endif; ?>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state"><h3>No Brushes</h3><p>This collection doesn't have any brushes yet.</p></div>
<?php endif; ?>

<div class="mt-4"><a href="/share/<?= e($token) ?>" class="btn btn-outline">&larr; Back to Collection</a></div>

<?php $content = ob_get_clean(); require BASE_PATH . '/src/Views/layouts/share.php'; ?>
