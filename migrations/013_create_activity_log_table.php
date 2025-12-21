<?php
/**
 * Migration: Create activity_log table for admin-visible activity logging
 */
return [
    "CREATE TABLE IF NOT EXISTS activity_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        action TEXT NOT NULL,
        target_type TEXT,
        target_id INTEGER,
        details TEXT,
        ip_address TEXT,
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )",
    "CREATE INDEX IF NOT EXISTS idx_activity_log_user_id ON activity_log(user_id)",
    "CREATE INDEX IF NOT EXISTS idx_activity_log_action ON activity_log(action)",
    "CREATE INDEX IF NOT EXISTS idx_activity_log_created_at ON activity_log(created_at)",
];
