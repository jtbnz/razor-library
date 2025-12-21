<?php
/**
 * Migration: Add deletion request fields to users table
 */

return [
    "ALTER TABLE users ADD COLUMN deletion_requested_at DATETIME",
    "ALTER TABLE users ADD COLUMN deletion_scheduled_at DATETIME",
];
