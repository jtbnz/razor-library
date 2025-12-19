# Razor Library User Guide

Welcome to Razor Library! This guide will help you get started with cataloging and managing your wet shaving collection.

## Table of Contents

1. [Getting Started](#getting-started)
2. [Dashboard Overview](#dashboard-overview)
3. [Managing Your Collection](#managing-your-collection)
4. [Working with Images](#working-with-images)
5. [Tracking Blade Usage](#tracking-blade-usage)
6. [Sharing Your Collection](#sharing-your-collection)
7. [Exporting Your Data](#exporting-your-data)
8. [Profile Settings](#profile-settings)

---

## Getting Started

### Creating an Account

If you're the first user on a fresh installation, you'll be prompted to create an admin account. Otherwise, ask your administrator to create an account for you, or register through the login page if registration is enabled.

### Logging In

1. Navigate to the application URL
2. Click "Sign In"
3. Enter your email and password
4. Click "Sign In" to access your dashboard

### Forgot Password

If you forget your password:
1. Click "Forgot your password?" on the login page
2. Enter your email address
3. Follow the instructions in the password reset email

---

## Dashboard Overview

After logging in, you'll land on the Razors page. Use the navigation menu to access different sections:

- **Razors** - Your safety razor, straight razor, and DE razor collection
- **Blades** - Your blade inventory
- **Brushes** - Your shaving brushes
- **Other** - Bowls, soaps, balms, splashes, and fragrances
- **Profile** - Account settings and collection sharing
- **Admin** - Administration panel (admin users only)

---

## Managing Your Collection

### Adding Items

1. Navigate to the section (Razors, Blades, Brushes, or Other)
2. Click the "Add [Item]" button
3. Fill in the details:
   - **Name** (required) - The item's name
   - **Images** - Upload one or more photos
   - **Description** - General description
   - **Notes** - Personal notes (e.g., where you bought it, condition)
4. Click "Save" to add the item

### Editing Items

1. Click on an item to view its details
2. Click the "Edit" button
3. Make your changes
4. Click "Save" to update

### Deleting Items

1. Click on an item to view its details
2. Click the "Delete" button
3. Confirm the deletion

**Note:** Deleted items are soft-deleted and their data is preserved in the database.

### Sorting Your Collection

Use the "Sort by" dropdown on any collection page to sort items by:
- Name (A-Z or Z-A)
- Date Added (Newest or Oldest First)
- Most Used (for razors and brushes with usage tracking)

---

## Working with Images

### Uploading Images

You can upload multiple images at once when creating or editing items:

1. Click the "Choose Files" or "Browse" button in the Images field
2. Select one or more images (hold Ctrl/Cmd to select multiple)
3. The first image uploaded becomes the **tile/hero image** shown in collection views

**Supported formats:** JPEG, PNG, GIF, WebP
**Maximum size:** 10MB per image

### Managing Images on Item Detail Pages

On an item's detail page, you can:

- **View all images** in the image gallery
- **Set a hero image** - Click the star icon on any image to make it the tile image displayed in your collection list
- **Delete images** - Click the X button to remove an image
- **Add more images** - Use the upload form at the bottom of the Images section

### Hero/Tile Image

The hero image is the primary image displayed:
- On the collection tile/card view
- At the top of the item's detail page

Look for the "Tile" badge to see which image is currently set as the hero. Click the star on any other image to change it.

---

## Tracking Blade Usage

### For Razors

Track which blades you've used with each razor:

1. Go to a razor's detail page
2. In the "Blade Usage" section, select a blade from the dropdown
3. Enter the usage count
4. Click "Add Usage"

Use the +/- buttons to increment or decrement usage counts.

### For Brushes

Track how many times you've used each brush:

1. Go to a brush's detail page
2. Click "Record Use" to increment the usage counter

---

## Sharing Your Collection

Share your collection with friends without giving them login access:

1. Go to **Profile**
2. Find your **Share Link**
3. Copy the link and send it to anyone
4. They can view your entire collection (read-only) without logging in

### Regenerating Your Share Link

If you want to invalidate old share links:
1. Go to **Profile**
2. Click "Regenerate Share Token"
3. Confirm the action
4. Your old share links will stop working

---

## Exporting Your Data

Download your entire collection as a ZIP file:

1. Go to **Profile**
2. Click "Export Collection"
3. A ZIP file will be downloaded containing:
   - All your items organized by category
   - Markdown files with item details
   - All your uploaded images

This is useful for:
- Backing up your collection
- Migrating to another system
- Offline viewing

---

## Importing from CSV

Bulk import items from a CSV spreadsheet:

1. Go to **Profile**
2. Scroll to the "Import from CSV" section
3. Select the import type (Razors, Blades, or Brushes)
4. Choose your CSV file
5. Click "Import"

### CSV Format

Your CSV file must have:
- A header row with column names
- One item per row

**Razors columns:** Brand, Name, UseCount, Notes
**Blades columns:** Brand, Name, Notes
**Brushes columns:** Brand, Name, BristleType, KnotSize, Loft, HandleMaterial, UseCount, Notes

### Download Templates

Download pre-formatted CSV templates from the Profile page:
- Click "Razors Template", "Blades Template", or "Brushes Template"
- Open the template in Excel or Google Sheets
- Add your items and save as CSV

### Import Tips

- **Name is required** - Items without a name will be skipped
- **Brand is combined with Name** - "Gillette" brand + "Slim" name = "Gillette Slim"
- **Duplicates are skipped** - Items with the same name won't be imported twice
- **Column names are flexible** - "UseCount" or "use_count" both work
- **Add details later** - Import basic info first, then add images and details via the web interface

---

## Profile Settings

### Changing Your Password

1. Go to **Profile**
2. Enter your current password
3. Enter your new password (minimum 8 characters)
4. Confirm your new password
5. Click "Update Password"

### Updating Your Username or Email

1. Go to **Profile**
2. Edit your username or email
3. Click "Save Changes"

---

## Tips and Best Practices

1. **Use descriptive names** - Include brand and model for easier searching
2. **Upload multiple angles** - Front, back, and detail shots help document condition
3. **Add purchase information** - Use the Notes field to record where and when you bought items
4. **Track blade usage** - This helps you understand your rotation and blade preferences
5. **Regular backups** - Ask your admin to create regular backups, or export your collection periodically
6. **Use the share link** - Great for showing off your collection without compromising account security

---

## Getting Help

If you encounter issues:
1. Check with your system administrator
2. Report bugs at: https://github.com/anthropics/razor-library/issues

---

*Happy shaving!*
