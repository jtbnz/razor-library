<?php
/**
 * Migration: Add year_manufactured and country_manufactured fields
 *
 * Adds year and country of manufacture to razors and blades tables
 * for better cataloging and sorting options.
 */

return [
    // Add to razors table
    "ALTER TABLE razors ADD COLUMN year_manufactured INTEGER",
    "ALTER TABLE razors ADD COLUMN country_manufactured TEXT",

    // Add to blades table
    "ALTER TABLE blades ADD COLUMN country_manufactured TEXT",

    // Create indexes for sorting
    "CREATE INDEX IF NOT EXISTS idx_razors_year ON razors(year_manufactured)",
    "CREATE INDEX IF NOT EXISTS idx_razors_country ON razors(country_manufactured)",
    "CREATE INDEX IF NOT EXISTS idx_blades_country ON blades(country_manufactured)",
];
