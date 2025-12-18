#!/usr/bin/env php
<?php
/**
 * Razor Library - Database Reset Script
 *
 * This script completely resets the database by:
 * 1. Deleting the existing database file
 * 2. Deleting all uploaded files
 * 3. Re-running migrations on next request
 *
 * Usage: php scripts/reset-database.php [--keep-uploads]
 *
 * Options:
 *   --keep-uploads    Keep uploaded images (only reset database)
 *   --force           Skip confirmation prompt
 */

// Set base path
define('BASE_PATH', dirname(__DIR__));

// Parse command line arguments
$keepUploads = in_array('--keep-uploads', $argv);
$force = in_array('--force', $argv);

// Load configuration
$config = require BASE_PATH . '/config.php';
if (file_exists(BASE_PATH . '/config.local.php')) {
    $localConfig = require BASE_PATH . '/config.local.php';
    $config = array_merge($config, $localConfig);
}

$dbPath = $config['DB_PATH'];
$uploadPath = $config['UPLOAD_PATH'];

echo "Razor Library - Database Reset Script\n";
echo "======================================\n\n";

echo "This will:\n";
echo "  - Delete the database at: {$dbPath}\n";
if (!$keepUploads) {
    echo "  - Delete all uploaded files in: {$uploadPath}\n";
} else {
    echo "  - Keep uploaded files (--keep-uploads specified)\n";
}
echo "\n";

// Confirmation prompt
if (!$force) {
    echo "Are you sure you want to continue? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);

    if (strtolower($line) !== 'yes') {
        echo "\nOperation cancelled.\n";
        exit(0);
    }
}

echo "\n";

// Delete database file
if (file_exists($dbPath)) {
    if (unlink($dbPath)) {
        echo "✓ Deleted database file\n";
    } else {
        echo "✗ Failed to delete database file\n";
        exit(1);
    }
} else {
    echo "- Database file does not exist (skipped)\n";
}

// Delete uploads directory contents
if (!$keepUploads && is_dir($uploadPath)) {
    $deleted = deleteDirectory($uploadPath, false);
    if ($deleted) {
        echo "✓ Deleted uploaded files\n";
    } else {
        echo "✗ Failed to delete some uploaded files\n";
    }
} else {
    echo "- Uploads directory skipped\n";
}

echo "\n";
echo "Database reset complete!\n";
echo "The database will be recreated on the next request.\n";
echo "\nTo create a new admin user, visit the application and register,\n";
echo "then use the following command to promote the user to admin:\n";
echo "\n  php scripts/make-admin.php <email>\n\n";

/**
 * Recursively delete directory contents
 */
function deleteDirectory(string $dir, bool $removeDir = true): bool
{
    if (!is_dir($dir)) {
        return true;
    }

    $items = array_diff(scandir($dir), ['.', '..']);

    foreach ($items as $item) {
        $path = $dir . '/' . $item;

        if (is_dir($path)) {
            deleteDirectory($path, true);
        } else {
            unlink($path);
        }
    }

    if ($removeDir) {
        return rmdir($dir);
    }

    return true;
}
