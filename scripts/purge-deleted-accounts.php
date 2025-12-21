#!/usr/bin/env php
<?php
/**
 * Purge Deleted Accounts Script
 *
 * This script permanently deletes user accounts that have been scheduled
 * for deletion and have passed their 30-day recovery window.
 *
 * Usage:
 *   php scripts/purge-deleted-accounts.php [--dry-run]
 *
 * Options:
 *   --dry-run   Show what would be deleted without actually deleting
 *
 * Recommended: Run as a daily cron job
 *   0 0 * * * php /path/to/razor-library/scripts/purge-deleted-accounts.php
 */

define('BASE_PATH', dirname(__DIR__));

// Load configuration
$config = require BASE_PATH . '/config.php';
if (file_exists(BASE_PATH . '/config.local.php')) {
    $localConfig = require BASE_PATH . '/config.local.php';
    $config = array_merge($config, $localConfig);
}
$GLOBALS['config'] = $config;

function config(string $key, $default = null) {
    return $GLOBALS['config'][$key] ?? $default;
}

// Load database
require_once BASE_PATH . '/src/Helpers/Database.php';
Database::init();

// Parse command line options
$dryRun = in_array('--dry-run', $argv);

echo "=== Razor Library Account Purge ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
if ($dryRun) {
    echo "Mode: DRY RUN (no changes will be made)\n";
}
echo "\n";

// Find accounts scheduled for deletion where the scheduled date has passed
$accountsToDelete = Database::fetchAll(
    "SELECT id, username, email, deletion_requested_at, deletion_scheduled_at
     FROM users
     WHERE deletion_scheduled_at IS NOT NULL
       AND deletion_scheduled_at < CURRENT_TIMESTAMP
       AND deleted_at IS NULL"
);

if (empty($accountsToDelete)) {
    echo "No accounts to purge.\n";
    exit(0);
}

echo "Found " . count($accountsToDelete) . " account(s) to purge:\n\n";

$uploadPath = config('UPLOAD_PATH');
$purgedCount = 0;
$failedCount = 0;

foreach ($accountsToDelete as $user) {
    echo "Processing: {$user['username']} ({$user['email']})\n";
    echo "  - Deletion requested: {$user['deletion_requested_at']}\n";
    echo "  - Scheduled for: {$user['deletion_scheduled_at']}\n";

    if ($dryRun) {
        echo "  - [DRY RUN] Would delete user and all associated data\n";
        $purgedCount++;
        continue;
    }

    try {
        // Start a transaction-like approach (SQLite doesn't support real transactions via PDO the same way)
        // Delete user's images first
        deleteUserImages($user['id'], $uploadPath);

        // Delete all user data from database
        deleteUserData($user['id']);

        echo "  - Successfully purged\n";
        $purgedCount++;

    } catch (Exception $e) {
        echo "  - ERROR: " . $e->getMessage() . "\n";
        $failedCount++;
    }

    echo "\n";
}

echo "=== Summary ===\n";
echo "Purged: {$purgedCount}\n";
echo "Failed: {$failedCount}\n";

/**
 * Delete all images for a user
 */
function deleteUserImages(int $userId, string $uploadPath): void
{
    $userDir = $uploadPath . '/users/' . $userId;

    if (!is_dir($userDir)) {
        return;
    }

    // Recursively delete the user's upload directory
    deleteDirectory($userDir);
}

/**
 * Delete all user data from database
 */
function deleteUserData(int $userId): void
{
    // Delete in dependency order

    // Razor-related
    Database::query("DELETE FROM razor_images WHERE razor_id IN (SELECT id FROM razors WHERE user_id = ?)", [$userId]);
    Database::query("DELETE FROM razor_urls WHERE razor_id IN (SELECT id FROM razors WHERE user_id = ?)", [$userId]);
    Database::query("DELETE FROM blade_usage WHERE razor_id IN (SELECT id FROM razors WHERE user_id = ?)", [$userId]);
    Database::query("DELETE FROM razors WHERE user_id = ?", [$userId]);

    // Blade-related
    Database::query("DELETE FROM blade_images WHERE blade_id IN (SELECT id FROM blades WHERE user_id = ?)", [$userId]);
    Database::query("DELETE FROM blade_urls WHERE blade_id IN (SELECT id FROM blades WHERE user_id = ?)", [$userId]);
    Database::query("DELETE FROM blade_usage WHERE blade_id IN (SELECT id FROM blades WHERE user_id = ?)", [$userId]);
    Database::query("DELETE FROM blades WHERE user_id = ?", [$userId]);

    // Brush-related
    Database::query("DELETE FROM brush_images WHERE brush_id IN (SELECT id FROM brushes WHERE user_id = ?)", [$userId]);
    Database::query("DELETE FROM brush_urls WHERE brush_id IN (SELECT id FROM brushes WHERE user_id = ?)", [$userId]);
    Database::query("DELETE FROM brushes WHERE user_id = ?", [$userId]);

    // Other items-related
    Database::query("DELETE FROM other_item_images WHERE item_id IN (SELECT id FROM other_items WHERE user_id = ?)", [$userId]);
    Database::query("DELETE FROM other_item_urls WHERE item_id IN (SELECT id FROM other_items WHERE user_id = ?)", [$userId]);
    Database::query("DELETE FROM other_item_attributes WHERE item_id IN (SELECT id FROM other_items WHERE user_id = ?)", [$userId]);
    Database::query("DELETE FROM other_items WHERE user_id = ?", [$userId]);

    // API keys (if table exists)
    try {
        Database::query("DELETE FROM api_keys WHERE user_id = ?", [$userId]);
    } catch (Exception $e) {
        // Table might not exist yet
    }

    // Subscription events
    try {
        Database::query("DELETE FROM subscription_events WHERE user_id = ?", [$userId]);
    } catch (Exception $e) {
        // Table might not exist yet
    }

    // Activity log (set user_id to NULL to preserve logs but disassociate)
    try {
        Database::query("UPDATE activity_log SET user_id = NULL WHERE user_id = ?", [$userId]);
    } catch (Exception $e) {
        // Table might not exist yet
    }

    // Email log
    try {
        Database::query("DELETE FROM email_log WHERE user_id = ?", [$userId]);
    } catch (Exception $e) {
        // Table might not exist yet
    }

    // Finally, delete the user record (hard delete, not soft)
    Database::query("DELETE FROM users WHERE id = ?", [$userId]);
}

/**
 * Recursively delete a directory
 */
function deleteDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    rmdir($dir);
}
