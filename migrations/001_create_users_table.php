<?php
/**
 * Migration: Create users table
 */

return "
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    is_admin INTEGER NOT NULL DEFAULT 0,
    share_token TEXT UNIQUE,
    reset_token TEXT,
    reset_token_expires DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME
);

CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_share_token ON users(share_token);
CREATE INDEX IF NOT EXISTS idx_users_reset_token ON users(reset_token);
";
