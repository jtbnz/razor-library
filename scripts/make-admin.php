#!/usr/bin/env php
<?php
/**
 * Razor Library - Make User Admin Script
 *
 * Promotes a user to admin status.
 *
 * Usage: php scripts/make-admin.php <email>
 */

// Set base path
define('BASE_PATH', dirname(__DIR__));

// Check for email argument
if (!isset($argv[1])) {
    echo "Usage: php scripts/make-admin.php <email>\n";
    exit(1);
}

$email = $argv[1];

// Load configuration
$config = require BASE_PATH . '/config.php';
if (file_exists(BASE_PATH . '/config.local.php')) {
    $localConfig = require BASE_PATH . '/config.local.php';
    $config = array_merge($config, $localConfig);
}

$dbPath = $config['DB_PATH'];

if (!file_exists($dbPath)) {
    echo "Error: Database does not exist at: {$dbPath}\n";
    echo "Please run the application first to create the database.\n";
    exit(1);
}

// Connect to database
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Find user
$stmt = $pdo->prepare("SELECT id, username, email, is_admin FROM users WHERE email = ? AND deleted_at IS NULL");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Error: User not found with email: {$email}\n";
    exit(1);
}

if ($user['is_admin']) {
    echo "User '{$user['username']}' ({$user['email']}) is already an admin.\n";
    exit(0);
}

// Promote to admin
$stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
$stmt->execute([$user['id']]);

echo "✓ User '{$user['username']}' ({$user['email']}) has been promoted to admin.\n";
