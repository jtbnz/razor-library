<?php ob_start(); ?>

<div class="page-header">
    <h1>My Blades</h1>
    <div class="page-header-actions">
        <a href="<?= url('/blades/new') ?>" class="btn btn-primary">Add Blade</a>
    </div>
</div>

<?php if (!empty($blades)): ?>
<div class="sort-controls mb-3">
    <span class="text-muted">Sort by:</span>
    <a href="<?= url('/blades?sort=name') ?>" class="btn btn-sm <?= $sort === 'name' ? 'btn-primary' : 'btn-outline' ?>">Name</a>
    <a href="<?= url('/blades?sort=date') ?>" class="btn btn-sm <?= $sort === 'date' ? 'btn-primary' : 'btn-outline' ?>">Date Added</a>
    <a href="<?= url('/blades?sort=usage') ?>" class="btn btn-sm <?= $sort === 'usage' ? 'btn-primary' : 'btn-outline' ?>">Most Used</a>
    <a href="<?= url('/blades?sort=last_used') ?>" class="btn btn-sm <?= $sort === 'last_used' ? 'btn-primary' : 'btn-outline' ?>">Last Used</a>
</div>

<div class="tile-grid">
    <?php foreach ($blades as $blade): ?>
    <a href="<?= url('/blades/' . $blade['id']) ?>" class="tile-card">
        <div class="tile-image">
            <?php if ($blade['hero_image']): ?>
            <img src="<?= upload_url("users/{$_SESSION['user_id']}/blades/" . str_replace('.', '_thumb.', $blade['hero_image'])) ?>"
                 alt="<?= e($blade['name']) ?>"
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
            <h3 class="tile-title"><?= e($blade['name']) ?></h3>
            <?php if ($blade['brand']): ?>
            <p class="tile-subtitle"><?= e($blade['brand']) ?></p>
            <?php endif; ?>
            <?php if ($blade['total_usage'] > 0): ?>
            <span class="badge badge-secondary"><?= $blade['total_usage'] ?> uses</span>
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
    <h3>No Blades Yet</h3>
    <p>Start building your blade collection.</p>
    <a href="<?= url('/blades/new') ?>" class="btn btn-primary">Add Your First Blade</a>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
