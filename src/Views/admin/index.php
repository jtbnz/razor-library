<?php ob_start(); ?>

<div class="page-header">
    <h1>Administration</h1>
</div>

<!-- User Management Section -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="h4 mb-0">User Management</h2>
        <a href="<?= url('/admin/users/new') ?>" class="btn btn-primary btn-sm">Add User</a>
    </div>
    <div class="card-body">
        <?php if (!empty($users)): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Collection</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <strong><?= e($user['username']) ?></strong>
                            <?php if ($user['id'] === $_SESSION['user_id']): ?>
                            <span class="badge badge-outline">You</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($user['email'] ?? '-') ?></td>
                        <td>
                            <span title="Razors"><?= $user['razor_count'] ?> R</span>,
                            <span title="Blades"><?= $user['blade_count'] ?> B</span>,
                            <span title="Brushes"><?= $user['brush_count'] ?> Br</span>,
                            <span title="Other"><?= $user['other_count'] ?> O</span>
                        </td>
                        <td>
                            <?php if ($user['is_admin']): ?>
                            <span class="badge badge-primary">Admin</span>
                            <?php else: ?>
                            <span class="badge badge-secondary">User</span>
                            <?php endif; ?>
                        </td>
                        <td><?= format_date($user['created_at']) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= url('/admin/users/' . $user['id'] . '/edit') ?>" class="btn btn-sm btn-outline">Edit</a>
                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <form action="<?= url('/admin/users/' . $user['id'] . '/delete') ?>" method="POST" style="display: inline;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-danger" data-confirm="Are you sure you want to delete this user? Their data will be preserved but they won't be able to login.">Delete</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <h3>No Users</h3>
            <p>No users have been created yet.</p>
            <a href="<?= url('/admin/users/new') ?>" class="btn btn-primary">Add User</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Backup & Restore Section -->
<div class="card mb-4">
    <div class="card-header">
        <h2 class="h4 mb-0">Backup & Restore</h2>
    </div>
    <div class="card-body">
        <div class="grid-2">
            <div>
                <h3 class="h5">Create Backup</h3>
                <p class="text-muted">Create a backup of the database and all uploaded images.</p>
                <?php if ($lastBackup): ?>
                <p class="mb-2"><strong>Last backup:</strong> <?= e($lastBackup['date']) ?> (<?= number_format($lastBackup['size'] / 1024 / 1024, 2) ?> MB)</p>
                <?php else: ?>
                <p class="mb-2 text-muted">No backups yet.</p>
                <?php endif; ?>
                <form action="<?= url('/admin/backup') ?>" method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary">Create Backup Now</button>
                </form>
            </div>
            <div>
                <h3 class="h5">Restore from Backup</h3>
                <p class="text-muted">Restore the database and images from a previous backup.</p>
                <?php if (!empty($backups)): ?>
                <form action="<?= url('/admin/restore') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="form-group mb-2">
                        <select name="backup_file" class="form-select" required>
                            <option value="">Select a backup...</option>
                            <?php foreach ($backups as $backup): ?>
                            <option value="<?= e($backup['filename']) ?>">
                                <?= e($backup['date']) ?> (<?= number_format($backup['size'] / 1024 / 1024, 2) ?> MB)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline" data-confirm="Are you sure you want to restore from this backup? Current data will be replaced.">Restore Selected</button>
                </form>
                <?php else: ?>
                <p class="text-muted">No backups available to restore.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($backups)): ?>
        <hr class="my-4">
        <h3 class="h5">Available Backups</h3>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                    <tr>
                        <td><?= e($backup['date']) ?></td>
                        <td><?= number_format($backup['size'] / 1024 / 1024, 2) ?> MB</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= url('/admin/backup/' . urlencode($backup['filename']) . '/download') ?>" class="btn btn-sm btn-outline">Download</a>
                                <form action="<?= url('/admin/backup/' . urlencode($backup['filename']) . '/delete') ?>" method="POST" style="display: inline;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-danger" data-confirm="Are you sure you want to delete this backup?">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Danger Zone Section -->
<div class="card border-danger">
    <div class="card-header bg-danger text-white">
        <h2 class="h4 mb-0">Danger Zone</h2>
    </div>
    <div class="card-body">
        <h3 class="h5">Reset Database</h3>
        <p class="text-muted">This will completely reset the database, removing all users and data. This action cannot be undone.</p>
        <?php if ($lastBackup): ?>
        <div class="alert alert-info mb-3">
            <strong>Last backup:</strong> <?= e($lastBackup['date']) ?>
            <br>
            <small>We strongly recommend creating a fresh backup before resetting the database.</small>
        </div>
        <?php else: ?>
        <div class="alert alert-warning mb-3">
            <strong>Warning:</strong> No backups exist. We strongly recommend creating a backup before resetting the database.
        </div>
        <?php endif; ?>
        <form action="<?= url('/admin/reset-database') ?>" method="POST" id="reset-form">
            <?= csrf_field() ?>
            <div class="form-group mb-2">
                <label for="confirm_text" class="form-label">Type <strong>RESET DATABASE</strong> to confirm:</label>
                <input type="text" id="confirm_text" name="confirm_text" class="form-input" required autocomplete="off" placeholder="RESET DATABASE">
            </div>
            <div class="form-group mb-3">
                <label class="d-flex align-items-center gap-2">
                    <input type="checkbox" name="keep_uploads" value="1">
                    <span>Keep uploaded images (only reset database)</span>
                </label>
            </div>
            <button type="submit" class="btn btn-danger" data-confirm="ARE YOU ABSOLUTELY SURE? This will delete ALL data and cannot be undone!">Reset Database</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
