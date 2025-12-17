<?php
$title = 'Setup - Razor Library';
ob_start();
?>

<div class="auth-box">
    <div class="auth-box-header">
        <h1>Razor Library</h1>
        <p class="mb-0">Create your admin account</p>
    </div>

    <div class="auth-box-body">
        <?php if (has_flash('error')): ?>
        <div class="alert alert-error"><?= get_flash('error') ?></div>
        <?php endif; ?>

        <form action="/setup" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-input"
                       value="<?= e(old('username')) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-input"
                       value="<?= e(old('email')) ?>" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-input" required>
                <span class="form-hint">Minimum <?= config('PASSWORD_MIN_LENGTH') ?> characters</span>
            </div>

            <div class="form-group">
                <label for="password_confirm" class="form-label">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-input" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/auth.php';
?>
