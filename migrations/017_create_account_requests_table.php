<?php
/**
 * Migration: Create account_requests table
 */

return [
    "CREATE TABLE IF NOT EXISTS account_requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        email TEXT NOT NULL,
        reason TEXT,
        status TEXT DEFAULT 'pending',
        reviewed_by INTEGER,
        reviewed_at DATETIME,
        rejection_reason TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
    )",
    "CREATE INDEX IF NOT EXISTS idx_account_requests_status ON account_requests(status)",
    "CREATE INDEX IF NOT EXISTS idx_account_requests_email ON account_requests(email)",
];
