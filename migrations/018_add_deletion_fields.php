<?php
/**
 * Migration: Add deletion request fields to users table
 *
 * Note: Uses a callable to check for existing columns before adding them,
 * since SQLite doesn't support ALTER TABLE ADD COLUMN IF NOT EXISTS.
 */

// Helper function to check if column exists
function migration018_column_exists(PDO $pdo, string $table, string $column): bool {
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
        'deletion_requested_at' => 'DATETIME',
        'deletion_scheduled_at' => 'DATETIME',
    ];

    foreach ($columnsToAdd as $column => $type) {
        if (!migration018_column_exists($pdo, 'users', $column)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN {$column} {$type}");
        }
    }
};
