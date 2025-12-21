<?php
/**
 * Migration: Add BMaC membership URL to subscription_config
 */

return function(PDO $pdo) {
    // Check if column exists
    $result = $pdo->query("PRAGMA table_info(subscription_config)");
    $hasColumn = false;
    foreach ($result as $row) {
        if ($row['name'] === 'bmac_membership_url') {
            $hasColumn = true;
            break;
        }
    }

    if (!$hasColumn) {
        $pdo->exec("ALTER TABLE subscription_config ADD COLUMN bmac_membership_url TEXT DEFAULT 'https://buymeacoffee.com/'");
    }
};
