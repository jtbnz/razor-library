#!/usr/bin/env php
<?php
/**
 * Razor Library - Create User Script
 *
 * Creates a new user from the command line.
 *
 * Usage: php scripts/create-user.php <username> <email> <password> [--admin]
 */

// Set base path
define('BASE_PATH', dirname(__DIR__));

// Check for required arguments
if ($argc < 4) {
    echo "Usage: php scripts/create-user.php <username> <email> <password> [--admin]\n";
    exit(1);
}

$username = $argv[1];
$email = $argv[2];
$password = $argv[3];
$isAdmin = in_array('--admin', $argv);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Error: Invalid email address.\n";
    exit(1);
}

// Load configuration
$config = require BASE_PATH . '/config.php';
if (file_exists(BASE_PATH . '/config.local.php')) {
    $localConfig = require BASE_PATH . '/config.local.php';
    $config = array_merge($config, $localConfig);
}

$dbPath = $config['DB_PATH'];
$dbDir = dirname($dbPath);

// Create data directory if needed
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

// Connect to database
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check if user already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
$stmt->execute([$email, $username]);
if ($stmt->fetch()) {
    echo "Error: User with this email or username already exists.\n";
    exit(1);
}

// Create user
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$shareToken = bin2hex(random_bytes(16));

$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, is_admin, share_token) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$username, $email, $passwordHash, $isAdmin ? 1 : 0, $shareToken]);

$userId = $pdo->lastInsertId();

echo "✓ User created successfully!\n";
echo "  ID: {$userId}\n";
echo "  Username: {$username}\n";
echo "  Email: {$email}\n";
echo "  Admin: " . ($isAdmin ? 'Yes' : 'No') . "\n";
