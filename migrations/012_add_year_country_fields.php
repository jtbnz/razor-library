<?php
/**
 * Migration: Add year_manufactured and country_manufactured fields
 *
 * Adds year and country of manufacture to razors and blades tables
 * for better cataloging and sorting options.
 *
 * Note: Uses a callable to check for existing columns before adding them,
 * since SQLite doesn't support ALTER TABLE ADD COLUMN IF NOT EXISTS.
 */

// Helper function to check if column exists
function migration012_column_exists(PDO $pdo, string $table, string $column): bool {
    $result = $pdo->query("PRAGMA table_info({$table})");
    foreach ($result as $row) {
        if ($row['name'] === $column) {
            return true;
        }
    }
    return false;
}

return function(PDO $pdo) {
    // Add to razors table
    if (!migration012_column_exists($pdo, 'razors', 'year_manufactured')) {
        $pdo->exec("ALTER TABLE razors ADD COLUMN year_manufactured INTEGER");
    }
    if (!migration012_column_exists($pdo, 'razors', 'country_manufactured')) {
        $pdo->exec("ALTER TABLE razors ADD COLUMN country_manufactured TEXT");
    }

    // Add to blades table
    if (!migration012_column_exists($pdo, 'blades', 'country_manufactured')) {
        $pdo->exec("ALTER TABLE blades ADD COLUMN country_manufactured TEXT");
    }

    // Create indexes for sorting
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_razors_year ON razors(year_manufactured)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_razors_country ON razors(country_manufactured)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_blades_country ON blades(country_manufactured)");
};
