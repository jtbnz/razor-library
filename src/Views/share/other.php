<?php ob_start(); ?>

<div class="page-header"><h1>Other Items</h1></div>

<div class="tabs mb-3">
    <a href="<?= url('/share/' . e($token) . '/other?category=all') ?>" class="tab <?= $currentCategory === 'all' ? 'active' : '' ?>">All</a>
    <?php foreach ($categories as $key => $label): ?>
    <a href="<?= url('/share/' . e($token) . '/other?category=' . $key) ?>" class="tab <?= $currentCategory === $key ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<?php if (!empty($items)): ?>
<div class="tile-grid">
    <?php foreach ($items as $item): ?>
    <a href="<?= url('/share/' . e($token) . '/other/' . $item['id']) ?>" class="tile-card">
        <div class="tile-image">
            <?php if ($item['hero_image']): ?>
            <img src="<?= upload_url("users/{$user['id']}/other/" . str_replace('.', '_thumb.', $item['hero_image'])) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
            <?php else: ?>
            <div class="placeholder-image"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
            <?php endif; ?>
        </div>
        <div class="tile-content">
            <span class="badge badge-outline mb-1"><?= e($categories[$item['category']] ?? ucfirst($item['category'])) ?></span>
            <h3 class="tile-title"><?= e($item['name']) ?></h3>
            <?php if ($item['brand']): ?><p class="tile-subtitle"><?= e($item['brand']) ?></p><?php endif; ?>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state"><h3>No Items</h3><p>This collection doesn't have any items in this category yet.</p></div>
<?php endif; ?>

<div class="mt-4"><a href="<?= url('/share/' . e($token)) ?>" class="btn btn-outline">&larr; Back to Collection</a></div>

<?php $content = ob_get_clean(); require BASE_PATH . '/src/Views/layouts/share.php'; ?>
