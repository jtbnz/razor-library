<?php ob_start(); ?>

<div class="page-header">
    <h1>Add User</h1>
</div>

<form action="/admin/users" method="POST" class="form-container">
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
        <input type="text" id="username" name="username" class="form-input" value="<?= e(old('username')) ?>" required>
    </div>

    <div class="form-group">
        <label for="email" class="form-label">Email</label>
        <input type="email" id="email" name="email" class="form-input" value="<?= e(old('email')) ?>">
        <p class="form-hint">Used for password reset.</p>
    </div>

    <div class="form-group">
        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
        <input type="password" id="password" name="password" class="form-input" minlength="8" required>
        <p class="form-hint">Minimum 8 characters.</p>
    </div>

    <div class="form-group">
        <label class="form-checkbox">
            <input type="checkbox" name="is_admin" value="1" <?= old('is_admin') ? 'checked' : '' ?>>
            <span>Admin privileges</span>
        </label>
        <p class="form-hint">Admins can manage users and access all features.</p>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Create User</button>
        <a href="/admin" class="btn btn-outline">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
