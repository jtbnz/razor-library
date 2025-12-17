<?php
/**
 * Migration: Create rate limiting table
 */

return "
CREATE TABLE IF NOT EXISTS rate_limits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    identifier TEXT NOT NULL,
    action TEXT NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 1,
    first_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_rate_limits_identifier_action ON rate_limits(identifier, action);
";
