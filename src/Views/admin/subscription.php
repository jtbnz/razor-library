<?php ob_start(); ?>

<div class="page-header">
    <h1>Subscription Settings</h1>
    <div class="page-header-actions">
        <a href="<?= url('/admin') ?>" class="btn btn-outline">&larr; Back to Admin</a>
    </div>
</div>

<div class="grid-2">
    <div>
        <!-- Settings Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>Configuration</h3>
            </div>
            <div class="card-body">
                <form action="<?= url('/admin/subscription') ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="d-flex align-items-center gap-2">
                            <input type="checkbox" name="subscription_check_enabled" value="1"
                                   <?= $config['subscription_check_enabled'] ? 'checked' : '' ?>>
                            <span>Enable subscription enforcement</span>
                        </label>
                        <p class="form-hint">When enabled, expired users will be blocked from accessing the app.</p>
                    </div>

                    <div class="form-group">
                        <label for="trial_days" class="form-label">Trial Duration (days)</label>
                        <input type="number" id="trial_days" name="trial_days" class="form-input"
                               value="<?= e($config['trial_days'] ?? 7) ?>" min="1" max="365">
                        <p class="form-hint">Number of days for new user trials.</p>
                    </div>

                    <div class="form-group">
                        <label for="expired_message" class="form-label">Expired Page Message</label>
                        <textarea id="expired_message" name="expired_message" class="form-input" rows="3"><?= e($config['expired_message'] ?? '') ?></textarea>
                        <p class="form-hint">Custom message shown on the subscription expired page.</p>
                    </div>

                    <hr class="my-4">

                    <h4 class="mb-3">Buy Me a Coffee Integration</h4>

                    <div class="form-group">
                        <label for="bmac_access_token" class="form-label">API Access Token</label>
                        <input type="password" id="bmac_access_token" name="bmac_access_token" class="form-input"
                               placeholder="<?= !empty($config['bmac_access_token']) ? '••••••••' : 'Enter token' ?>">
                        <p class="form-hint">Leave blank to keep existing token. Get this from BMaC developer settings.</p>
                    </div>

                    <div class="form-group">
                        <label for="bmac_webhook_secret" class="form-label">Webhook Secret</label>
                        <input type="password" id="bmac_webhook_secret" name="bmac_webhook_secret" class="form-input"
                               placeholder="<?= !empty($config['bmac_webhook_secret']) ? '••••••••' : 'Enter secret' ?>">
                        <p class="form-hint">Used to verify incoming webhooks from BMaC.</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Webhook URL</label>
                        <input type="text" class="form-input" value="<?= e(config('APP_URL') . '/webhooks/bmac') ?>" readonly>
                        <p class="form-hint">Configure this URL in your BMaC webhook settings.</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div>
        <!-- Recent Events -->
        <div class="card">
            <div class="card-header">
                <h3>Recent Subscription Events</h3>
            </div>
            <div class="card-body">
                <?php if (empty($events)): ?>
                <p class="text-muted">No subscription events yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Event</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                            <tr>
                                <td style="white-space: nowrap;"><?= format_date($event['created_at']) ?></td>
                                <td><?= e($event['username'] ?? 'Unknown') ?></td>
                                <td>
                                    <span class="badge badge-<?= getEventBadgeClass($event['event_type']) ?>">
                                        <?= e(formatEventName($event['event_type'])) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Helper functions
function getEventBadgeClass(string $type): string {
    return match(true) {
        str_contains($type, 'activated') || str_contains($type, 'started') => 'success',
        str_contains($type, 'expired') || str_contains($type, 'cancelled') => 'danger',
        str_contains($type, 'lifetime') => 'primary',
        default => 'secondary',
    };
}

function formatEventName(string $type): string {
    return ucwords(str_replace('_', ' ', $type));
}
?>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
