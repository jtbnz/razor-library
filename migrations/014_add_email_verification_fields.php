<?php
/**
 * Migration: Add email verification fields for email change feature
 *
 * Note: Uses a callable to check for existing columns before adding them,
 * since SQLite doesn't support ALTER TABLE ADD COLUMN IF NOT EXISTS.
 */

// Helper function to check if column exists
function migration014_column_exists(PDO $pdo, string $table, string $column): bool {
    $result = $pdo->query("PRAGMA table_info({$table})");
    foreach ($result as $row) {
        if ($row['name'] === $column) {
            return true;
        }
    }
    return false;
}

return function(PDO $pdo) {
    $columnsToAdd = [
        'pending_email' => 'TEXT',
        'email_verification_token' => 'TEXT',
        'email_verification_expires' => 'DATETIME',
    ];

    foreach ($columnsToAdd as $column => $type) {
        if (!migration014_column_exists($pdo, 'users', $column)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN {$column} {$type}");
        }
    }
};
