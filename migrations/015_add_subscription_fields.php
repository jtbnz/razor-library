<?php
/**
 * Migration: Add subscription system fields to users and create subscription_config table
 *
 * Note: Uses a callable to check for existing columns before adding them,
 * since SQLite doesn't support ALTER TABLE ADD COLUMN IF NOT EXISTS.
 */

// Helper function to check if column exists
function migration015_column_exists(PDO $pdo, string $table, string $column): bool {
    $result = $pdo->query("PRAGMA table_info({$table})");
    foreach ($result as $row) {
        if ($row['name'] === $column) {
            return true;
        }
    }
    return false;
}

return function(PDO $pdo) {
    // Add subscription fields to users table (only if they don't exist)
    $columnsToAdd = [
        'subscription_status' => "TEXT DEFAULT 'trial'",
        'subscription_started_at' => 'DATETIME',
        'subscription_expires_at' => 'DATETIME',
        'bmac_member_id' => 'TEXT',
        'deletion_requested_at' => 'DATETIME',
        'deletion_scheduled_at' => 'DATETIME',
    ];

    foreach ($columnsToAdd as $column => $type) {
        if (!migration015_column_exists($pdo, 'users', $column)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN {$column} {$type}");
        }
    }

    // Create subscription_config table (single row for admin settings)
    $pdo->exec("CREATE TABLE IF NOT EXISTS subscription_config (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        bmac_access_token TEXT,
        bmac_webhook_secret TEXT,
        trial_days INTEGER DEFAULT 7,
        subscription_check_enabled INTEGER DEFAULT 0,
        expired_message TEXT DEFAULT 'Your trial has expired. Subscribe to continue using Razor Library.',
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert default config row
    $pdo->exec("INSERT OR IGNORE INTO subscription_config (id, trial_days, subscription_check_enabled) VALUES (1, 7, 0)");

    // Create subscription_events table for logging
    $pdo->exec("CREATE TABLE IF NOT EXISTS subscription_events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        event_type TEXT NOT NULL,
        details TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");

    // Indexes
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_subscription_status ON users(subscription_status)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_subscription_expires ON users(subscription_expires_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_subscription_events_user ON subscription_events(user_id)");
};
