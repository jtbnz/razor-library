<?php
/**
 * Migration: Add subscription system fields to users and create subscription_config table
 */
return [
    // Add subscription fields to users table
    "ALTER TABLE users ADD COLUMN subscription_status TEXT DEFAULT 'trial'",
    "ALTER TABLE users ADD COLUMN subscription_started_at DATETIME",
    "ALTER TABLE users ADD COLUMN subscription_expires_at DATETIME",
    "ALTER TABLE users ADD COLUMN bmac_member_id TEXT",
    "ALTER TABLE users ADD COLUMN deletion_requested_at DATETIME",
    "ALTER TABLE users ADD COLUMN deletion_scheduled_at DATETIME",

    // Create subscription_config table (single row for admin settings)
    "CREATE TABLE IF NOT EXISTS subscription_config (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        bmac_access_token TEXT,
        bmac_webhook_secret TEXT,
        trial_days INTEGER DEFAULT 7,
        subscription_check_enabled INTEGER DEFAULT 0,
        expired_message TEXT DEFAULT 'Your trial has expired. Subscribe to continue using Razor Library.',
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",

    // Insert default config row
    "INSERT OR IGNORE INTO subscription_config (id, trial_days, subscription_check_enabled) VALUES (1, 7, 0)",

    // Create subscription_events table for logging
    "CREATE TABLE IF NOT EXISTS subscription_events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        event_type TEXT NOT NULL,
        details TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )",

    // Indexes
    "CREATE INDEX IF NOT EXISTS idx_users_subscription_status ON users(subscription_status)",
    "CREATE INDEX IF NOT EXISTS idx_users_subscription_expires ON users(subscription_expires_at)",
    "CREATE INDEX IF NOT EXISTS idx_subscription_events_user ON subscription_events(user_id)",
];
