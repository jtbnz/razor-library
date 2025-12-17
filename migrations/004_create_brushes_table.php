<?php
/**
 * Migration: Create brushes table and related tables
 */

return [
    "CREATE TABLE IF NOT EXISTS brushes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        description TEXT,
        notes TEXT,
        hero_image TEXT,
        bristle_type TEXT,
        badger_grade TEXT,
        knot_size_mm INTEGER,
        loft_height_mm INTEGER,
        handle_material TEXT,
        handle_height_mm INTEGER,
        manufacturer TEXT,
        country_of_origin TEXT,
        usage_count INTEGER NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        deleted_at DATETIME,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    "CREATE INDEX IF NOT EXISTS idx_brushes_user_id ON brushes(user_id)",

    "CREATE TABLE IF NOT EXISTS brush_images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        brush_id INTEGER NOT NULL,
        filename TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (brush_id) REFERENCES brushes(id) ON DELETE CASCADE
    )",

    "CREATE INDEX IF NOT EXISTS idx_brush_images_brush_id ON brush_images(brush_id)",

    "CREATE TABLE IF NOT EXISTS brush_urls (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        brush_id INTEGER NOT NULL,
        url TEXT NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (brush_id) REFERENCES brushes(id) ON DELETE CASCADE
    )",

    "CREATE INDEX IF NOT EXISTS idx_brush_urls_brush_id ON brush_urls(brush_id)",
];
