# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Razor Library is a personal collection tracker for wet shaving enthusiasts. It's a multi-user PHP web application for cataloging safety razors, DE blades, shaving brushes, and other grooming items with photo galleries and usage tracking.

**Stack:** PHP 8.1+, SQLite 3, vanilla JavaScript, no external dependencies (no Composer/npm)

## Development Commands

```bash
# Start development server
php -S localhost:8080 -t public -d upload_max_filesize=10M -d post_max_size=50M

# Create user from CLI
php scripts/create-user.php "username" "email@example.com" "password" [--admin]

# Promote user to admin
php scripts/make-admin.php "email@example.com"

# Reset database (destructive)
php scripts/reset-database.php [--force] [--keep-uploads]
```

## Architecture

**Request Flow:** All requests → `public/index.php` → Router → Middleware → Controller → View

**Key directories:**
- `public/` - Web root (document root points here), entry point, static assets
- `src/Controllers/` - Request handlers (CRUD for razors, blades, brushes, other items)
- `src/Helpers/` - Database, Router, ImageHandler, Mailer, RateLimiter, functions.php
- `src/Views/` - PHP templates with layouts (app.php, auth.php, share.php)
- `migrations/` - Numbered database migrations (auto-run on first request)
- `scripts/` - CLI utilities

**Database:** SQLite with PDO. Migrations in `/migrations/` auto-execute. All item tables use soft deletes (`deleted_at` field).

**Configuration:** `config.php` (defaults) merged with `config.local.php` (local overrides, gitignored)

## Code Patterns

**Controllers:**
- Verify CSRF with `verify_csrf()` before processing POST
- Use `Database::query()` with prepared statements (never concatenate SQL)
- Render views with `echo view('path', ['data' => $var])` or `redirect('/path')`
- Check user ownership: `WHERE user_id = ?` in queries

**Views:**
- Escape output with `e()` function (htmlspecialchars wrapper)
- Use `url()` helper for links (supports subdirectory installs)
- Include `csrf_field()` in all forms
- Layout pattern: `ob_start()` → content → `ob_get_clean()` → require layout

**Images:**
- Process uploads via `ImageHandler::processUpload()`
- Stored in `/uploads/` with UUID filenames
- Auto-generates thumbnails (300px) and resizes full images (1200px max)

**Authentication:**
- Session-based with middleware (`auth`, `admin`, `guest`)
- Rate limiting on login (5 attempts per 15 minutes per IP)
- Share links use token-based read-only access

## Database Schema Patterns

Core tables: `users`, `razors`, `blades`, `brushes`, `other_items`
Each item type has related `*_images` and `*_urls` tables.
Special tables: `blade_usage` (usage count per razor), `rate_limits`, `migrations`

Always include `WHERE deleted_at IS NULL` in queries for soft-deleted items.

## Routes

Defined in `public/index.php`. Patterns:
- Public: `/`, `/login`, `/logout`, `/setup`, `/share/{token}`
- Protected: `/razors*`, `/blades*`, `/brushes*`, `/other*`, `/profile*`
- Admin: `/admin*`
