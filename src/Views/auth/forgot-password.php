<?php
$title = 'Forgot Password - Razor Library';
ob_start();
?>

<div class="auth-box">
    <div class="auth-box-header">
        <h1>Razor Library</h1>
        <p class="mb-0">Reset your password</p>
    </div>

    <div class="auth-box-body">
        <?php if (has_flash('success')): ?>
        <div class="alert alert-success"><?= get_flash('success') ?></div>
        <?php endif; ?>

        <?php if (has_flash('error')): ?>
        <div class="alert alert-error"><?= get_flash('error') ?></div>
        <?php endif; ?>

        <p class="text-muted mb-3">Enter your email address and we'll send you a link to reset your password.</p>

        <form action="<?= url('/forgot-password') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-input" required autofocus>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
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
