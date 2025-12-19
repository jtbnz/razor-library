# Razor Library

A personal collection tracker for wet shaving enthusiasts. Catalog and manage your razors, blades, brushes, and other grooming accessories with photo galleries, usage tracking, and shareable collection links.

## Features

- **Multi-item Management**: Track razors, blades, brushes, bowls, soaps, balms, splashes, and fragrances
- **Photo Galleries**: Upload multiple images per item with automatic resizing and thumbnails
- **Tile Images**: Set any image as the tile/hero image displayed in collection views
- **Usage Tracking**: Track blade usage per razor and brush usage counts
- **Related URLs**: Link to product pages, reviews, or other resources
- **Share Collections**: Generate private shareable links for your collection
- **CSV Import**: Bulk import items from CSV spreadsheets
- **Export**: Download your entire collection as a ZIP with images and markdown files
- **Multi-user**: Support for multiple users with admin management
- **Backup & Restore**: Full database and image backup/restore functionality
- **Mobile Responsive**: Works on desktop and mobile devices

## Requirements

- PHP 8.1 or higher
- SQLite 3
- GD extension (for image processing)

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/razor-library.git
   cd razor-library
   ```

2. **Configure the application**
   ```bash
   cp config.php config.local.php
   ```

   Edit `config.local.php` and adjust settings as needed:
   ```php
   return [
       'APP_URL' => 'http://localhost:8080',
       'APP_DEBUG' => true,
       // ... other settings
   ];
   ```

3. **Create required directories**
   ```bash
   mkdir -p data uploads
   chmod 755 data uploads
   ```

4. **Start the development server**
   ```bash
   php -S localhost:8080 -t public
   ```

5. **Create your first user**

   Option A: Register through the web interface at `http://localhost:8080/register`

   Option B: Use the command line:
   ```bash
   php scripts/create-user.php "username" "email@example.com" "password" --admin
   ```

## Directory Structure

```
razor-library/
├── config.php              # Default configuration
├── config.local.php        # Local overrides (gitignored)
├── data/                   # SQLite database (gitignored)
├── docs/                   # Documentation
│   ├── user-guide.md      # User documentation
│   └── admin-guide.md     # Administrator documentation
├── migrations/             # Database migrations
├── public/                 # Web root
│   ├── index.php          # Application entry point
│   ├── uploads.php        # Serve uploaded images
│   └── assets/            # CSS, JS, images
├── scripts/               # CLI scripts
├── src/
│   ├── Controllers/       # Request handlers
│   ├── Helpers/           # Database, ImageHandler, etc.
│   └── Views/             # PHP templates
└── uploads/               # User uploaded images (gitignored)
```

## CLI Scripts

### Reset Database
Completely resets the database and optionally removes all uploaded files:
```bash
php scripts/reset-database.php              # Interactive reset
php scripts/reset-database.php --force      # Skip confirmation
php scripts/reset-database.php --keep-uploads  # Keep images
```

### Create User
Create a new user from the command line:
```bash
php scripts/create-user.php <username> <email> <password> [--admin]
```

### Make Admin
Promote an existing user to admin:
```bash
php scripts/make-admin.php <email>
```

## Usage Guide

### Adding Items

1. Log in to your account
2. Navigate to Razors, Blades, Brushes, or Other from the sidebar
3. Click "Add New" to create a new item
4. Fill in the details and optionally upload a hero image
5. Save the item

### Managing Images

- **Upload Multiple**: When creating an item or on any item's detail page, you can select multiple images at once. Hold `Cmd` (Mac) or `Ctrl` (Windows) to select multiple files
- **Automatic Hero**: The first image uploaded becomes the hero/tile image automatically
- **Set Tile Image**: Click the star icon on any gallery image to set it as the tile/hero image
- **Delete Images**: Click the X button on any image to delete it

### Tracking Usage

- **Blade Usage**: On a razor's detail page, select a blade and track how many times you've used it
- **Brush Usage**: On a brush's detail page, click "Record Use" to increment the usage counter

### Sharing Your Collection

1. Go to Profile from the sidebar
2. Copy your share link
3. Anyone with the link can view your collection (read-only)
4. Regenerate the link anytime to invalidate the old one

### Exporting Data

1. Go to Profile from the sidebar
2. Click "Export Collection"
3. A ZIP file will download containing:
   - All your images
   - Markdown files for each item

### Importing from CSV

Bulk import items from a spreadsheet:

1. Go to Profile from the sidebar
2. Scroll to "Import from CSV"
3. Select the import type (Razors, Blades, or Brushes)
4. Upload your CSV file
5. Click "Import"

**CSV Format:**
- Razors: `Brand, Name, UseCount, Notes`
- Blades: `Brand, Name, Notes`
- Brushes: `Brand, Name, BristleType, KnotSize, Loft, HandleMaterial, UseCount, Notes`

Download sample templates from the Profile page.

## Administration

### Admin Panel

Admin users can access the Admin panel from the navigation menu:

- **User Management**: Create, edit, and delete user accounts
- **Backup & Restore**: Create backups of the database and all images, restore from previous backups
- **Database Reset**: Completely reset the database (with confirmation safeguards)

### Backup & Restore

From the Admin panel:

1. **Create Backup**: Click "Create Backup Now" to generate a ZIP file containing the database and all uploaded images
2. **Download Backup**: Download any backup for off-site storage
3. **Restore**: Select a backup from the dropdown and click "Restore Selected" to restore the database and images
4. **Delete**: Remove old backups to free disk space

### Database Reset

For a complete reset:

1. Scroll to the "Danger Zone" section
2. Type `RESET DATABASE` exactly to confirm
3. Optionally keep uploaded images
4. Click "Reset Database"

**Warning**: This deletes all users and data. Create a backup first!

## Documentation

- [User Guide](docs/user-guide.md) - Complete user documentation
- [Admin Guide](docs/admin-guide.md) - Administration and maintenance

## Development

### Running Tests
```bash
# Not yet implemented
```

### Code Style
The project uses standard PHP coding conventions with PSR-4 autoloading.

## Security Considerations

- All user input is escaped using `htmlspecialchars()`
- SQL queries use prepared statements
- CSRF tokens protect all forms
- Passwords are hashed with `password_hash()`
- Uploaded files are validated for type and size
- Path traversal is prevented in file uploads

## License

MIT License - see LICENSE file for details.
