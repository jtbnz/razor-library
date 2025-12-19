<?php ob_start(); ?>

<div class="share-hero text-center mb-4">
    <h1><?= e($user['username']) ?>'s Collection</h1>
    <p class="text-muted">A personal collection of wet shaving gear</p>
</div>

<!-- Stats -->
<div class="share-stats mb-4">
    <div class="stats-grid stats-grid-4">
        <a href="<?= url('/share/' . e($token) . '/razors') ?>" class="stat-item stat-item-link">
            <span class="stat-value"><?= $stats['razors'] ?></span>
            <span class="stat-label">Razors</span>
        </a>
        <a href="<?= url('/share/' . e($token) . '/blades') ?>" class="stat-item stat-item-link">
            <span class="stat-value"><?= $stats['blades'] ?></span>
            <span class="stat-label">Blades</span>
        </a>
        <a href="<?= url('/share/' . e($token) . '/brushes') ?>" class="stat-item stat-item-link">
            <span class="stat-value"><?= $stats['brushes'] ?></span>
            <span class="stat-label">Brushes</span>
        </a>
        <a href="<?= url('/share/' . e($token) . '/other') ?>" class="stat-item stat-item-link">
            <span class="stat-value"><?= $stats['other'] ?></span>
            <span class="stat-label">Other</span>
        </a>
    </div>
</div>

<?php if (!empty($recentRazors)): ?>
<section class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2>Razors</h2>
        <a href="<?= url('/share/' . e($token) . '/razors') ?>" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="tile-grid">
        <?php foreach ($recentRazors as $razor): ?>
        <a href="<?= url('/share/' . e($token) . '/razors/' . $razor['id']) ?>" class="tile-card">
            <div class="tile-image">
                <?php if ($razor['hero_image']): ?>
                <img src="<?= upload_url("users/{$user['id']}/razors/" . str_replace('.', '_thumb.', $razor['hero_image'])) ?>"
                     alt="<?= e($razor['name']) ?>"
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
                <h3 class="tile-title"><?= e($razor['name']) ?></h3>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($recentBlades)): ?>
<section class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2>Blades</h2>
        <a href="<?= url('/share/' . e($token) . '/blades') ?>" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="tile-grid">
        <?php foreach ($recentBlades as $blade): ?>
        <a href="<?= url('/share/' . e($token) . '/blades/' . $blade['id']) ?>" class="tile-card">
            <div class="tile-image">
                <?php if ($blade['hero_image']): ?>
                <img src="<?= upload_url("users/{$user['id']}/blades/" . str_replace('.', '_thumb.', $blade['hero_image'])) ?>"
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
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/share.php';
?>
