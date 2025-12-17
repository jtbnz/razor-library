<?php ob_start(); ?>

<div class="page-header">
    <h1>Other Items</h1>
    <div class="page-header-actions">
        <a href="/other/new" class="btn btn-primary">Add Item</a>
    </div>
</div>

<!-- Category Tabs -->
<div class="tabs mb-3">
    <a href="/other?category=all&sort=<?= $sort ?>" class="tab <?= $currentCategory === 'all' ? 'active' : '' ?>">All</a>
    <?php foreach ($categories as $key => $label): ?>
    <a href="/other?category=<?= $key ?>&sort=<?= $sort ?>" class="tab <?= $currentCategory === $key ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<?php if (!empty($items)): ?>
<div class="sort-controls mb-3">
    <span class="text-muted">Sort by:</span>
    <a href="/other?category=<?= $currentCategory ?>&sort=name" class="btn btn-sm <?= $sort === 'name' ? 'btn-primary' : 'btn-outline' ?>">Name</a>
    <a href="/other?category=<?= $currentCategory ?>&sort=date" class="btn btn-sm <?= $sort === 'date' ? 'btn-primary' : 'btn-outline' ?>">Date Added</a>
</div>

<div class="tile-grid">
    <?php foreach ($items as $item): ?>
    <a href="/other/<?= $item['id'] ?>" class="tile-card">
        <div class="tile-image">
            <?php if ($item['hero_image']): ?>
            <img src="<?= upload_url("users/{$_SESSION['user_id']}/other/" . str_replace('.', '_thumb.', $item['hero_image'])) ?>"
                 alt="<?= e($item['name']) ?>"
                 loading="lazy">
            <?php else: ?>
            <div class="placeholder-image">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <?php endif; ?>
        </div>
        <div class="tile-content">
            <span class="badge badge-outline mb-1"><?= e($categories[$item['category']] ?? ucfirst($item['category'])) ?></span>
            <h3 class="tile-title"><?= e($item['name']) ?></h3>
            <?php if ($item['brand']): ?>
            <p class="tile-subtitle"><?= e($item['brand']) ?></p>
            <?php endif; ?>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
    <h3>No Items Yet</h3>
    <p>Start tracking your shaving accessories.</p>
    <a href="/other/new" class="btn btn-primary">Add Your First Item</a>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
