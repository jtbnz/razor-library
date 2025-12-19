# Razor Library Administrator Guide

This guide covers administration tasks for managing your Razor Library installation.

## Table of Contents

1. [Administration Overview](#administration-overview)
2. [User Management](#user-management)
3. [Backup and Restore](#backup-and-restore)
4. [Database Reset](#database-reset)
5. [Command Line Tools](#command-line-tools)
6. [Security Considerations](#security-considerations)
7. [Troubleshooting](#troubleshooting)

---

## Administration Overview

### Accessing the Admin Panel

1. Log in with an admin account
2. Click "Admin" in the navigation menu
3. You'll see the Administration dashboard with three sections:
   - User Management
   - Backup & Restore
   - Danger Zone

### Admin Privileges

Admin users can:
- Create, edit, and delete user accounts
- Grant or revoke admin privileges
- Create and restore database backups
- Reset the database
- Access all standard user features

---

## User Management

### Creating Users

1. Go to **Admin** > Click "Add User"
2. Fill in the required fields:
   - **Username** (required, must be unique)
   - **Email** (optional but recommended)
   - **Password** (minimum 8 characters)
   - **Admin** checkbox (grants admin privileges)
3. Click "Create User"

### Editing Users

1. Find the user in the User Management table
2. Click "Edit"
3. Modify the fields as needed
4. Leave the password blank to keep the current password
5. Click "Update User"

**Note:** You cannot remove admin privileges from your own account.

### Deleting Users

1. Find the user in the User Management table
2. Click "Delete"
3. Confirm the deletion

**Important:**
- You cannot delete your own account
- Deleted users are soft-deleted (their data is preserved)
- Deleted users cannot log in but their collection remains in the database

### User Statistics

The User Management table shows:
- **Collection counts** - R (Razors), B (Blades), Br (Brushes), O (Other items)
- **Role** - Admin or User badge
- **Created date** - When the account was created

---

## Backup and Restore

### Creating Backups

Backups include:
- The complete SQLite database
- All uploaded images (excluding the backups folder itself)

To create a backup:
1. Go to **Admin**
2. In the "Backup & Restore" section, click "Create Backup Now"
3. A ZIP file will be created with a timestamp name

**Backup naming format:** `razor_library_backup_YYYY-MM-DD_HH-MM-SS.zip`

### Viewing Backups

The "Available Backups" table shows:
- Date and time of each backup
- File size
- Download and Delete options

### Downloading Backups

1. Find the backup in the Available Backups table
2. Click "Download"
3. Save the ZIP file to a safe location

**Recommended:** Store backups off-site (cloud storage, external drive) for disaster recovery.

### Restoring from Backup

**Warning:** Restoring will replace all current data with the backup data.

1. Select a backup from the dropdown
2. Click "Restore Selected"
3. Confirm the action

During restore:
- Current database is backed up as `razor_library.db.pre_restore`
- Database is replaced with the backup
- Uploaded images are restored (existing images are preserved, missing ones are added)

### Deleting Backups

1. Find the backup in the Available Backups table
2. Click "Delete"
3. Confirm the deletion

---

## Database Reset

**Warning:** This is a destructive operation that cannot be undone!

### When to Use Database Reset

- Starting fresh for a new deployment
- Testing purposes
- Recovering from severe data corruption (when backups aren't available)

### How to Reset

1. Go to **Admin**
2. Scroll to the "Danger Zone" section
3. Note the last backup date (create a fresh backup if needed)
4. Type `RESET DATABASE` exactly in the confirmation field
5. Optionally check "Keep uploaded images" to preserve image files
6. Click "Reset Database"
7. Confirm the final warning

### After Reset

- All users are deleted
- All collection data is deleted
- You'll be logged out
- You'll be redirected to create a new admin account

---

## Command Line Tools

The `scripts/` directory contains CLI tools for advanced administration.

### create-user.php

Create a user from the command line:

```bash
# Create a regular user
php scripts/create-user.php "username" "email@example.com" "password123"

# Create an admin user
php scripts/create-user.php "username" "email@example.com" "password123" --admin
```

### make-admin.php

Promote an existing user to admin:

```bash
php scripts/make-admin.php email@example.com
```

### reset-database.php

Reset the database from command line:

```bash
# Full reset (database + uploads)
php scripts/reset-database.php

# Reset database only, keep images
php scripts/reset-database.php --keep-uploads

# Skip confirmation prompt
php scripts/reset-database.php --force
```

---

## Security Considerations

### Password Policy

- Minimum 8 characters required
- Passwords are hashed using PHP's `password_hash()` with bcrypt
- Never store or transmit passwords in plain text

### Session Security

- Sessions are named uniquely (`razor_library_session`)
- Session lifetime is 24 hours by default
- CSRF tokens protect all forms

### Rate Limiting

- Login attempts: 5 per 15 minutes
- Password reset requests: 3 per hour

### File Upload Security

- Only image files (JPEG, PNG, GIF, WebP) are accepted
- Maximum file size: 10MB
- Files are stored with UUID names (original filenames are not preserved)
- MIME type validation is performed

### Admin Access

- Admin routes require both authentication and admin privileges
- Admin status is checked on every request
- Self-removal of admin privileges is prevented

### Backups

- Backups are stored in `uploads/backups/`
- Consider restricting web access to this directory
- Regularly download backups to off-site storage

---

## Troubleshooting

### Users Can't Log In

1. Check if the user exists and is not deleted
2. Verify the email address is correct
3. Reset their password via Admin panel
4. Check rate limiting hasn't locked them out (wait 15 minutes)

### Missing Images

1. Check the `uploads/` directory permissions (should be writable)
2. Verify the image file exists on disk
3. Check the database record matches the file path
4. Restore from backup if necessary

### Database Errors

1. Check file permissions on `data/razor_library.db`
2. Ensure the `data/` directory exists and is writable
3. Check disk space availability
4. Try restoring from a recent backup

### Backup Creation Fails

1. Check disk space
2. Verify `uploads/backups/` directory is writable
3. Ensure PHP's ZipArchive extension is enabled
4. Check for very large files that might timeout

### Restore Fails

1. Verify the backup ZIP file is not corrupted
2. Check disk space for extraction
3. Ensure temp directory is writable
4. Check PHP memory limits for large backups

### Session Issues

1. Check PHP session configuration
2. Verify `session.save_path` is writable
3. Clear browser cookies
4. Check for conflicting session names

---

## Configuration Reference

Key configuration options in `config.php`:

| Setting | Default | Description |
|---------|---------|-------------|
| `DB_PATH` | `data/razor_library.db` | SQLite database location |
| `UPLOAD_PATH` | `uploads/` | Image upload directory |
| `UPLOAD_MAX_SIZE` | 10MB | Maximum file upload size |
| `SESSION_LIFETIME` | 86400 | Session timeout (seconds) |
| `RATE_LIMIT_LOGIN_ATTEMPTS` | 5 | Max login attempts |
| `RATE_LIMIT_LOGIN_WINDOW` | 900 | Login lockout window (seconds) |

Create a `config.local.php` file to override defaults without modifying the main config file.

---

## Best Practices

1. **Regular Backups**
   - Create backups weekly or after significant changes
   - Download backups to off-site storage
   - Test restore procedures periodically

2. **User Management**
   - Use real email addresses for password recovery
   - Grant admin privileges sparingly
   - Remove inactive users

3. **Security**
   - Keep PHP and dependencies updated
   - Use HTTPS in production
   - Restrict admin access to trusted users
   - Monitor login attempts

4. **Maintenance**
   - Check disk space periodically
   - Clean up old backups
   - Review error logs

---

## Support

For issues and feature requests:
- GitHub Issues: https://github.com/anthropics/razor-library/issues

---

*Keep your data safe and your shaves smooth!*
