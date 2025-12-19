<?php
/**
 * Migration: Add use_count column to razors table
 */

return "ALTER TABLE razors ADD COLUMN use_count INTEGER NOT NULL DEFAULT 0";
