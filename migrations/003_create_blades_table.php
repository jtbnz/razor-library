<?php
/**
 * Migration: Create blades table and related tables
 */

return [
    "CREATE TABLE IF NOT EXISTS blades (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        description TEXT,
        notes TEXT,
        hero_image TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        deleted_at DATETIME,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    "CREATE INDEX IF NOT EXISTS idx_blades_user_id ON blades(user_id)",

    "CREATE TABLE IF NOT EXISTS blade_images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        blade_id INTEGER NOT NULL,
        filename TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (blade_id) REFERENCES blades(id) ON DELETE CASCADE
    )",

    "CREATE INDEX IF NOT EXISTS idx_blade_images_blade_id ON blade_images(blade_id)",

    "CREATE TABLE IF NOT EXISTS blade_urls (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        blade_id INTEGER NOT NULL,
        url TEXT NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (blade_id) REFERENCES blades(id) ON DELETE CASCADE
    )",

    "CREATE INDEX IF NOT EXISTS idx_blade_urls_blade_id ON blade_urls(blade_id)",

    "CREATE TABLE IF NOT EXISTS blade_usage (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        razor_id INTEGER NOT NULL,
        blade_id INTEGER NOT NULL,
        count INTEGER NOT NULL DEFAULT 0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (razor_id) REFERENCES razors(id) ON DELETE CASCADE,
        FOREIGN KEY (blade_id) REFERENCES blades(id) ON DELETE CASCADE,
        UNIQUE(razor_id, blade_id)
    )",

    "CREATE INDEX IF NOT EXISTS idx_blade_usage_razor_id ON blade_usage(razor_id)",
    "CREATE INDEX IF NOT EXISTS idx_blade_usage_blade_id ON blade_usage(blade_id)",
];
