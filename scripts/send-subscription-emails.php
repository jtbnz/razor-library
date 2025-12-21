#!/usr/bin/env php
<?php
/**
 * Send subscription notification emails
 *
 * This script should be run daily via cron:
 * 0 9 * * * /usr/bin/php /path/to/razor-library/scripts/send-subscription-emails.php
 *
 * Sends:
 * - Trial expiry warnings (3 days before)
 * - Subscription renewal reminders (3 days before)
 * - Subscription expired notifications
 */

// Bootstrap the application
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config.php';
if (file_exists(BASE_PATH . '/config.local.php')) {
    require BASE_PATH . '/config.local.php';
}
require BASE_PATH . '/src/Helpers/functions.php';

// Load required classes
spl_autoload_register(function ($class) {
    $paths = [
        BASE_PATH . '/src/Helpers/' . $class . '.php',
        BASE_PATH . '/src/Controllers/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Initialize database
Database::init();

$verbose = in_array('-v', $argv) || in_array('--verbose', $argv);
$dryRun = in_array('--dry-run', $argv);

function log_message(string $message): void {
    global $verbose;
    if ($verbose) {
        echo date('[Y-m-d H:i:s] ') . $message . "\n";
    }
}

log_message("Starting subscription email job...");

if ($dryRun) {
    log_message("DRY RUN MODE - no emails will be sent");
}

// Check if subscription checking is enabled
$config = SubscriptionChecker::getConfig();
if (!$config['subscription_check_enabled']) {
    log_message("Subscription checking is disabled, exiting.");
    exit(0);
}

$emailsSent = 0;

// 1. Send trial expiry warnings (3 days before)
log_message("Checking for trial expiry warnings...");

$trialWarnings = Database::fetchAll(
    "SELECT id, username, email, subscription_expires_at
     FROM users
     WHERE subscription_status = 'trial'
     AND email IS NOT NULL
     AND deleted_at IS NULL
     AND DATE(subscription_expires_at) = DATE('now', '+3 days')"
);

foreach ($trialWarnings as $user) {
    if (Mailer::wasEmailSentToday($user['id'], 'trial_warning')) {
        log_message("  Skipping {$user['username']} - already sent today");
        continue;
    }

    log_message("  Sending trial warning to {$user['email']}");

    if (!$dryRun) {
        if (Mailer::sendTrialWarning($user['email'], $user['username'], $user['subscription_expires_at'], 3)) {
            Mailer::logEmailSent($user['id'], 'trial_warning');
            $emailsSent++;
        }
    } else {
        $emailsSent++;
    }
}

// 2. Send subscription renewal reminders (3 days before)
log_message("Checking for renewal reminders...");

$renewalReminders = Database::fetchAll(
    "SELECT id, username, email, subscription_expires_at
     FROM users
     WHERE subscription_status = 'active'
     AND email IS NOT NULL
     AND deleted_at IS NULL
     AND DATE(subscription_expires_at) = DATE('now', '+3 days')"
);

foreach ($renewalReminders as $user) {
    if (Mailer::wasEmailSentToday($user['id'], 'renewal_reminder')) {
        log_message("  Skipping {$user['username']} - already sent today");
        continue;
    }

    log_message("  Sending renewal reminder to {$user['email']}");

    if (!$dryRun) {
        if (Mailer::sendRenewalReminder($user['email'], $user['username'], $user['subscription_expires_at'], 3)) {
            Mailer::logEmailSent($user['id'], 'renewal_reminder');
            $emailsSent++;
        }
    } else {
        $emailsSent++;
    }
}

// 3. Check for newly expired subscriptions and send notifications
log_message("Checking for expired subscriptions...");

$expiredUsers = Database::fetchAll(
    "SELECT id, username, email, subscription_status, subscription_expires_at
     FROM users
     WHERE subscription_status IN ('trial', 'active')
     AND email IS NOT NULL
     AND deleted_at IS NULL
     AND subscription_expires_at < CURRENT_TIMESTAMP"
);

foreach ($expiredUsers as $user) {
    log_message("  Expiring subscription for {$user['username']}");

    if (!$dryRun) {
        // Update status to expired
        SubscriptionChecker::expireSubscription($user['id']);

        // Send notification if not already sent
        if (!Mailer::wasEmailSentToday($user['id'], 'subscription_expired')) {
            if (Mailer::sendSubscriptionExpired($user['email'], $user['username'])) {
                Mailer::logEmailSent($user['id'], 'subscription_expired');
                $emailsSent++;
            }
        }
    } else {
        $emailsSent++;
    }
}

log_message("Completed. Emails sent: {$emailsSent}");
echo "Emails sent: {$emailsSent}\n";
