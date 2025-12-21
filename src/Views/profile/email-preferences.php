<?php ob_start(); ?>

<div class="page-header">
    <h1>Email Preferences</h1>
    <div class="page-header-actions">
        <a href="<?= url('/profile') ?>" class="btn btn-outline">Back to Profile</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Notification Settings</h3>
    </div>
    <div class="card-body">
        <form action="<?= url('/profile/email-preferences') ?>" method="POST">
            <?= csrf_field() ?>

            <p class="text-muted mb-4">Choose which emails you'd like to receive from Razor Library. Security-related emails (password resets, account changes) will always be sent.</p>

            <div class="form-group mb-3">
                <label class="checkbox-label">
                    <input type="checkbox" name="email_trial_warnings" value="1"
                           <?= $preferences['email_trial_warnings'] ? 'checked' : '' ?>>
                    <span>
                        <strong>Trial Expiration Warnings</strong>
                        <br><small class="text-muted">Get notified when your trial is about to expire</small>
                    </span>
                </label>
            </div>

            <div class="form-group mb-3">
                <label class="checkbox-label">
                    <input type="checkbox" name="email_renewal_reminders" value="1"
                           <?= $preferences['email_renewal_reminders'] ? 'checked' : '' ?>>
                    <span>
                        <strong>Subscription Renewal Reminders</strong>
                        <br><small class="text-muted">Get reminders before your subscription renews</small>
                    </span>
                </label>
            </div>

            <div class="form-group mb-3">
                <label class="checkbox-label">
                    <input type="checkbox" name="email_account_updates" value="1"
                           <?= $preferences['email_account_updates'] ? 'checked' : '' ?>>
                    <span>
                        <strong>Account Updates</strong>
                        <br><small class="text-muted">Get notified about important account changes and new features</small>
                    </span>
                </label>
            </div>

            <div class="form-group mb-4">
                <label class="checkbox-label">
                    <input type="checkbox" name="email_marketing" value="1"
                           <?= $preferences['email_marketing'] ? 'checked' : '' ?>>
                    <span>
                        <strong>Marketing & Tips</strong>
                        <br><small class="text-muted">Receive occasional tips about wet shaving and product recommendations</small>
                    </span>
                </label>
            </div>

            <div class="alert alert-info mb-4">
                <strong>Note:</strong> You will always receive emails related to security (password resets, email changes, account deletion) regardless of these preferences.
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Preferences</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
