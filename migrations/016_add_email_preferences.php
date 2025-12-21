<?php
/**
 * Migration: Add email preference fields and email log table
 *
 * Note: Uses a callable to check for existing columns before adding them,
 * since SQLite doesn't support ALTER TABLE ADD COLUMN IF NOT EXISTS.
 */

// Helper function to check if column exists
function migration016_column_exists(PDO $pdo, string $table, string $column): bool {
    $result = $pdo->query("PRAGMA table_info({$table})");
    foreach ($result as $row) {
        if ($row['name'] === $column) {
            return true;
        }
    }
    return false;
}

return function(PDO $pdo) {
    // Add email preference fields to users (only if they don't exist)
    $columnsToAdd = [
        'email_trial_warnings' => 'INTEGER DEFAULT 1',
        'email_renewal_reminders' => 'INTEGER DEFAULT 1',
        'email_account_updates' => 'INTEGER DEFAULT 1',
        'email_marketing' => 'INTEGER DEFAULT 0',
        'unsubscribe_token' => 'TEXT',
    ];

    foreach ($columnsToAdd as $column => $type) {
        if (!migration016_column_exists($pdo, 'users', $column)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN {$column} {$type}");
        }
    }

    // Create email log table to prevent duplicate sends
    $pdo->exec("CREATE TABLE IF NOT EXISTS email_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        email_type TEXT NOT NULL,
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_email_log_user_type ON email_log(user_id, email_type)");
};
