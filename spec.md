# Razor Library - Design Specification

A multi-user web application for collectors to catalog and track their safety razors, blades, brushes, and grooming accessories.

---

## 1. Overview

### 1.1 Purpose
A personal collection management system where users can:
- Catalog their safety razors with images and details
- Catalog their blade collection
- Catalog their shaving brushes with detailed specifications
- Catalog other grooming items (bowls, soaps, balms, splashes, fragrances)
- Track blade usage per razor
- Track brush usage count
- Share their collection via private links

### 1.2 Tech Stack
| Component | Technology |
|-----------|------------|
| Backend | PHP (vanilla) |
| Frontend | HTML, CSS, JavaScript (vanilla) |
| Database | SQLite |
| Image Storage | Local filesystem with UUID naming |
| Hosting | PHP-compatible host with git auto-deploy |

---

## 2. User System

### 2.1 User Roles
| Role | Capabilities |
|------|-------------|
| **Admin** | Create/delete users, access admin panel, full site control |
| **User** | Manage own razors/blades/brushes/other items, generate share links |
| **Guest** | View shared collections via token URL (read-only) |

### 2.2 Authentication
- Session-based authentication
- Password hashing using PHP's `password_hash()` (bcrypt)
- First registered user automatically becomes admin
- Admin-only user creation (no public registration)

### 2.3 Share Links
- Format: `/share/{random_token}`
- Token: 32-character random string
- Users can regenerate token (invalidates previous link)
- Grants read-only access to that user's entire collection

---

## 3. Landing Page

### 3.1 Public View (Not Logged In)
- Splash image (admin-uploadable)
- Site title: "Razor Library"
- Login form
- Clean, minimal design

### 3.2 Logged In View
- Redirect to user's dashboard/collection

---

## 4. User Dashboard

### 4.1 Navigation
- **Razors** - View/manage razor collection
- **Blades** - View/manage blade collection
- **Brushes** - View/manage brush collection
- **Other** - View/manage other grooming items (with subcategory tabs)
- **Profile** - User settings and share link
- **Logout**

### 4.2 Admin Additional Navigation
- **Admin Panel** - User management

---

## 5. Razors Section

### 5.1 Tile View (List)
- Grid of razor cards
- Each card displays:
  - Hero image (or placeholder if none)
  - Razor name
- Sort options:
  - Alphabetical (A-Z, Z-A)
  - Date added (newest, oldest)
  - Most used (by total blade usage)
- **Add Razor** button (authenticated only)

### 5.2 Razor Detail Page

#### Fields
| Field | Required | Description |
|-------|----------|-------------|
| Name | Yes | Razor name/model |
| Hero Image | No | Primary display image (placeholder if empty) |
| Description | No | Text description |
| Attached Images | No | Additional images gallery |
| Related URLs | No | List of URLs with descriptions |
| Notes | No | Personal notes |
| Blades Used | Auto | List of blades with usage count per blade |

#### Blade Usage Tracking (Authenticated Only)
- Select blade from user's blade collection
- Increment/decrement usage count
- Display only when authenticated
- Hidden in shared/guest view

### 5.3 Actions (Authenticated Only)
- Edit all fields
- Upload/delete images
- Add/remove related URLs
- Delete razor (soft delete)

---

## 6. Blades Section

### 6.1 Tile View (List)
- Grid of blade cards
- Each card displays:
  - Hero image (or placeholder if none)
  - Blade name
- Sort options:
  - Alphabetical (A-Z, Z-A)
  - Date added (newest, oldest)
  - Most used (by total usage across all razors)
- **Add Blade** button (authenticated only)

### 6.2 Blade Detail Page

#### Fields
| Field | Required | Description |
|-------|----------|-------------|
| Name | Yes | Blade brand/name |
| Hero Image | No | Primary display image (placeholder if empty) |
| Description | No | Text description |
| Attached Images | No | Additional images gallery |
| Related URLs | No | List of URLs with descriptions |
| Notes | No | Personal notes |
| Total Used | Auto | Sum of usage across all razors (authenticated only) |

### 6.3 Actions (Authenticated Only)
- Edit all fields
- Upload/delete images
- Add/remove related URLs
- Delete blade (soft delete)

---

## 7. Brushes Section

### 7.1 Tile View (List)
- Grid of brush cards
- Each card displays:
  - Hero image (or placeholder if none)
  - Brush name
- Sort options:
  - Alphabetical (A-Z, Z-A)
  - Date added (newest, oldest)
  - Most used (by usage count)
- **Add Brush** button (authenticated only)

### 7.2 Brush Detail Page

#### Common Fields
| Field | Required | Description |
|-------|----------|-------------|
| Name | Yes | Brush name/model |
| Hero Image | No | Primary display image (placeholder if empty) |
| Description | No | Text description |
| Attached Images | No | Additional images gallery |
| Related URLs | No | List of URLs with descriptions |
| Notes | No | Personal notes |
| Usage Count | Auto | Number of uses (authenticated only) |

#### Brush-Specific Fields (All Optional)
| Field | Type | Description |
|-------|------|-------------|
| Bristle Type | Select | Badger (Silvertip/Super/Best/Pure), Boar, Synthetic, Horse, Mixed |
| Badger Grade | Select | Silvertip, Super, Best, Pure (only shown if Bristle Type = Badger) |
| Knot Size | Number (mm) | Diameter of the knot base (common: 21-26mm) |
| Loft Height | Number (mm) | Total height from handle to tip (common: 48-56mm) |
| Handle Material | Select | Resin, Wood, Metal, Acrylic, Horn, Plastic |
| Handle Height | Number (mm) | Height of the handle |
| Manufacturer | Text | Brand/maker name |
| Country of Origin | Text | Where the brush was made |

#### Bristle Type Reference
| Type | Characteristics |
|------|----------------|
| **Badger - Silvertip** | Softest, most luxurious, excellent water retention, highest grade |
| **Badger - Super** | Very soft, good backbone, second highest grade |
| **Badger - Best** | Good softness, more backbone, mid-grade |
| **Badger - Pure** | Coarser, most backbone, entry-level badger |
| **Boar** | Affordable, requires break-in, good backbone, splits over time |
| **Synthetic** | Vegan-friendly, quick-drying, consistent quality, no break-in |
| **Horse** | Mix of softness and backbone, less common |

#### Usage Tracking (Authenticated Only)
- Increment/decrement usage count
- Display only when authenticated
- Hidden in shared/guest view

### 7.3 Actions (Authenticated Only)
- Edit all fields
- Upload/delete images
- Add/remove related URLs
- Delete brush (soft delete)

---

## 8. Other Section

### 8.1 Overview
A single section for miscellaneous grooming items, organized by subcategories via tabs/filter.

#### Subcategories
| Category | Description |
|----------|-------------|
| **Bowls** | Shaving bowls and scuttles |
| **Soaps** | Shaving soaps and creams |
| **Balms** | Aftershave balms |
| **Splashes** | Aftershave splashes |
| **Fragrances** | Colognes, EDT, EDP, etc. |

### 8.2 Tile View (List)
- Grid of item cards
- Category tabs/filter at top: All | Bowls | Soaps | Balms | Splashes | Fragrances
- Each card displays:
  - Hero image (or placeholder if none)
  - Item name
  - Category badge/label
- Sort options:
  - Alphabetical (A-Z, Z-A)
  - Date added (newest, oldest)
  - Category
- **Add Item** button (authenticated only)

### 8.3 Item Detail Page

#### Common Fields (All Categories)
| Field | Required | Description |
|-------|----------|-------------|
| Name | Yes | Item name/model |
| Category | Yes | Bowls, Soaps, Balms, Splashes, or Fragrances |
| Hero Image | No | Primary display image (placeholder if empty) |
| Description | No | Text description |
| Attached Images | No | Additional images gallery |
| Related URLs | No | List of URLs with descriptions |
| Notes | No | Personal notes |

#### Bowl-Specific Fields (Optional)
| Field | Type | Description |
|-------|------|-------------|
| Material | Select | Ceramic, Porcelain, Stainless Steel, Wood, Marble, Copper, Horn, Plastic |
| Diameter | Number (mm/inches) | Bowl diameter |
| Height | Number (mm/inches) | Bowl height |
| Has Lid | Boolean | Whether the bowl has a lid |
| Has Handle | Boolean | Whether the bowl has a handle |
| Textured Interior | Boolean | Whether interior has ridges/texture for lathering |

#### Bowl Material Reference
| Material | Properties |
|----------|------------|
| **Ceramic/Porcelain** | Retains heat well, easy to clean, durable |
| **Stainless Steel** | Durable, doesn't retain heat as well |
| **Wood** | Warm feel, aids lathering, needs drying care |
| **Marble** | Excellent heat retention, premium feel, heavy |

#### Soap-Specific Fields (Optional)
| Field | Type | Description |
|-------|------|-------------|
| Base Type | Select | Tallow, Vegan/Plant-Based, Glycerin, Cream |
| Scent Notes | Text | Free-form scent description |
| Weight | Number (oz/g) | Product weight |
| Artisan/Manufacturer | Text | Brand or maker |
| Vegan | Boolean | Whether the product is vegan |

#### Soap Base Reference
| Base | Properties |
|------|------------|
| **Tallow** | Dense lather, excellent slickness, great post-shave feel, contains animal fat |
| **Vegan/Plant-Based** | Cruelty-free, uses coconut oil/shea butter, modern formulas rival tallow |
| **Glycerin** | Good moisture retention, clear appearance, good for sensitive skin |
| **Cream** | Softer consistency, easier to lather, often comes in tubes |

#### Balm-Specific Fields (Optional)
| Field | Type | Description |
|-------|------|-------------|
| Scent Notes | Text | Free-form scent description |
| Volume | Number (ml/oz) | Product volume |
| Key Ingredients | Text | Notable ingredients (e.g., aloe, witch hazel, shea butter) |
| Alcohol-Free | Boolean | Whether the product is alcohol-free |
| Artisan/Manufacturer | Text | Brand or maker |

#### Splash-Specific Fields (Optional)
| Field | Type | Description |
|-------|------|-------------|
| Scent Notes | Text | Free-form scent description |
| Volume | Number (ml/oz) | Product volume |
| Alcohol Content | Select | Alcohol-Free, Low, Standard (~70%), High |
| Key Ingredients | Text | Notable ingredients (e.g., menthol, witch hazel, allantoin) |
| Artisan/Manufacturer | Text | Brand or maker |

#### Fragrance-Specific Fields (Optional)
| Field | Type | Description |
|-------|------|-------------|
| Concentration | Select | Parfum/Extrait, EDP, EDT, EDC, Eau Fraîche |
| Scent Notes | Text | Free-form scent description (top, heart, base notes) |
| Volume | Number (ml/oz) | Bottle size |
| Longevity | Select | Light (1-2 hrs), Moderate (3-5 hrs), Long (6-8 hrs), Very Long (8+ hrs) |
| Sillage | Select | Intimate, Moderate, Strong, Powerful |
| Season | Multi-Select | Spring, Summer, Fall, Winter |
| Occasion | Multi-Select | Daily, Office, Evening, Special |
| House/Brand | Text | Fragrance house or brand |

#### Fragrance Concentration Reference
| Concentration | Oil % | Typical Longevity |
|---------------|-------|-------------------|
| **Parfum/Extrait** | 20-40% | 12-24 hours |
| **Eau de Parfum (EDP)** | 15-20% | 6-8 hours |
| **Eau de Toilette (EDT)** | 5-15% | 4-6 hours |
| **Eau de Cologne (EDC)** | 2-5% | 2-3 hours |
| **Eau Fraîche** | 1-3% | 1-2 hours |

### 8.4 Actions (Authenticated Only)
- Edit all fields
- Upload/delete images
- Add/remove related URLs
- Delete item (soft delete)

---

## 9. Profile Section

### 9.1 User Settings
- Update email address
- Change password
- View/regenerate share link
- Copy share link to clipboard
- Download full collection backup (see 9.3)

### 9.2 Admin Panel (Admin Only)
- List all users
- Create new user (username, email, password)
- Delete user (soft delete, preserves data)
- Upload/change splash image

### 9.3 Password Reset

#### Reset Flow
1. User clicks "Forgot Password?" on login page
2. User enters their email address
3. System sends email with secure reset link (if email exists)
4. Link contains unique token valid for 1 hour
5. User clicks link and is taken to password reset form
6. User enters new password (with confirmation)
7. Password is updated, token is invalidated
8. User is redirected to login page with success message

#### Security Measures
- Reset tokens are single-use and expire after 1 hour
- Tokens are cryptographically secure (32+ random bytes)
- Same response shown whether email exists or not (prevents enumeration)
- Rate limiting: max 3 reset requests per email per hour
- Old tokens invalidated when new one is generated
- Password must meet minimum requirements (8+ characters)

#### Email Configuration
- Uses PHP's `mail()` function or configurable SMTP
- Config options in `config.php`:
  - `MAIL_FROM`: Sender email address
  - `MAIL_FROM_NAME`: Sender name (e.g., "Razor Library")
  - `SMTP_HOST`: Optional SMTP server
  - `SMTP_PORT`: Optional SMTP port
  - `SMTP_USER`: Optional SMTP username
  - `SMTP_PASS`: Optional SMTP password

#### Reset Email Template
```
Subject: Reset your Razor Library password

Hi {username},

You requested a password reset for your Razor Library account.

Click the link below to reset your password:
{reset_url}

This link will expire in 1 hour.

If you didn't request this, you can safely ignore this email.

- Razor Library
```

### 9.4 Collection Export

Users can download a complete backup of their collection as a compressed ZIP file.

#### Export Contents
```
{username}_collection_{date}.zip
├── razors/
│   ├── razor-name-1/
│   │   ├── razor-name-1.md
│   │   ├── hero.jpg
│   │   └── images/
│   │       ├── image1.jpg
│   │       └── image2.jpg
│   └── razor-name-2/
│       └── ...
├── blades/
│   └── blade-name-1/
│       ├── blade-name-1.md
│       └── ...
├── brushes/
│   └── brush-name-1/
│       ├── brush-name-1.md
│       └── ...
├── other/
│   ├── bowls/
│   │   └── ...
│   ├── soaps/
│   │   └── ...
│   ├── balms/
│   │   └── ...
│   ├── splashes/
│   │   └── ...
│   └── fragrances/
│       └── ...
└── README.md
```

#### Markdown File Format
Each item generates a markdown file with all its data:

```markdown
# {Item Name}

## Details
- **Added:** {date}
- **Last Updated:** {date}

## Description
{description text}

## Notes
{notes text}

## Specifications
{category-specific fields as key-value pairs}

## Related URLs
- [{description}]({url})
- ...

## Blade Usage (razors only)
| Blade | Uses |
|-------|------|
| Blade Name | 5 |
| ... | ... |

## Images
- hero.jpg
- images/image1.jpg
- ...
```

#### Export Options
- **Full Export**: All items with all images (default)
- **Metadata Only**: Markdown files without images (smaller file size)

#### Export Behavior
- Generates ZIP file server-side
- Downloads immediately to user's device
- Filename includes username and export date
- Soft-deleted items are NOT included
- Images are organized in item-specific folders
- README.md at root explains the export structure

---

## 10. Image Handling

### 10.1 Upload Processing
- Accept: JPEG, PNG, WebP, GIF
- Max upload size: 10MB
- Auto-resize: Max 1200px on longest edge
- Generate thumbnail: 300px for tile views
- Storage format: UUID filename (e.g., `a1b2c3d4-5678-90ab-cdef.jpg`)
- Preserve original format

### 10.2 Storage Structure
```
/uploads/
  /users/{user_id}/
    /razors/{uuid}.jpg
    /razors/{uuid}_thumb.jpg
    /blades/{uuid}.jpg
    /blades/{uuid}_thumb.jpg
    /brushes/{uuid}.jpg
    /brushes/{uuid}_thumb.jpg
    /other/{uuid}.jpg
    /other/{uuid}_thumb.jpg
  /system/
    splash.jpg
```

### 10.3 Placeholder
- Default placeholder image for items without hero image
- SVG or simple generated placeholder

---

## 11. Database Schema

### 11.1 Tables

#### users
| Column | Type | Notes |
|--------|------|-------|
| id | INTEGER | Primary key, auto-increment |
| username | TEXT | Unique |
| email | TEXT | Unique |
| password_hash | TEXT | bcrypt hash |
| is_admin | INTEGER | 0 or 1 |
| share_token | TEXT | Unique, nullable |
| reset_token | TEXT | Nullable, for password reset |
| reset_token_expires | DATETIME | Nullable, token expiry time |
| created_at | DATETIME | |
| deleted_at | DATETIME | Nullable, soft delete |

#### razors
| Column | Type | Notes |
|--------|------|-------|
| id | INTEGER | Primary key |
| user_id | INTEGER | Foreign key |
| name | TEXT | Required |
| description | TEXT | Nullable |
| notes | TEXT | Nullable |
| hero_image | TEXT | UUID filename, nullable |
| created_at | DATETIME | |
| updated_at | DATETIME | |
| deleted_at | DATETIME | Nullable, soft delete |

#### blades
| Column | Type | Notes |
|--------|------|-------|
| id | INTEGER | Primary key |
| user_id | INTEGER | Foreign key |
| name | TEXT | Required |
| description | TEXT | Nullable |
| notes | TEXT | Nullable |
| hero_image | TEXT | UUID filename, nullable |
| created_at | DATETIME | |
| updated_at | DATETIME | |
| deleted_at | DATETIME | Nullable, soft delete |

#### razor_images
| Column | Type | Notes |
|--------|------|-------|
| id | INTEGER | Primary key |
| razor_id | INTEGER | Foreign key |
| filename | TEXT | UUID filename |
| created_at | DATETIME | |

#### blade_images
| Column | Type | Notes |
|--------|------|-------|
| id | INTEGER | Primary key |
| blade_id | INTEGER | Foreign key |
| filename | TEXT | UUID filename |
| created_at | DATETIME | |

#### razor_urls
| Column | Type | Notes |
|--------|------|-------|
| id | INTEGER | Primary key |
| razor_id | INTEGER | Foreign key |
| url | TEXT | |
| description | TEXT | Nullable |
| created_at | DATETIME | |

#### blade_urls
| Column | Type | Notes |
|--------|------|-------|
| id | INTEGER | Primary key |
| blade_id | INTEGER | Foreign key |
| url | TEXT | |
| description | TEXT | Nullable |
| created_at | DATETIME | |

#### blade_usage
| Column | Type | Notes |
|--------|------|-------|
| id | INTEGER | Primary key |
| razor_id | INTEGER | Foreign key |
| blade_id | INTEGER | Foreign key |
| count | INTEGER | Default 0 |
| updated_at | DATETIME | |

#### brushes
| Column | Type | Notes |
|--------|------|-------|
| id | INTEGER | Primary key |
| user_id | INTEGER | Foreign key |
| name | TEXT | Required |
| description | TEXT | Nullable |
| notes | TEXT | Nullable |
| hero_image | TEXT | UUID filename, nullable |
| bristle_type | TEXT | Nullable (Badger, Boar, Synthetic, Horse, Mixed) |
| badger_grade | TEXT | Nullable (Silvertip, Super, Best, Pure) |
| knot_size_mm | INTEGER | Nullable |
| loft_height_mm | INTEGER | Nullable |
| handle_material | TEXT | Nullable |
| handle_height_mm | INTEGER | Nullable |
| manufacturer | TEXT | Nullable |
| country_of_origin | TEXT | Nullable |
| usage_count | INTEGER | Default 0 |
| created_at | DATETIME | |
| updated_at | DATETIME | |
| deleted_at | DATETIME | Nullable, soft delete |

#### brush_images
| Column | Type | Notes |
|--------|------|-------|
| id | INTEGER | Primary key |
| brush_id | INTEGER | Foreign key |
| filename | TEXT | UUID filename |
| created_at | DATETIME | |

#### brush_urls
| Column | Type | Notes |
|--------|------|-------|
| id | INTEGER | Primary key |
| brush_id | INTEGER | Foreign key |
| url | TEXT | |
| description | TEXT | Nullable |
| created_at | DATETIME | |

#### other_items
| Column | Type | Notes |
|--------|------|-------|
| id | INTEGER | Primary key |
| user_id | INTEGER | Foreign key |
| name | TEXT | Required |
| category | TEXT | Required (bowls, soaps, balms, splashes, fragrances) |
| description | TEXT | Nullable |
| notes | TEXT | Nullable |
| hero_image | TEXT | UUID filename, nullable |
| created_at | DATETIME | |
| updated_at | DATETIME | |
| deleted_at | DATETIME | Nullable, soft delete |

#### other_item_images
| Column | Type | Notes |
|--------|------|-------|
| id | INTEGER | Primary key |
| other_item_id | INTEGER | Foreign key |
| filename | TEXT | UUID filename |
| created_at | DATETIME | |

#### other_item_urls
| Column | Type | Notes |
|--------|------|-------|
| id | INTEGER | Primary key |
| other_item_id | INTEGER | Foreign key |
| url | TEXT | |
| description | TEXT | Nullable |
| created_at | DATETIME | |

#### other_item_attributes
| Column | Type | Notes |
|--------|------|-------|
| id | INTEGER | Primary key |
| other_item_id | INTEGER | Foreign key |
| attribute_name | TEXT | Field name (e.g., 'material', 'scent_notes') |
| attribute_value | TEXT | Field value |
| created_at | DATETIME | |

*Note: The `other_item_attributes` table uses an EAV (Entity-Attribute-Value) pattern to store category-specific fields flexibly without requiring schema changes for each category.*

### 11.2 Database Initialization
- Auto-create database file if not exists
- Run migrations on each app start
- Schema versioning to handle updates
- Never overwrite existing data

---

## 12. Design Theme

### 12.1 Style: Modern Minimal with Barbershop Accents

#### Colors
| Use | Color |
|-----|-------|
| Background | White/Light grey (#FAFAFA) |
| Text | Dark charcoal (#333333) |
| Accent Primary | Classic barber red (#C41E3A) |
| Accent Secondary | Navy blue (#1C3A5F) |
| Stripe accent | Red/white/blue subtle stripes |

#### Typography
- Headings: Clean serif (e.g., Playfair Display)
- Body: Modern sans-serif (e.g., Inter, Open Sans)

#### Design Elements
- Subtle barber stripe accents on headers/dividers
- Clean card-based layouts
- Generous whitespace
- Subtle shadows for depth
- Responsive grid for tiles

### 12.2 Mobile Responsiveness

#### Breakpoints
| Breakpoint | Width | Layout |
|------------|-------|--------|
| Mobile | < 576px | Single column |
| Tablet | 576px - 992px | 2 columns |
| Desktop | > 992px | 3-4 columns |

#### Mobile Navigation
- Hamburger menu icon in header
- Slide-out or dropdown navigation panel
- Touch-friendly tap targets (min 44px)
- Sticky header for easy navigation access

#### Mobile Tile Views
- Single column layout on small screens
- Larger touch targets for cards
- Swipe-friendly interactions
- Tiles stack vertically with full-width cards

#### Mobile Detail Pages
- Full-width images
- Stacked form fields (single column)
- Large, touch-friendly buttons
- Collapsible sections for long content
- Floating action button for primary actions (edit/save)

#### Mobile Forms
- Full-width input fields
- Larger font size for readability (min 16px to prevent zoom)
- Adequate spacing between form elements
- Native select dropdowns for better mobile UX
- Touch-friendly increment/decrement controls for usage counts

#### Mobile Image Handling
- Pinch-to-zoom on detail images
- Swipeable image galleries
- Optimized thumbnail loading
- Lazy loading for tile views

#### Touch Interactions
- Swipe gestures for image galleries
- Pull-to-refresh on list views (optional)
- Long-press for quick actions (optional)
- No hover-dependent features (all interactions work with tap)

---

## 13. Security Considerations

- CSRF tokens on all forms
- Prepared statements for all database queries
- Input sanitization and validation
- Secure session handling
- Password strength requirements (min 8 characters)
- Rate limiting on login attempts
- Validate file uploads (type, size)
- Serve uploaded images with proper headers

---

## 14. Git & Deployment

### 14.1 .gitignore
```
# Database
*.db
*.sqlite
/data/

# Uploaded files
/uploads/

# Local config
config.local.php

# IDE
.idea/
.vscode/
*.swp

# OS
.DS_Store
Thumbs.db
```

### 14.2 Directory Structure
```
/razor-library/
  /public/              # Web root
    index.php           # Entry point
    /assets/
      /css/
      /js/
      /images/          # Static images (icons, placeholders)
  /src/
    /Controllers/
    /Models/
    /Views/
    /Middleware/
    /Helpers/
  /data/                # SQLite database (gitignored)
  /uploads/             # User uploads (gitignored)
  /migrations/          # Database migrations
  config.php            # Default config
  config.local.php      # Local overrides (gitignored)
```

### 14.3 Deployment Notes
- Database auto-creates on first run
- Migrations run automatically
- Upload directories auto-created with proper permissions
- No manual setup required after git pull

---

## 15. Future Considerations (Out of Scope)

These features are not included in the initial build but noted for potential future expansion:
- Email notifications (beyond password reset)
- Import collection from backup
- Multiple share links per user
- Public user discovery/directory
- API for mobile app

---

## 16. Page Routes

| Route | Access | Description |
|-------|--------|-------------|
| `/` | Public | Landing/splash page with login |
| `/login` | Public | Login form |
| `/logout` | Auth | End session |
| `/forgot-password` | Public | Request password reset email |
| `/reset-password/{token}` | Public | Reset password form (with valid token) |
| `/dashboard` | Auth | User home, redirects to razors |
| `/razors` | Auth | Razor collection tiles |
| `/razors/{id}` | Auth | Razor detail/edit |
| `/razors/new` | Auth | Add new razor |
| `/blades` | Auth | Blade collection tiles |
| `/blades/{id}` | Auth | Blade detail/edit |
| `/blades/new` | Auth | Add new blade |
| `/brushes` | Auth | Brush collection tiles |
| `/brushes/{id}` | Auth | Brush detail/edit |
| `/brushes/new` | Auth | Add new brush |
| `/other` | Auth | Other items tiles (with category tabs) |
| `/other/{id}` | Auth | Other item detail/edit |
| `/other/new` | Auth | Add new other item |
| `/profile` | Auth | User settings |
| `/profile/export` | Auth | Download collection backup (ZIP) |
| `/admin` | Admin | User management |
| `/share/{token}` | Public | Shared collection view |
| `/share/{token}/razors` | Public | Shared razors |
| `/share/{token}/razors/{id}` | Public | Shared razor detail |
| `/share/{token}/blades` | Public | Shared blades |
| `/share/{token}/blades/{id}` | Public | Shared blade detail |
| `/share/{token}/brushes` | Public | Shared brushes |
| `/share/{token}/brushes/{id}` | Public | Shared brush detail |
| `/share/{token}/other` | Public | Shared other items |
| `/share/{token}/other/{id}` | Public | Shared other item detail |

---

## 17. Acceptance Criteria

### Must Have
- [ ] User authentication (login/logout)
- [ ] Admin can create users (with email)
- [ ] First user becomes admin
- [ ] User email address in profile
- [ ] Password reset via email link
- [ ] CRUD operations for razors
- [ ] CRUD operations for blades
- [ ] CRUD operations for brushes
- [ ] CRUD operations for other items (with subcategories)
- [ ] Image upload with resize/thumbnail
- [ ] Blade usage tracking per razor
- [ ] Brush usage count tracking
- [ ] Share link generation
- [ ] Read-only shared view
- [ ] Soft delete with data preservation
- [ ] Responsive design (mobile, tablet, desktop)
- [ ] Mobile-friendly navigation (hamburger menu)
- [ ] Touch-friendly UI (min 44px tap targets)
- [ ] Barbershop-themed styling
- [ ] Auto-database creation
- [ ] Proper .gitignore

### Should Have
- [ ] Sort options on tile views
- [ ] Placeholder images for items without photos
- [ ] Multiple images per item
- [ ] Related URLs with descriptions
- [ ] Copy share link button
- [ ] CSRF protection
- [ ] Input validation
- [ ] Category tabs/filter for Other section
- [ ] Brush-specific fields (bristle type, knot size, loft, etc.)
- [ ] Category-specific fields for Other items
- [ ] Collection export (ZIP with images and markdown files)
- [ ] Export option: full vs metadata-only
- [ ] Rate limiting on password reset requests
- [ ] Configurable SMTP for email delivery

### Nice to Have
- [ ] Image reordering
- [ ] Bulk operations
- [ ] Search/filter functionality
- [ ] Badger grade conditional field (only shows when bristle type is Badger)
- [ ] Swipeable image galleries on mobile
- [ ] Pull-to-refresh on list views
- [ ] Lazy loading for images
