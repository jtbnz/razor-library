<?php
/**
 * Migration: Create razors table and related tables
 */

return [
    "CREATE TABLE IF NOT EXISTS razors (
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

    "CREATE INDEX IF NOT EXISTS idx_razors_user_id ON razors(user_id)",

    "CREATE TABLE IF NOT EXISTS razor_images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        razor_id INTEGER NOT NULL,
        filename TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (razor_id) REFERENCES razors(id) ON DELETE CASCADE
    )",

    "CREATE INDEX IF NOT EXISTS idx_razor_images_razor_id ON razor_images(razor_id)",

    "CREATE TABLE IF NOT EXISTS razor_urls (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        razor_id INTEGER NOT NULL,
        url TEXT NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (razor_id) REFERENCES razors(id) ON DELETE CASCADE
    )",

    "CREATE INDEX IF NOT EXISTS idx_razor_urls_razor_id ON razor_urls(razor_id)",
];
