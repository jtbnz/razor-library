<?php
/**
 * Migration: Create other_items table and related tables
 */

return [
    "CREATE TABLE IF NOT EXISTS other_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        category TEXT NOT NULL,
        description TEXT,
        notes TEXT,
        hero_image TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        deleted_at DATETIME,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    "CREATE INDEX IF NOT EXISTS idx_other_items_user_id ON other_items(user_id)",
    "CREATE INDEX IF NOT EXISTS idx_other_items_category ON other_items(category)",

    "CREATE TABLE IF NOT EXISTS other_item_images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        other_item_id INTEGER NOT NULL,
        filename TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (other_item_id) REFERENCES other_items(id) ON DELETE CASCADE
    )",

    "CREATE INDEX IF NOT EXISTS idx_other_item_images_other_item_id ON other_item_images(other_item_id)",

    "CREATE TABLE IF NOT EXISTS other_item_urls (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        other_item_id INTEGER NOT NULL,
        url TEXT NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (other_item_id) REFERENCES other_items(id) ON DELETE CASCADE
    )",

    "CREATE INDEX IF NOT EXISTS idx_other_item_urls_other_item_id ON other_item_urls(other_item_id)",

    "CREATE TABLE IF NOT EXISTS other_item_attributes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        other_item_id INTEGER NOT NULL,
        attribute_name TEXT NOT NULL,
        attribute_value TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (other_item_id) REFERENCES other_items(id) ON DELETE CASCADE
    )",

    "CREATE INDEX IF NOT EXISTS idx_other_item_attributes_other_item_id ON other_item_attributes(other_item_id)",
];
