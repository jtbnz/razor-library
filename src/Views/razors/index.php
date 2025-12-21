<?php ob_start(); ?>

<div class="page-header">
    <h1>Razors</h1>
    <div class="page-header-actions">
        <a href="<?= url('/razors/new') ?>" class="btn btn-primary">Add Razor</a>
    </div>
</div>

<!-- Search and Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/razors') ?>" class="search-filter-form">
            <div class="search-row">
                <div class="search-input-wrapper">
                    <input type="text" name="q" class="form-input" placeholder="Search razors..."
                           value="<?= e($search ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search || $filters['country'] || $filters['year_from'] || $filters['year_to']): ?>
                <a href="<?= url('/razors') ?>" class="btn btn-outline">Clear</a>
                <?php endif; ?>
            </div>

            <details class="filter-details" <?= ($filters['country'] || $filters['year_from'] || $filters['year_to']) ? 'open' : '' ?>>
                <summary>Advanced Filters</summary>
                <div class="filter-row">
                    <?php if (!empty($countries)): ?>
                    <div class="filter-group">
                        <label for="country" class="form-label">Country</label>
                        <select id="country" name="country" class="form-select">
                            <option value="">All Countries</option>
                            <?php foreach ($countries as $country): ?>
                            <option value="<?= e($country) ?>" <?= $filters['country'] === $country ? 'selected' : '' ?>><?= e($country) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="filter-group">
                        <label for="year_from" class="form-label">Year From</label>
                        <input type="number" id="year_from" name="year_from" class="form-input" placeholder="e.g., 1950"
                               value="<?= e($filters['year_from'] ?? '') ?>" min="1800" max="<?= date('Y') ?>">
                    </div>
                    <div class="filter-group">
                        <label for="year_to" class="form-label">Year To</label>
                        <input type="number" id="year_to" name="year_to" class="form-input" placeholder="e.g., 1970"
                               value="<?= e($filters['year_to'] ?? '') ?>" min="1800" max="<?= date('Y') ?>">
                    </div>
                </div>
            </details>

            <input type="hidden" name="sort" value="<?= e($sort) ?>">
        </form>
    </div>
</div>

<div class="sort-controls">
    <label for="sort-select">Sort by:</label>
    <select id="sort-select" class="form-select sort-select">
        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
        <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
        <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Newest First</option>
        <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Oldest First</option>
        <option value="usage" <?= $sort === 'usage' ? 'selected' : '' ?>>Most Used</option>
        <option value="last_used" <?= $sort === 'last_used' ? 'selected' : '' ?>>Last Used</option>
        <option value="year_asc" <?= $sort === 'year_asc' ? 'selected' : '' ?>>Year (Oldest)</option>
        <option value="year_desc" <?= $sort === 'year_desc' ? 'selected' : '' ?>>Year (Newest)</option>
        <option value="country_asc" <?= $sort === 'country_asc' ? 'selected' : '' ?>>Country (A-Z)</option>
        <option value="country_desc" <?= $sort === 'country_desc' ? 'selected' : '' ?>>Country (Z-A)</option>
    </select>
</div>

<?php if (empty($razors)): ?>
<div class="empty-state">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
    </svg>
    <h3>No razors yet</h3>
    <p>Start building your collection by adding your first razor.</p>
    <a href="<?= url('/razors/new') ?>" class="btn btn-primary">Add Razor</a>
</div>
<?php else: ?>
<div class="tile-grid">
    <?php foreach ($razors as $razor): ?>
    <a href="<?= url('/razors/' . $razor['id']) ?>" class="card">
        <div class="card-image">
            <?php if ($razor['hero_image']): ?>
            <img src="<?= upload_url("users/{$_SESSION['user_id']}/razors/{$razor['hero_image']}") ?>"
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
        <div class="card-body">
            <h3 class="card-title"><?= e(trim(($razor['brand'] ?? '') . ' ' . $razor['name'])) ?></h3>
            <?php if ($razor['total_usage'] > 0): ?>
            <p class="card-text"><?= $razor['total_usage'] ?> blade uses</p>
            <?php endif; ?>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
