<?php
$title = 'Login - Razor Library';
ob_start();
?>

<div class="auth-box">
    <div class="auth-box-header">
        <h1>Razor Library</h1>
        <p class="mb-0">Sign in to your account</p>
    </div>

    <div class="auth-box-body">
        <?php if (has_flash('success')): ?>
        <div class="alert alert-success"><?= get_flash('success') ?></div>
        <?php endif; ?>

        <?php if (has_flash('error')): ?>
        <div class="alert alert-error"><?= get_flash('error') ?></div>
        <?php endif; ?>

        <form action="<?= url('/login') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-input"
                       value="<?= e(old('email')) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>

        <div class="auth-links">
            <a href="<?= url('/forgot-password') ?>">Forgot your password?</a>
        </div>

        <div class="auth-divider">
            <span>or</span>
        </div>

        <div class="auth-links">
            <a href="<?= url('/request-account') ?>" class="btn btn-outline btn-block">Request an Account</a>
        </div>

        <p class="text-center text-muted mt-3" style="font-size: 0.875rem;">
            Support the project on <a href="https://buymeacoffee.com/jonjones" target="_blank" rel="noopener">Buy Me a Coffee</a>
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/auth.php';
?>
