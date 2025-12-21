<?php
$title = 'Request Account - Razor Library';
ob_start();
?>

<div class="auth-box" style="max-width: 480px;">
    <div class="auth-box-header">
        <h1>Razor Library</h1>
        <p class="mb-0">Request an account</p>
    </div>

    <div class="auth-box-body">
        <?php if (has_flash('success')): ?>
        <div class="alert alert-success"><?= get_flash('success') ?></div>
        <?php endif; ?>

        <?php if (has_flash('error')): ?>
        <div class="alert alert-error"><?= get_flash('error') ?></div>
        <?php endif; ?>

        <form action="<?= url('/request-account') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-input"
                       value="<?= e(old('username')) ?>" required autofocus
                       pattern="[a-zA-Z0-9_-]+" minlength="3" maxlength="50"
                       title="Letters, numbers, underscores, and hyphens only">
                <small class="form-help">3-50 characters, letters, numbers, underscores, and hyphens only</small>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-input"
                       value="<?= e(old('email')) ?>" required>
                <small class="form-help">We'll send your login credentials to this email</small>
            </div>

            <div class="form-group">
                <label for="reason" class="form-label">Why do you want an account? <span class="text-muted">(optional)</span></label>
                <textarea id="reason" name="reason" class="form-input" rows="3"
                          placeholder="Tell us about your interest in wet shaving..."><?= e(old('reason')) ?></textarea>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="terms_accepted" required>
                    <span>I have read and accept the <a href="<?= url('/terms') ?>" target="_blank">Terms and Conditions</a></span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Submit Request</button>
        </form>

        <div class="auth-links">
            <a href="<?= url('/login') ?>">Already have an account? Sign in</a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/auth.php';
?>
