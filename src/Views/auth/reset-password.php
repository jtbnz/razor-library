<?php
$title = 'Reset Password - Razor Library';
ob_start();
?>

<div class="auth-box">
    <div class="auth-box-header">
        <h1>Razor Library</h1>
        <p class="mb-0">Set a new password</p>
    </div>

    <div class="auth-box-body">
        <?php if (has_flash('error')): ?>
        <div class="alert alert-error"><?= get_flash('error') ?></div>
        <?php endif; ?>

        <form action="<?= url('/reset-password/' . e($token)) ?>" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="password" class="form-label">New Password</label>
                <input type="password" id="password" name="password" class="form-input" required autofocus>
                <span class="form-hint">Minimum <?= config('PASSWORD_MIN_LENGTH') ?> characters</span>
            </div>

            <div class="form-group">
                <label for="password_confirm" class="form-label">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-input" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
        </form>

        <div class="auth-links">
            <a href="<?= url('/login') ?>">Back to login</a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/auth.php';
?>
