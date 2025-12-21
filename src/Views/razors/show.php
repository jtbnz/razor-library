<?php ob_start(); ?>

<div class="page-header">
    <h1><?= e(trim(($razor['year_manufactured'] ? $razor['year_manufactured'] . ' ' : '') . ($razor['brand'] ?? '') . ' ' . $razor['name'])) ?></h1>
    <div class="page-header-actions">
        <a href="<?= url('/razors/' . $razor['id'] . '/edit') ?>" class="btn btn-outline">Edit</a>
        <form action="<?= url('/razors/' . $razor['id'] . '/delete') ?>" method="POST" style="display: inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger" data-confirm="Are you sure you want to delete this razor?">Delete</button>
        </form>
    </div>
</div>

<!-- Hero Image -->
<div class="detail-hero">
    <?php if ($razor['hero_image']): ?>
    <img src="<?= upload_url("users/{$_SESSION['user_id']}/razors/{$razor['hero_image']}") ?>"
         alt="<?= e($razor['name']) ?>"
         class="clickable-image"
         data-gallery="razor-<?= $razor['id'] ?>"
         data-full="<?= upload_url("users/{$_SESSION['user_id']}/razors/{$razor['hero_image']}") ?>">
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
        <!-- Year and Country -->
        <?php if ($razor['year_manufactured'] || $razor['country_manufactured']): ?>
        <div class="detail-section">
            <h3>Manufacturing Details</h3>
            <?php if ($razor['year_manufactured']): ?>
            <p><strong>Year:</strong> <?= e($razor['year_manufactured']) ?></p>
            <?php endif; ?>
            <?php if ($razor['country_manufactured']): ?>
            <p><strong>Country:</strong> <?= e($razor['country_manufactured']) ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

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

        <!-- Related URLs -->
        <div class="detail-section">
            <h3>Related URLs</h3>
            <?php if (!empty($urls)): ?>
            <ul class="url-list">
                <?php foreach ($urls as $url): ?>
                <li>
                    <div class="url-info">
                        <a href="<?= e($url['url']) ?>" target="_blank" rel="noopener" class="url-link"><?= e($url['url']) ?></a>
                        <?php if ($url['description']): ?>
                        <span class="url-desc"><?= e($url['description']) ?></span>
                        <?php endif; ?>
                    </div>
                    <form action="<?= url('/razors/' . $razor['id'] . '/urls/' . $url['id'] . '/delete') ?>" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline" data-confirm="Delete this URL?">Delete</button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="text-muted">No URLs added yet.</p>
            <?php endif; ?>

            <form action="<?= url('/razors/' . $razor['id'] . '/urls') ?>" method="POST" class="mt-3">
                <?= csrf_field() ?>
                <div class="d-flex gap-2 flex-wrap">
                    <input type="url" name="url" placeholder="https://example.com" class="form-input" style="flex: 2; min-width: 200px;" required>
                    <input type="text" name="url_description" placeholder="Description (optional)" class="form-input" style="flex: 1; min-width: 150px;">
                    <button type="submit" class="btn btn-outline">Add URL</button>
                </div>
            </form>
        </div>
    </div>

    <div>
        <!-- Usage Tracking -->
        <div class="detail-section">
            <h3>Usage</h3>
            <form action="<?= url('/razors/' . $razor['id'] . '/usage') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="d-flex gap-3 flex-wrap align-items-end mb-3">
                    <div>
                        <label for="use_count" class="form-label">Use Count</label>
                        <input type="number" id="use_count" name="use_count" class="form-input" style="width: 100px;" value="<?= (int)($razor['use_count'] ?? 0) ?>" min="0">
                    </div>
                    <div>
                        <label for="last_used_at" class="form-label">Last Used</label>
                        <input type="date" id="last_used_at" name="last_used_at" class="form-input" style="width: auto;" value="<?= $razor['last_used_at'] ? date('Y-m-d', strtotime($razor['last_used_at'])) : '' ?>">
                    </div>
                    <button type="submit" class="btn btn-outline">Update</button>
                </div>
                <?php if ($razor['last_used_at']): ?>
                <p class="text-muted">Last used: <?= date('M j, Y', strtotime($razor['last_used_at'])) ?></p>
                <?php endif; ?>
            </form>
        </div>

        <!-- Blades Used -->
        <div class="detail-section">
            <h3>Blades Used</h3>
            <?php if (!empty($bladeUsage)): ?>
            <div class="mb-3">
                <?php foreach ($bladeUsage as $usage): ?>
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2" style="padding: 0.5rem; background: var(--color-bg); border-radius: var(--radius-md);">
                    <a href="<?= url('/blades/' . $usage['blade_id']) ?>" class="text-link"><?= e($usage['blade_name']) ?></a>
                    <form action="<?= url('/razors/' . $razor['id'] . '/blades/' . $usage['blade_id'] . '/remove') ?>" method="POST" style="display: inline;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline" data-confirm="Remove this blade?">Remove</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted mb-3">No blades linked to this razor yet.</p>
            <?php endif; ?>

            <?php
            // Filter out blades already linked
            $linkedBladeIds = array_column($bladeUsage, 'blade_id');
            $availableBlades = array_filter($allBlades, fn($b) => !in_array($b['id'], $linkedBladeIds));
            ?>
            <?php if (!empty($availableBlades)): ?>
            <form action="<?= url('/razors/' . $razor['id'] . '/blades') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="d-flex gap-2 flex-wrap">
                    <select name="blade_id" class="form-select" style="flex: 1; min-width: 150px;" required>
                        <option value="">Select blade...</option>
                        <?php foreach ($availableBlades as $blade): ?>
                        <option value="<?= $blade['id'] ?>"><?= e($blade['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-outline">Add Blade</button>
                </div>
            </form>
            <?php elseif (empty($allBlades)): ?>
            <p class="text-muted">Add some blades first.</p>
            <a href="<?= url('/blades/new') ?>" class="btn btn-sm btn-outline">Add Blade</a>
            <?php else: ?>
            <p class="text-muted">All your blades are already linked to this razor.</p>
            <?php endif; ?>
        </div>

        <!-- Images -->
        <div class="detail-section">
            <h3>Images</h3>
            <?php if (!empty($images)): ?>
            <div class="image-gallery">
                <?php foreach ($images as $image): ?>
                <div class="image-gallery-item <?= $image['filename'] === $razor['hero_image'] ? 'is-hero' : '' ?>">
                    <img src="<?= upload_url("users/{$_SESSION['user_id']}/razors/" . str_replace('.', '_thumb.', $image['filename'])) ?>"
                         alt="Additional image"
                         loading="lazy"
                         class="clickable-image"
                         data-gallery="razor-<?= $razor['id'] ?>"
                         data-full="<?= upload_url("users/{$_SESSION['user_id']}/razors/{$image['filename']}") ?>">
                    <?php if ($image['filename'] === $razor['hero_image']): ?>
                    <span class="hero-badge">Tile</span>
                    <?php endif; ?>
                    <div class="image-actions">
                        <?php if ($image['filename'] !== $razor['hero_image']): ?>
                        <form action="<?= url('/razors/' . $razor['id'] . '/images/' . $image['id'] . '/hero') ?>" method="POST" style="display:inline;">
                            <?= csrf_field() ?>
                            <button type="submit" class="hero-btn" title="Set as tile image">&#9733;</button>
                        </form>
                        <?php endif; ?>
                        <form action="<?= url('/razors/' . $razor['id'] . '/images/' . $image['id'] . '/delete') ?>" method="POST" style="display:inline;">
                            <?= csrf_field() ?>
                            <button type="submit" class="delete-btn" data-confirm="Delete this image?">&times;</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted">No images uploaded yet.</p>
            <?php endif; ?>

            <form action="<?= url('/razors/' . $razor['id'] . '/images') ?>" method="POST" enctype="multipart/form-data" class="mt-3">
                <?= csrf_field() ?>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <input type="file" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" class="form-input" style="flex: 1;" multiple required>
                    <button type="submit" class="btn btn-outline">Upload Images</button>
                </div>
                <p class="form-hint mt-1">Max 10MB per image. JPEG, PNG, GIF, or WebP. You can select multiple images.</p>
            </form>
        </div>
    </div>
</div>

<div class="mt-4">
    <a href="<?= url('/razors') ?>" class="btn btn-outline">&larr; Back to Razors</a>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
