<?php
/**
 * Migration: Create API keys table
 */

return [
    "CREATE TABLE IF NOT EXISTS api_keys (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        key_hash TEXT NOT NULL,
        key_prefix TEXT NOT NULL,
        last_used_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        revoked_at DATETIME,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",
    "CREATE INDEX IF NOT EXISTS idx_api_keys_key_hash ON api_keys(key_hash)",
    "CREATE INDEX IF NOT EXISTS idx_api_keys_user_id ON api_keys(user_id)",
];
