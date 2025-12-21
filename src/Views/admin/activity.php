<?php ob_start(); ?>

<div class="page-header">
    <h1>Activity Log</h1>
    <div class="page-header-actions">
        <a href="<?= url('/admin') ?>" class="btn btn-outline">&larr; Back to Admin</a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/admin/activity') ?>" class="d-flex gap-3 flex-wrap align-items-end">
            <div class="form-group mb-0" style="min-width: 150px;">
                <label for="action" class="form-label">Action</label>
                <select id="action" name="action" class="form-select">
                    <option value="">All Actions</option>
                    <?php foreach ($actionTypes as $type): ?>
                    <option value="<?= e($type) ?>" <?= $filters['action'] === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-0" style="min-width: 150px;">
                <label for="user_id" class="form-label">User</label>
                <select id="user_id" name="user_id" class="form-select">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?= $user['id'] ?>" <?= $filters['user_id'] == $user['id'] ? 'selected' : '' ?>><?= e($user['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-0">
                <label for="date_from" class="form-label">From</label>
                <input type="date" id="date_from" name="date_from" class="form-input" value="<?= e($filters['date_from'] ?? '') ?>">
            </div>

            <div class="form-group mb-0">
                <label for="date_to" class="form-label">To</label>
                <input type="date" id="date_to" name="date_to" class="form-input" value="<?= e($filters['date_to'] ?? '') ?>">
            </div>

            <div class="form-group mb-0" style="min-width: 200px;">
                <label for="search" class="form-label">Search</label>
                <input type="text" id="search" name="search" class="form-input" placeholder="Search in details..." value="<?= e($filters['search'] ?? '') ?>">
            </div>

            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="<?= url('/admin/activity') ?>" class="btn btn-outline">Clear</a>
        </form>
    </div>
</div>

<!-- Activity Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($activities)): ?>
        <p class="text-muted text-center py-4">No activity found.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td style="white-space: nowrap;">
                            <?= date('Y-m-d H:i:s', strtotime($activity['created_at'])) ?>
                        </td>
                        <td>
                            <?php if ($activity['username']): ?>
                            <strong><?= e($activity['username']) ?></strong>
                            <?php else: ?>
                            <span class="text-muted">System</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= getActionBadgeClass($activity['action']) ?>">
                                <?= e(formatActionName($activity['action'])) ?>
                            </span>
                            <?php if ($activity['target_type']): ?>
                            <span class="text-muted"><?= e($activity['target_type']) ?><?= $activity['target_id'] ? " #{$activity['target_id']}" : '' ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($activity['details']): ?>
                            <code style="font-size: 0.85em;"><?= e(json_encode($activity['details'], JSON_UNESCAPED_SLASHES)) ?></code>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-family: monospace; font-size: 0.85em;">
                            <?= e($activity['ip_address'] ?? '-') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['totalPages'] > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-4">
            <p class="text-muted mb-0">
                Showing page <?= $pagination['page'] ?> of <?= $pagination['totalPages'] ?>
                (<?= $pagination['total'] ?> total entries)
            </p>
            <div class="d-flex gap-2">
                <?php if ($pagination['page'] > 1): ?>
                <a href="<?= url('/admin/activity?' . http_build_query(array_merge($filters, ['page' => $pagination['page'] - 1]))) ?>" class="btn btn-outline btn-sm">&larr; Previous</a>
                <?php endif; ?>
                <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                <a href="<?= url('/admin/activity?' . http_build_query(array_merge($filters, ['page' => $pagination['page'] + 1]))) ?>" class="btn btn-outline btn-sm">Next &rarr;</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Helper functions for formatting
function getActionBadgeClass(string $action): string {
    return match(true) {
        str_contains($action, 'failed') => 'danger',
        str_contains($action, 'login') => 'success',
        str_contains($action, 'password') => 'warning',
        str_contains($action, 'admin') => 'primary',
        str_contains($action, 'deleted') => 'danger',
        str_contains($action, 'created') => 'success',
        default => 'secondary',
    };
}

function formatActionName(string $action): string {
    return ucwords(str_replace('_', ' ', $action));
}
?>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
