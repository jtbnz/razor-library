<?php
/**
 * Migration: Add missing columns to brushes table
 * The original table had different column names than what the code expects
 */

return [
    "ALTER TABLE brushes ADD COLUMN brand TEXT",
    "ALTER TABLE brushes ADD COLUMN knot_size TEXT",
    "ALTER TABLE brushes ADD COLUMN loft TEXT",
    "ALTER TABLE brushes ADD COLUMN use_count INTEGER NOT NULL DEFAULT 0",
];
