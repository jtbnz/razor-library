<?php
/**
 * Migration: Add last_used_at column to all item tables
 */

return [
    "ALTER TABLE razors ADD COLUMN last_used_at DATETIME",
    "ALTER TABLE blades ADD COLUMN last_used_at DATETIME",
    "ALTER TABLE brushes ADD COLUMN last_used_at DATETIME",
    "ALTER TABLE other_items ADD COLUMN last_used_at DATETIME",
];
