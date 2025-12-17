<?php ob_start(); ?>

<div class="page-header">
    <h1>User Management</h1>
    <div class="page-header-actions">
        <a href="/admin/users/new" class="btn btn-primary">Add User</a>
    </div>
</div>

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
                        <a href="/admin/users/<?= $user['id'] ?>/edit" class="btn btn-sm btn-outline">Edit</a>
                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                        <form action="/admin/users/<?= $user['id'] ?>/delete" method="POST" style="display: inline;">
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
    <a href="/admin/users/new" class="btn btn-primary">Add User</a>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
