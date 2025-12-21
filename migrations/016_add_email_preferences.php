<?php
/**
 * Migration: Add email preference fields and email log table
 */
return [
    // Add email preference fields to users
    "ALTER TABLE users ADD COLUMN email_trial_warnings INTEGER DEFAULT 1",
    "ALTER TABLE users ADD COLUMN email_renewal_reminders INTEGER DEFAULT 1",
    "ALTER TABLE users ADD COLUMN email_account_updates INTEGER DEFAULT 1",
    "ALTER TABLE users ADD COLUMN email_marketing INTEGER DEFAULT 0",
    "ALTER TABLE users ADD COLUMN unsubscribe_token TEXT",

    // Create email log table to prevent duplicate sends
    "CREATE TABLE IF NOT EXISTS email_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        email_type TEXT NOT NULL,
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE INDEX IF NOT EXISTS idx_email_log_user_type ON email_log(user_id, email_type)",
];
