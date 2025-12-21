# Razor Library v2 Specification

This document defines the features and implementation details for version 2 of Razor Library.

---

## 1. Subscription System (Buy Me a Coffee Integration)

### 1.1 Overview
User access is controlled via Buy Me a Coffee memberships. New users get a 7-day trial. After trial/subscription expires, users are blocked from the app and shown a landing page prompting them to subscribe. Admin accounts are exempt from subscription requirements.

### 1.2 Database Changes

**Modify `users` table:**
```sql
ALTER TABLE users ADD COLUMN subscription_status TEXT DEFAULT 'trial';  -- trial, active, expired, cancelled
ALTER TABLE users ADD COLUMN subscription_started_at DATETIME;
ALTER TABLE users ADD COLUMN subscription_expires_at DATETIME;
ALTER TABLE users ADD COLUMN bmac_member_id TEXT;                        -- Buy Me a Coffee member ID
ALTER TABLE users ADD COLUMN deletion_requested_at DATETIME;             -- For soft delete with recovery
ALTER TABLE users ADD COLUMN deletion_scheduled_at DATETIME;             -- When purge will occur (30 days after request)
```

**New table: `subscription_config`** (single row, admin-managed)
```sql
CREATE TABLE subscription_config (
    id INTEGER PRIMARY KEY CHECK (id = 1),  -- Ensure single row
    bmac_access_token TEXT,                  -- Buy Me a Coffee API token
    bmac_webhook_secret TEXT,                -- Webhook signature verification
    trial_days INTEGER DEFAULT 7,
    subscription_check_enabled INTEGER DEFAULT 1,  -- Toggle to disable checks
    expired_message TEXT,                    -- Custom message on expired page
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**New table: `subscription_events`** (audit log)
```sql
CREATE TABLE subscription_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    event_type TEXT NOT NULL,               -- trial_started, activated, expired, cancelled, renewed
    bmac_transaction_id TEXT,
    details TEXT,                           -- JSON with additional info
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_subscription_events_user_id ON subscription_events(user_id);
```

### 1.3 Subscription States

| Status | Description | App Access | Share Links |
|--------|-------------|------------|-------------|
| `trial` | New user within trial period | Full access | Active |
| `active` | Paid subscription via BMaC | Full access | Active |
| `expired` | Trial ended or subscription lapsed | **Blocked** - landing page only | **Disabled** |
| `cancelled` | User cancelled subscription | **Blocked** - landing page only | **Disabled** |

**Admin exemption:** Users with `is_admin = 1` bypass all subscription checks.

**Share links:** When a user's subscription expires, their public share links will return an error page explaining the collection is no longer available.

### 1.4 User Flows

**New user registration:**
1. Account created (via admin or account request approval)
2. `subscription_status` = 'trial'
3. `subscription_started_at` = now
4. `subscription_expires_at` = now + 7 days (configurable)

**Trial expiry:**
1. Middleware checks `subscription_expires_at` on each request
2. If expired and status is 'trial', update to 'expired'
3. Redirect to `/subscription/expired`

**Expired user access:**
1. User logs in successfully
2. Middleware detects expired status
3. Redirect to `/subscription/expired` (cannot access any other page)
4. Landing page shows: message, "Subscribe" button (links to BMaC), "Download My Data" option

**Subscription activation (webhook):**
1. BMaC sends webhook on membership purchase
2. Verify webhook signature
3. Match user by email
4. Update `subscription_status` = 'active', set new `subscription_expires_at`
5. Log event to `subscription_events`

### 1.5 Buy Me a Coffee Integration

**API endpoints used:**
- Webhooks for membership events (new, cancelled, expired)
- Optionally: API to verify membership status on-demand

**Webhook endpoint:** `POST /webhooks/bmac`
- Verify signature using `bmac_webhook_secret`
- Handle events: `membership.started`, `membership.cancelled`, `membership.expired`
- **Unmatched webhooks:** If webhook email doesn't match any user, log the event and notify admin via email. Admin can manually link payment to user.

**Admin configuration page:** `/admin/subscription`
- Set BMaC access token
- Set webhook secret
- Configure trial duration (days)
- Toggle subscription enforcement on/off
- Custom expired page message
- Test webhook connection
- **Manual subscription override:** Admin can set any user's subscription status and expiry date directly (useful for gifts, promotions, or webhook issues)

### 1.6 Expired Landing Page

**Route:** `/subscription/expired`

**Content:**
- Friendly message explaining subscription required
- Show remaining data summary (X razors, X blades, etc.)
- "Subscribe Now" button → BMaC membership page
- "Download My Data" button → export ZIP (allow even when expired)
- "Delete My Account" link → account deletion flow

### 1.7 New Files

- `src/Controllers/SubscriptionController.php` - Expired page, webhook handler
- `src/Helpers/SubscriptionChecker.php` - Middleware for subscription validation
- `src/Helpers/BmacClient.php` - Buy Me a Coffee API/webhook utilities
- `src/Views/subscription/expired.php` - Expired landing page
- `src/Views/admin/subscription.php` - Admin config page

---

## 2. Account Deletion

### 2.1 Overview
Users can request deletion of all their data. Deletion is soft with a 30-day recovery window before permanent purge.

### 2.2 User Flow

**Initiate deletion:** Profile page (`/profile`) → "Delete My Account" section

**Deletion request page:** `/profile/delete`
- Warning message explaining what will be deleted
- Recommendation to download backup first (with button)
- Confirmation: type "DELETE MY ACCOUNT" to confirm
- CSRF protected

**After request submitted:**
1. Set `deletion_requested_at` = now
2. Set `deletion_scheduled_at` = now + 30 days
3. Log user out
4. Send confirmation email with: deletion date, recovery instructions
5. User sees confirmation page

**Recovery:** `/profile/recover` (accessible while logged in during 30-day window)
- If user logs back in within 30 days, show banner: "Your account is scheduled for deletion on {date}. [Cancel Deletion]"
- Cancel clears `deletion_requested_at` and `deletion_scheduled_at`

**Permanent purge:** Background job or cron
- Query users where `deletion_scheduled_at < now`
- For each: delete all images from filesystem, hard delete all user data
- Log purge event

### 2.3 What Gets Deleted

- User account record
- All razors, blades, brushes, other items
- All images (hero images and gallery images)
- All URLs associated with items
- All blade usage records
- All API keys
- All subscription events

### 2.4 Admin Visibility

**Admin panel:**
- Show users with pending deletion requests
- Admin can: expedite deletion or cancel deletion on behalf of user

### 2.5 New Files

- `src/Views/profile/delete.php` - Deletion request page
- `src/Views/profile/delete-confirmed.php` - Confirmation page
- `scripts/purge-deleted-accounts.php` - CLI script for permanent deletion

---

## 3. REST API

### 3.1 Overview
Add API access for reading and updating collection entries. Each user can create multiple named API keys to authenticate requests.

**Subscription requirement:** API access requires active subscription. Returns 403 with `SUBSCRIPTION_REQUIRED` error code when expired.

### 3.2 Database Changes

**New table: `api_keys`**
```sql
CREATE TABLE api_keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,              -- User-friendly name for the key
    key_hash TEXT NOT NULL,          -- SHA-256 hash of the API key
    key_prefix TEXT NOT NULL,        -- First 8 chars for identification (e.g., "rl_abc123")
    last_used_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME,             -- Soft revoke instead of delete
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_api_keys_user_id ON api_keys(user_id);
CREATE INDEX idx_api_keys_key_hash ON api_keys(key_hash);
```

### 3.3 API Key Management

**Location:** Profile page (`/profile`) - new "API Keys" section

**Features:**
- Generate new API key with custom name
- Display key once on creation (never shown again)
- List existing keys showing: name, prefix, created date, last used date
- Revoke individual keys

**Key format:** `rl_` prefix + 32 random hex characters (e.g., `rl_a1b2c3d4e5f6...`)

### 3.4 API Endpoints

**Base path:** `/api/v1`

**Authentication:** `Authorization: Bearer <api_key>` header

**Subscription check:** All endpoints verify user has active subscription (admin exempt)

**Endpoints:**

| Method | Path | Description |
|--------|------|-------------|
| GET | `/razors` | List all razors |
| GET | `/razors/{id}` | Get single razor |
| POST | `/razors` | Create razor |
| PUT | `/razors/{id}` | Update razor |
| DELETE | `/razors/{id}` | Soft delete razor |
| GET | `/blades` | List all blades |
| GET | `/blades/{id}` | Get single blade |
| POST | `/blades` | Create blade |
| PUT | `/blades/{id}` | Update blade |
| DELETE | `/blades/{id}` | Soft delete blade |
| GET | `/brushes` | List all brushes |
| GET | `/brushes/{id}` | Get single brush |
| POST | `/brushes` | Create brush |
| PUT | `/brushes/{id}` | Update brush |
| DELETE | `/brushes/{id}` | Soft delete brush |
| GET | `/other` | List all other items |
| GET | `/other/{id}` | Get single other item |
| POST | `/other` | Create other item |
| PUT | `/other/{id}` | Update other item |
| DELETE | `/other/{id}` | Soft delete other item |

**Response format:** JSON with consistent structure
```json
{
  "success": true,
  "data": { ... }
}
```

**Error format:**
```json
{
  "success": false,
  "error": "Error message",
  "code": "ERROR_CODE"
}
```

**Error codes:**
- `UNAUTHORIZED` - Invalid or missing API key
- `SUBSCRIPTION_REQUIRED` - Subscription expired (403)
- `NOT_FOUND` - Resource not found
- `VALIDATION_ERROR` - Invalid input data
- `RATE_LIMITED` - Too many requests

### 3.5 API Documentation

**Location:** `/api/docs` (public page, no auth required)

**Implementation:** Static HTML page with:
- Authentication instructions
- Endpoint reference with request/response examples
- Rate limiting information (use existing RateLimiter)
- Subscription requirements note

### 3.6 New Files

- `src/Controllers/ApiController.php` - API endpoint handlers
- `src/Helpers/ApiAuth.php` - API key validation + subscription check
- `src/Views/profile/api-keys.php` - API key management partial
- `src/Views/api/docs.php` - API documentation page

---

## 4. Account Requests

### 4.1 Overview
Allow visitors to request an account. Admin reviews and approves/rejects requests. Approved users start with a 7-day trial.

### 4.2 Database Changes

**New table: `account_requests`**
```sql
CREATE TABLE account_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL,
    email TEXT NOT NULL,
    reason TEXT,                     -- Optional: why they want an account
    status TEXT DEFAULT 'pending',   -- pending, approved, rejected
    reviewed_by INTEGER,             -- Admin user_id who reviewed
    reviewed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
);
CREATE INDEX idx_account_requests_status ON account_requests(status);
```

### 4.3 User Flow

**Login page (`/login`):**
- Add "Request an Account" button in bottom-right corner
- Links to `/request-account`

**Request account page (`/request-account`):**
- Form fields: Username, Email, Reason (optional textarea)
- Display Terms and Conditions (must accept checkbox)
- CSRF protection and rate limiting
- Success message: "Your request has been submitted. You will receive an email when reviewed."

### 4.4 Admin Flow

**Admin panel (`/admin`):**
- New "Pending Requests" section showing count badge
- List pending requests with: username, email, reason, date
- Actions: Approve (creates user with trial, sends welcome email) or Reject (sends rejection email)

**On approval:**
1. Create user account
2. Set `subscription_status` = 'trial'
3. Set `subscription_expires_at` = now + trial_days
4. Generate temporary password
5. Send welcome email with login credentials and trial info

**Email notifications:**
- To admin: "New account request from {username}"
- To user on approval: "Your account has been approved" with login link, temp password, trial expiry date
- To user on rejection: "Your account request was not approved"

### 4.5 Terms and Conditions

**Content requirements:**
- Standard website usage terms
- Uploaded images must be shaving-related
- Users retain ownership of their uploaded images
- Right to remove inappropriate content
- Data privacy statement
- Subscription and trial terms

**Location:** `/terms` (public page)

### 4.6 New Files

- `src/Controllers/AccountRequestController.php`
- `src/Views/auth/request-account.php`
- `src/Views/admin/pending-requests.php` (partial)
- `src/Views/legal/terms.php`

---

## 5. New Item Fields

### 5.1 Razors: Year of Manufacture

**Field:** `year_manufactured` (INTEGER, nullable)

**Database migration:**
```sql
ALTER TABLE razors ADD COLUMN year_manufactured INTEGER;
```

**Display behavior:**
- Show in title/tile when present: "Gillette Slim (1963)"
- Show on detail page in specifications section
- Include in CSV import/export

### 5.2 Razors: Country of Manufacture

**Field:** `country_manufactured` (TEXT, nullable)

**Database migration:**
```sql
ALTER TABLE razors ADD COLUMN country_manufactured TEXT;
```

### 5.3 Blades: Country of Manufacture

**Field:** `country_manufactured` (TEXT, nullable)

**Database migration:**
```sql
ALTER TABLE blades ADD COLUMN country_manufactured TEXT;
```

### 5.4 Form Updates

**Razor create/edit forms:**
- Add "Year of Manufacture" number input (optional, 4-digit year validation)
- Add "Country of Manufacture" text input (optional)

**Blade create/edit forms:**
- Add "Country of Manufacture" text input (optional)

### 5.5 CSV Import/Export Updates

**Razor template columns:** Add `YearManufactured`, `CountryManufactured`
**Blade template columns:** Add `CountryManufactured`

---

## 6. Implementation Order

Recommended sequence for implementation:

1. **New item fields** (smallest change, immediate user value)
   - Migration for year_manufactured and country_manufactured
   - Update forms and views
   - Update CSV import/export

2. **Subscription system foundation**
   - Database migrations for subscription fields
   - Admin configuration page for BMaC settings
   - Subscription checker middleware
   - Expired landing page

3. **Buy Me a Coffee webhook integration**
   - Webhook endpoint
   - Signature verification
   - Membership event handling

4. **Terms and Conditions**
   - Create terms page
   - Add link to footer

5. **Account Requests**
   - Database migration
   - Request form with terms acceptance
   - Admin review interface (with trial activation)
   - Email notifications

6. **Account Deletion**
   - Deletion request flow
   - Recovery mechanism
   - Purge script

7. **REST API**
   - API key management
   - API endpoints with subscription checks
   - API documentation

---

## 7. Migration Files

New migrations to create:

- `012_add_razor_manufacture_fields.php` - year_manufactured, country_manufactured for razors
- `013_add_blade_country_manufactured.php` - country_manufactured for blades
- `014_add_subscription_fields.php` - subscription columns on users table
- `015_create_subscription_config_table.php`
- `016_create_subscription_events_table.php`
- `017_create_account_requests_table.php`
- `018_create_api_keys_table.php`
- `019_add_deletion_fields.php` - deletion_requested_at, deletion_scheduled_at on users

---

## 8. Configuration

### 8.1 New Config Options

Add to `config.php` defaults:
```php
// Subscription
'SUBSCRIPTION_ENABLED' => true,           // Master toggle
'TRIAL_DAYS' => 7,                         // Default trial period
'DELETION_RECOVERY_DAYS' => 30,            // Days before permanent purge

// Buy Me a Coffee (set in config.local.php or admin panel)
'BMAC_ACCESS_TOKEN' => '',
'BMAC_WEBHOOK_SECRET' => '',
'BMAC_MEMBERSHIP_URL' => '',               // Link to your BMaC membership page
```

### 8.2 Admin-Configurable Settings

These can be set via admin panel and stored in `subscription_config` table:
- BMaC access token
- BMaC webhook secret
- Trial duration
- Subscription enforcement toggle
- Custom expired page message
- BMaC membership page URL

---

## 9. Documentation Updates

All documentation must be updated to reflect v2 changes.

### 9.1 README.md Updates

**Features section - Add:**
- Subscription-based access with 7-day free trial
- Buy Me a Coffee membership integration
- REST API for external integrations
- Account request system for new users
- Self-service account deletion with 30-day recovery

**Requirements section - Add:**
- Note about Buy Me a Coffee account for subscription management

**CLI Scripts section - Add:**
```bash
# Purge accounts scheduled for deletion
php scripts/purge-deleted-accounts.php

# Run as daily cron job
0 0 * * * php /path/to/scripts/purge-deleted-accounts.php
```

**CSV Format section - Update:**
- Razors: `Brand, Name, YearManufactured, CountryManufactured, UseCount, Notes`
- Blades: `Brand, Name, CountryManufactured, Notes`

**New section: API Access**
```markdown
### API Access

Razor Library provides a REST API for external integrations:

1. Go to **Profile** > **API Keys**
2. Click "Generate New Key"
3. Name your key and copy it (shown only once)
4. Use the key in the `Authorization: Bearer <key>` header

See `/api/docs` for full API documentation.

**Note:** API access requires an active subscription.
```

**New section: Subscription System**
```markdown
### Subscription System

Razor Library uses Buy Me a Coffee for subscription management:

- New accounts receive a 7-day free trial
- After trial, a subscription is required to access the app
- Expired users can still download their data
- Admin accounts are exempt from subscription requirements

Configure subscription settings in **Admin** > **Subscription Settings**.
```

### 9.2 docs/user-guide.md Updates

**Table of Contents - Add:**
- Subscription & Trial
- API Keys
- Deleting Your Account

**Getting Started section - Update:**
```markdown
### Creating an Account

New to Razor Library? Here's how to get started:

1. Click "Request an Account" on the login page
2. Fill in your details and accept the Terms and Conditions
3. Wait for admin approval (you'll receive an email)
4. Once approved, you'll get a 7-day free trial
5. Subscribe via Buy Me a Coffee to continue after the trial
```

**New section: Subscription & Trial**
```markdown
## Subscription & Trial

### Free Trial

All new accounts receive a 7-day free trial with full access to all features.

### After Your Trial

When your trial expires:
- You'll see a subscription prompt when logging in
- You can still download your data
- You can delete your account if desired

### Subscribing

1. Click the "Subscribe" button on the expiration page
2. Complete payment through Buy Me a Coffee
3. Your account will be activated automatically (may take a few minutes)

### Checking Your Status

Go to **Profile** to see your current subscription status and expiry date.
```

**New section: API Keys**
```markdown
## API Keys

Access your collection programmatically using the REST API.

### Creating an API Key

1. Go to **Profile**
2. Scroll to the "API Keys" section
3. Click "Generate New Key"
4. Enter a name for the key (e.g., "Home Assistant", "iOS Shortcut")
5. Copy the key immediately - it won't be shown again!

### Managing Keys

- View all your keys with their names and last-used dates
- Revoke keys you no longer need
- Keys are tied to your account and respect your subscription status

### Using the API

See the [API Documentation](/api/docs) for endpoints and examples.

**Note:** API access requires an active subscription.
```

**New section: Deleting Your Account**
```markdown
## Deleting Your Account

You can request deletion of your account and all associated data.

### Before You Delete

1. Go to **Profile** > **Export Collection** to download a backup
2. Your backup includes all items and images

### Requesting Deletion

1. Go to **Profile**
2. Scroll to "Delete My Account"
3. Click "Request Account Deletion"
4. Type `DELETE MY ACCOUNT` to confirm
5. Click "Delete My Account"

### What Happens Next

- You'll be logged out immediately
- You'll receive a confirmation email
- Your account is scheduled for permanent deletion in 30 days
- During the 30-day window, you can log back in to cancel the deletion

### Recovery

If you change your mind within 30 days:
1. Log in with your credentials
2. You'll see a banner about pending deletion
3. Click "Cancel Deletion" to restore your account
```

**Managing Your Collection section - Update for new fields:**
```markdown
### Adding Razors

When adding a razor, you can now also specify:
- **Year of Manufacture** - The year the razor was made (shown in title)
- **Country of Manufacture** - Where the razor was manufactured

### Adding Blades

When adding blades, you can now also specify:
- **Country of Manufacture** - Where the blades were manufactured
```

**Profile Settings section - Add subscription status:**
```markdown
### Viewing Subscription Status

On your Profile page you can see:
- Current subscription status (Trial, Active, Expired)
- Trial/subscription expiry date
- Link to subscribe or manage subscription
```

### 9.3 docs/admin-guide.md Updates

**Table of Contents - Add:**
- Subscription Management
- Account Requests
- Account Deletion Management
- API Configuration

**Administration Overview - Update admin privileges:**
```markdown
### Admin Privileges

Admin users can:
- Create, edit, and delete user accounts
- Grant or revoke admin privileges
- Review and approve/reject account requests
- Configure subscription settings (Buy Me a Coffee)
- View and manage pending account deletions
- Create and restore database backups
- Reset the database
- Access all standard user features
- **Bypass subscription requirements** (admins always have full access)
```

**New section: Subscription Management**
```markdown
## Subscription Management

### Configuring Buy Me a Coffee

1. Go to **Admin** > **Subscription Settings**
2. Enter your BMaC credentials:
   - **Access Token**: From your BMaC developer settings
   - **Webhook Secret**: For verifying webhook signatures
   - **Membership URL**: Link to your membership page
3. Configure trial duration (default: 7 days)
4. Click "Save Settings"

### Setting Up Webhooks

1. In your Buy Me a Coffee dashboard, go to Webhooks
2. Add a new webhook pointing to: `https://yourdomain.com/webhooks/bmac`
3. Copy the webhook secret to your admin settings
4. Test the connection using "Test Webhook" button

### Managing User Subscriptions

The User Management table now shows subscription status:
- **Trial** - User is in free trial period (shows days remaining)
- **Active** - User has active subscription
- **Expired** - Trial or subscription has ended
- **Cancelled** - User cancelled their subscription

### Disabling Subscription Checks

For private installations or testing:
1. Go to **Admin** > **Subscription Settings**
2. Toggle "Subscription Enforcement" to Off
3. All users will have full access regardless of status

**Warning:** This bypasses all subscription validation.
```

**New section: Account Requests**
```markdown
## Account Requests

### Reviewing Requests

When users request an account:
1. You'll receive an email notification
2. Go to **Admin** - pending requests show as a badge
3. Review each request (username, email, reason)
4. Click "Approve" or "Reject"

### Approving Requests

When you approve a request:
1. A user account is created automatically
2. The user starts with a 7-day trial
3. A temporary password is generated
4. The user receives a welcome email with login credentials

### Rejecting Requests

When you reject a request:
- The user receives a notification email
- No account is created
- The request is marked as rejected (kept for records)
```

**New section: Account Deletion Management**
```markdown
## Account Deletion Management

### Viewing Pending Deletions

The Admin panel shows users with pending deletion requests:
- Username and email
- Deletion request date
- Scheduled purge date (30 days after request)

### Admin Actions

For each pending deletion, you can:
- **Cancel Deletion**: Restore the user's account
- **Expedite Deletion**: Immediately purge the account (use with caution)

### Automatic Purge

Accounts are permanently deleted 30 days after the request:
- All user data is removed from the database
- All uploaded images are deleted from the filesystem
- This process runs via the `purge-deleted-accounts.php` script

### Setting Up Automatic Purge

Add a daily cron job:
```bash
0 0 * * * php /path/to/razor-library/scripts/purge-deleted-accounts.php
```
```

**Command Line Tools section - Add:**
```markdown
### purge-deleted-accounts.php

Permanently delete accounts that have passed their 30-day recovery window:

```bash
# Run manually
php scripts/purge-deleted-accounts.php

# Dry run (show what would be deleted without deleting)
php scripts/purge-deleted-accounts.php --dry-run
```

Set up as a daily cron job for automatic purging.
```

**Configuration Reference - Add new settings:**
```markdown
| Setting | Default | Description |
|---------|---------|-------------|
| `SUBSCRIPTION_ENABLED` | true | Enable subscription checks |
| `TRIAL_DAYS` | 7 | Free trial duration |
| `DELETION_RECOVERY_DAYS` | 30 | Days before permanent deletion |
| `BMAC_ACCESS_TOKEN` | (empty) | Buy Me a Coffee API token |
| `BMAC_WEBHOOK_SECRET` | (empty) | Webhook signature secret |
| `BMAC_MEMBERSHIP_URL` | (empty) | Link to BMaC membership page |
```

**Troubleshooting section - Add:**
```markdown
### Subscription Issues

**User not activated after payment:**
1. Check webhook is configured correctly in BMaC
2. Verify webhook secret matches in admin settings
3. Check `subscription_events` table for webhook logs
4. Manually update user status if needed

**Webhooks not working:**
1. Verify your site is accessible from the internet
2. Check server logs for webhook requests
3. Test with "Test Webhook" button in admin
4. Ensure SSL certificate is valid

### Account Deletion Issues

**Purge script not running:**
1. Check cron job is configured correctly
2. Verify PHP path in cron command
3. Check script permissions (must be executable)
4. Review script output for errors

**User can't cancel deletion:**
1. Verify they're within the 30-day window
2. Check `deletion_scheduled_at` in database
3. Ensure they can log in (account not yet purged)
```

### 9.4 CLAUDE.md Updates

Add the following to the CLAUDE.md file:

```markdown
## v2 Features

**Subscription System:**
- Users have `subscription_status` (trial/active/expired/cancelled)
- Check subscription with `SubscriptionChecker` middleware
- Admin accounts (`is_admin = 1`) bypass all subscription checks
- Expired users redirected to `/subscription/expired`

**API:**
- API keys stored in `api_keys` table (hashed, with prefix for display)
- Authenticate via `Authorization: Bearer <key>` header
- All endpoints require active subscription (except admin)
- Endpoints at `/api/v1/{razors|blades|brushes|other}`

**Account Lifecycle:**
- Account requests in `account_requests` table
- Deletion uses `deletion_requested_at` and `deletion_scheduled_at`
- 30-day recovery window before permanent purge
- Purge script: `scripts/purge-deleted-accounts.php`

**New Item Fields:**
- `razors.year_manufactured` (INTEGER) - displayed in title as "(1963)"
- `razors.country_manufactured` (TEXT)
- `blades.country_manufactured` (TEXT)
```

---

## 10. Email Notifications

### 10.1 Overview
Extend the existing email system with subscription and account lifecycle notifications.

### 10.2 New Email Types

**Trial Expiry Warning (3 days before):**
- Subject: "Your Razor Library trial expires in 3 days"
- Content: Trial end date, subscribe link, collection summary
- Trigger: Daily cron job checks for trials expiring in 3 days

**Subscription Renewal Reminder (7 days before):**
- Subject: "Your Razor Library subscription expires soon"
- Content: Expiry date, renewal link, collection summary
- Trigger: Daily cron job checks for subscriptions expiring in 7 days

**Unmatched Webhook Alert (to admin):**
- Subject: "BMaC payment received - user not found"
- Content: BMaC email, transaction details, instructions to manually link
- Trigger: Webhook received with no matching user email

### 10.3 Email Schedule Script

**New file:** `scripts/send-scheduled-emails.php`
- Run daily via cron: `0 9 * * * php scripts/send-scheduled-emails.php`
- Checks for upcoming trial/subscription expirations
- Tracks sent emails to avoid duplicates (new `email_log` table)

### 10.4 Database Changes

**New table: `email_log`**
```sql
CREATE TABLE email_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    email_type TEXT NOT NULL,        -- trial_warning, renewal_reminder, etc.
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_email_log_user_type ON email_log(user_id, email_type);
```

---

## 11. Search and Filtering

### 11.1 Overview
Add search and filtering capabilities to collection pages.

### 11.2 Search Features

**Search box on collection pages:**
- Text search across name, brand, description, notes
- Real-time filtering as user types (JavaScript)
- Search persists when navigating between pages

### 11.3 Filter Options

**Razors filters:**
- Brand (dropdown of existing brands)
- Country of Manufacture (dropdown)
- Year of Manufacture range (min/max year inputs)
- Has images (yes/no)

**Blades filters:**
- Brand (dropdown)
- Country of Manufacture (dropdown)
- Has images (yes/no)

**Brushes filters:**
- Brand (dropdown)
- Bristle Type (dropdown)
- Handle Material (dropdown)

**Other Items filters:**
- Category (dropdown)
- Brand (dropdown)

### 11.6 Updated Sorting Options

**Razors sorting (updated for new fields):**
- Name (A-Z) - default
- Name (Z-A)
- Date Added (Newest)
- Date Added (Oldest)
- Most Used (blade usage count)
- Last Used
- Year of Manufacture (Newest)
- Year of Manufacture (Oldest)
- Country of Manufacture (A-Z)

**Blades sorting (updated for new fields):**
- Name (A-Z) - default
- Name (Z-A)
- Date Added (Newest)
- Date Added (Oldest)
- Most Used
- Last Used
- Country of Manufacture (A-Z)

**Brushes sorting (unchanged):**
- Name (A-Z) - default
- Name (Z-A)
- Date Added (Newest)
- Date Added (Oldest)
- Most Used
- Last Used

**Other Items sorting (unchanged):**
- Name (A-Z) - default
- Category
- Brand (A-Z)
- Date Added (Newest)

### 11.4 Implementation

**URL parameters:** `?search=term&brand=Gillette&country=USA`

**Controller pattern:**
```php
$filters = [
    'search' => $_GET['search'] ?? '',
    'brand' => $_GET['brand'] ?? '',
    'country' => $_GET['country'] ?? '',
];
// Build dynamic WHERE clause with prepared statements
```

**View updates:**
- Add filter bar above collection grid
- Show active filters with clear buttons
- Maintain filters when sorting

### 11.5 New Files

- `src/Views/partials/filter-bar.php` - Reusable filter component
- JavaScript in `public/assets/js/app.js` - Real-time search

---

## 12. Activity Logging

### 12.1 Overview
Admin-visible activity log for security and troubleshooting.

### 12.2 Events to Log

**Authentication:**
- User login (success/failure with IP)
- Password change
- Password reset request/completion

**Subscription:**
- Trial started
- Subscription activated/renewed
- Subscription expired/cancelled
- Manual subscription override by admin

**Account:**
- Account created (by admin or request approval)
- Account request submitted/approved/rejected
- Deletion requested/cancelled/completed
- Profile updated

**Admin actions:**
- User created/edited/deleted
- Backup created/restored
- Subscription settings changed

### 12.3 Database Changes

**New table: `activity_log`**
```sql
CREATE TABLE activity_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,                  -- NULL for system events
    action TEXT NOT NULL,             -- login, subscription_activated, etc.
    target_type TEXT,                 -- user, razor, subscription, etc.
    target_id INTEGER,
    details TEXT,                     -- JSON with additional context
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_activity_log_user_id ON activity_log(user_id);
CREATE INDEX idx_activity_log_action ON activity_log(action);
CREATE INDEX idx_activity_log_created_at ON activity_log(created_at);
```

### 12.4 Admin Interface

**Route:** `/admin/activity`

**Features:**
- Paginated activity list (newest first)
- Filter by action type
- Filter by user
- Filter by date range
- Search in details

### 12.5 New Files

- `src/Helpers/ActivityLogger.php` - Logging utility class
- `src/Views/admin/activity.php` - Activity log view

---

## 13. Email Change with Re-verification

### 13.1 Overview
Allow users to change their email address with verification to ensure BMaC subscription matching works correctly.

### 13.2 User Flow

1. User enters new email on Profile page
2. Verification email sent to NEW address with confirmation link
3. User clicks link to confirm new email
4. Old email receives notification that email was changed
5. Email updated in database

### 13.3 Database Changes

**Modify `users` table:**
```sql
ALTER TABLE users ADD COLUMN pending_email TEXT;
ALTER TABLE users ADD COLUMN email_verification_token TEXT;
ALTER TABLE users ADD COLUMN email_verification_expires DATETIME;
```

### 13.4 Security Considerations

- Verification token expires in 24 hours
- Old email notified of change (security alert)
- Rate limit email change requests (1 per hour)
- Log email changes to activity log

---

## 14. API Image Access

### 14.1 Overview
API returns image URLs but does not support image upload.

### 14.2 Image URL Format

API responses include image URLs that can be fetched directly:

```json
{
  "id": 123,
  "name": "Gillette Slim",
  "hero_image": "/uploads/users/1/razors/abc123.jpg",
  "hero_image_url": "https://example.com/uploads/users/1/razors/abc123.jpg",
  "images": [
    {
      "id": 1,
      "filename": "def456.jpg",
      "url": "https://example.com/uploads/users/1/razors/def456.jpg",
      "thumbnail_url": "https://example.com/uploads/users/1/razors/def456_thumb.jpg"
    }
  ]
}
```

### 14.3 Image Access Control

- Images are served via `uploads.php` which validates user ownership
- API users can only access their own images
- Share token images accessible without authentication (if subscription active)

---

## 15. Updated Migration Files

Additional migrations for new features:

- `020_create_email_log_table.php`
- `021_create_activity_log_table.php`
- `022_add_email_verification_fields.php`

---

## 16. Security Audit & Hardening

### 16.1 Critical Issues (Fix Immediately)

#### Password Column Naming Inconsistency
**Locations:** `migrations/001_create_users_table.php`, `AdminController.php`, `ProfileController.php`, `AuthController.php`

**Issue:** Database schema defines `password_hash` but code references `password`. This causes authentication failures.

**Fix:** Create migration to rename column OR update all code references:
```sql
-- Migration 023_rename_password_column.php
ALTER TABLE users RENAME COLUMN password_hash TO password;
```

#### XSS in Flash Messages
**Locations:** All view files using `<?= get_flash('error') ?>`

**Issue:** Flash messages output without HTML escaping.

**Fix:** Update `get_flash()` helper in `functions.php`:
```php
function get_flash(string $key): ?string
{
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message ? e($message) : null;
}
```

#### Deprecated mime_content_type()
**Location:** `ProfileController.php:640`

**Issue:** `mime_content_type()` deprecated in PHP 7.1+.

**Fix:**
```php
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
```

### 16.2 High Priority Issues

#### Session Fixation Prevention
**Location:** `public/index.php`, `AuthController.php`

**Issue:** No session regeneration after login, missing secure cookie flags.

**Fix in `index.php`:**
```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // If HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
session_start();
```

**Fix in `AuthController::login()` after successful authentication:**
```php
session_regenerate_id(true);
```

#### SQL Injection in ORDER BY Clauses
**Locations:** `RazorController.php:36`, `BladeController.php:28`, `BrushController.php:26`, `OtherController.php:42`

**Issue:** ORDER BY built via string interpolation.

**Fix:** Use strict whitelist mapping:
```php
$orderByMap = [
    'name_asc' => 'name ASC',
    'name_desc' => 'name DESC',
    'date_asc' => 'created_at ASC',
    'date_desc' => 'created_at DESC',
    // etc.
];
$orderBy = $orderByMap[$sort] ?? 'name ASC';
// Then use in query (still interpolated but from strict whitelist)
```

#### Security Headers
**Location:** `public/index.php`

**Issue:** No security headers set.

**Fix:** Add after session_start():
```php
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com; img-src 'self' data:;");
```

### 16.3 Medium Priority Issues

#### Hash Password Reset Tokens
**Location:** `AuthController.php` (forgot password flow)

**Issue:** Reset tokens stored in plain text.

**Fix:**
```php
// When creating token:
$token = generate_token(32);
$tokenHash = hash('sha256', $token);
Database::query(
    "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?",
    [$tokenHash, $expires, $userId]
);
// Send unhashed $token in email

// When verifying:
$tokenHash = hash('sha256', $tokenFromUrl);
$user = Database::fetch(
    "SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > CURRENT_TIMESTAMP",
    [$tokenHash]
);
```

#### Dual Rate Limiting (IP + Email)
**Location:** `AuthController.php`

**Issue:** Rate limiting only by IP; shared IPs cause lockouts, proxies bypass.

**Fix:**
```php
$ip = $_SERVER['REMOTE_ADDR'];
$email = trim($_POST['email'] ?? '');

// Limit by IP (stricter)
if (RateLimiter::isLimited($ip, 'login', 10, 900)) {
    flash('error', 'Too many attempts from this location.');
    redirect('/login');
}

// Also limit by email (after email provided)
if (!empty($email) && RateLimiter::isLimited($email, 'login_email', 5, 900)) {
    flash('error', 'Too many attempts for this account.');
    redirect('/login');
}
```

#### Password Strength Requirements
**Location:** `AuthController.php`, `ProfileController.php`, `AdminController.php`

**Issue:** Only minimum length (8 chars) enforced.

**Fix:** Add helper function:
```php
function validate_password_strength(string $password): array
{
    $errors = [];
    if (strlen($password) < 10) {
        $errors[] = 'Password must be at least 10 characters';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain an uppercase letter';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain a lowercase letter';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain a number';
    }
    return $errors;
}
```

#### Failed Login Notifications
**Location:** `AuthController.php`

**Issue:** No notification to user about suspicious login attempts.

**Fix:** After 3 failed attempts, email the account owner:
```php
if ($failedAttempts >= 3 && $user) {
    Mailer::sendSecurityAlert(
        $user['email'],
        $user['username'],
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    );
}
```

#### Invalidate Sessions on Password Change
**Location:** `ProfileController.php`, `AuthController.php`

**Issue:** Old sessions remain valid after password change.

**Fix:** Add session version to users table:
```sql
ALTER TABLE users ADD COLUMN session_version INTEGER DEFAULT 1;
```

On password change:
```php
Database::query("UPDATE users SET session_version = session_version + 1 WHERE id = ?", [$userId]);
```

In session validation:
```php
$user = Database::fetch("SELECT session_version FROM users WHERE id = ?", [$_SESSION['user_id']]);
if ($user['session_version'] !== $_SESSION['session_version']) {
    session_destroy();
    redirect('/login');
}
```

### 16.4 Low Priority Issues

#### Debug Information Disclosure
**Location:** `src/Helpers/functions.php:15`

**Issue:** View errors reveal file paths.

**Fix:**
```php
if (!file_exists($viewFile)) {
    error_log("View not found: {$name}");
    if (config('APP_DEBUG')) {
        return "View not found: {$name}";
    }
    return view('errors/500');
}
```

#### Backup Download CSRF Protection
**Location:** `AdminController.php:368`

**Issue:** Backup download via GET without CSRF.

**Fix:** Change to POST with CSRF, or add time-limited download tokens.

#### CSV Upload Validation
**Location:** `ProfileController.php:640-644`

**Issue:** MIME types too permissive.

**Fix:**
```php
$allowedMimeTypes = ['text/csv', 'text/plain'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv' || !in_array($mimeType, $allowedMimeTypes)) {
    flash('error', 'Please upload a valid CSV file.');
    redirect('/profile');
}
```

### 16.5 API Security (New for v2)

#### API Rate Limiting
```php
// Per-key rate limiting: 100 requests per minute
if (RateLimiter::isLimited($apiKeyPrefix, 'api', 100, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Rate limit exceeded', 'code' => 'RATE_LIMITED']);
    exit;
}
```

#### API Key Hashing
Store API keys hashed (like passwords):
```php
// On creation:
$key = 'rl_' . bin2hex(random_bytes(32));
$keyHash = hash('sha256', $key);
$keyPrefix = substr($key, 0, 11); // "rl_" + 8 chars

Database::query(
    "INSERT INTO api_keys (user_id, name, key_hash, key_prefix) VALUES (?, ?, ?, ?)",
    [$userId, $name, $keyHash, $keyPrefix]
);

// Return unhashed key to user (once only)
return $key;

// On validation:
$keyHash = hash('sha256', $providedKey);
$apiKey = Database::fetch("SELECT * FROM api_keys WHERE key_hash = ? AND revoked_at IS NULL", [$keyHash]);
```

#### API Response Sanitization
Never include sensitive fields in API responses:
```php
// Remove sensitive fields before returning
unset($user['password']);
unset($user['reset_token']);
unset($user['share_token']);
unset($user['session_version']);
```

### 16.6 Database Migration for Security Fixes

**New migration: `023_security_fixes.php`**
```php
<?php
return [
    // Fix password column name (if needed)
    "ALTER TABLE users RENAME COLUMN password_hash TO password",

    // Add session versioning
    "ALTER TABLE users ADD COLUMN session_version INTEGER DEFAULT 1",

    // Add failed login tracking
    "ALTER TABLE rate_limits ADD COLUMN notified_at DATETIME",
];
```

### 16.7 Security Checklist for Deployment

- [ ] HTTPS enforced (redirect HTTP → HTTPS)
- [ ] Security headers configured
- [ ] APP_DEBUG set to false
- [ ] config.local.php has restrictive permissions (600)
- [ ] uploads/ directory not directly browsable
- [ ] backups/ directory protected from web access
- [ ] Database file outside web root or protected
- [ ] SMTP credentials use environment variables
- [ ] Session cookie flags set (HttpOnly, Secure, SameSite)
- [ ] Error logging configured (not displayed to users)
- [ ] Rate limiting active on login, password reset, API
- [ ] Backup encryption enabled (future enhancement)

---

## 17. Updated Implementation Order

Revised sequence including security fixes:

1. **Security fixes** (Critical issues first - password column, XSS, session)
2. **Security headers and hardening**
3. **Error pages** (quick win, matches auth styling)
4. **New item fields** (smallest feature change)
5. **Activity logging** (foundation for other features)
6. **Email change with re-verification**
7. **Subscription system foundation**
8. **Email notifications** (trial warning, renewal reminder)
9. **Buy Me a Coffee webhook integration**
10. **Search and filtering**
11. **Terms and Conditions**
12. **Account Requests**
13. **Account Deletion**
14. **REST API** (with security best practices)
15. **Email Preferences**
16. **Test execution** (run test plan, fix any issues)

---

## 18. Email Preferences

### 18.1 Overview
Allow users to control which email notifications they receive. Essential for GDPR compliance and user experience.

### 18.2 Database Changes

**Modify `users` table:**
```sql
ALTER TABLE users ADD COLUMN email_trial_warnings INTEGER DEFAULT 1;     -- Trial expiry warnings
ALTER TABLE users ADD COLUMN email_renewal_reminders INTEGER DEFAULT 1;  -- Subscription renewal reminders
ALTER TABLE users ADD COLUMN email_account_updates INTEGER DEFAULT 1;    -- Password changes, email changes, deletion
ALTER TABLE users ADD COLUMN email_marketing INTEGER DEFAULT 0;          -- Feature announcements (opt-in)
```

### 18.3 User Interface

**Location:** Profile page (`/profile`) → "Email Preferences" section

**Options:**
- **Trial & Subscription Alerts** (default: on)
  - Trial expiry warning (3 days before)
  - Subscription renewal reminder (7 days before)
  - Subscription expired notification

- **Account Security** (default: on, cannot be disabled)
  - Password change confirmation
  - Email change verification
  - Account deletion confirmation
  - Failed login alerts

- **Feature Updates** (default: off)
  - New feature announcements
  - Maintenance notifications

### 18.4 Implementation

**Email send helper update:**
```php
function should_send_email(int $userId, string $emailType): bool
{
    // Security emails always sent (password reset, email verification, etc.)
    $alwaysSend = ['password_reset', 'email_verification', 'account_deleted', 'security_alert'];
    if (in_array($emailType, $alwaysSend)) {
        return true;
    }

    $user = Database::fetch("SELECT * FROM users WHERE id = ?", [$userId]);
    if (!$user) return false;

    $mapping = [
        'trial_warning' => 'email_trial_warnings',
        'renewal_reminder' => 'email_renewal_reminders',
        'subscription_expired' => 'email_trial_warnings',
        'account_update' => 'email_account_updates',
        'marketing' => 'email_marketing',
    ];

    $field = $mapping[$emailType] ?? null;
    return $field ? (bool)$user[$field] : true;
}
```

### 18.5 Unsubscribe Links

All non-security emails include an unsubscribe link:
- Format: `https://example.com/email-preferences?token={unsubscribe_token}`
- Token is user-specific and expires never (or regenerates on security events)
- Links to email preferences page with that category pre-toggled

**Database addition:**
```sql
ALTER TABLE users ADD COLUMN unsubscribe_token TEXT;
```

Generate on account creation:
```php
$unsubscribeToken = bin2hex(random_bytes(32));
```

### 18.6 Email Footer Template

**All emails include:**
```html
<hr>
<p style="font-size: 12px; color: #666;">
  You received this email because you have an account at Razor Library.<br>
  <a href="{unsubscribe_url}">Manage email preferences</a> |
  <a href="{app_url}/profile">View your profile</a>
</p>
```

### 18.7 GDPR Compliance Notes

- Users can opt out of all non-essential emails
- Security-related emails cannot be disabled (required for account safety)
- Unsubscribe mechanism available in every email
- Email preferences stored with user consent timestamps

### 18.8 New Files

- `src/Views/profile/email-preferences.php` - Preferences form (partial)
- Update email templates to include unsubscribe footer

### 18.9 Documentation Updates

**user-guide.md - Add section:**
```markdown
## Email Preferences

Control which emails you receive from Razor Library.

### Managing Preferences

1. Go to **Profile**
2. Scroll to "Email Preferences"
3. Toggle notifications on/off:
   - **Trial & Subscription Alerts** - Reminders about your trial and subscription
   - **Feature Updates** - Announcements about new features (optional)

### Security Emails

Some emails cannot be disabled for your account security:
- Password reset confirmations
- Email change verifications
- Account deletion confirmations
- Suspicious login alerts

### Unsubscribe

Every email includes an "Unsubscribe" link at the bottom that takes you directly to your email preferences.
```

### 18.10 Migration

**New migration: `024_add_email_preferences.php`**
```php
<?php
return [
    "ALTER TABLE users ADD COLUMN email_trial_warnings INTEGER DEFAULT 1",
    "ALTER TABLE users ADD COLUMN email_renewal_reminders INTEGER DEFAULT 1",
    "ALTER TABLE users ADD COLUMN email_account_updates INTEGER DEFAULT 1",
    "ALTER TABLE users ADD COLUMN email_marketing INTEGER DEFAULT 0",
    "ALTER TABLE users ADD COLUMN unsubscribe_token TEXT",
];
```

---

## 19. Error Pages

### 19.1 Overview
Custom error pages that match the application's visual design, following the auth/login page styling pattern.

### 19.2 Error Pages to Create

| Error Code | File | Description |
|------------|------|-------------|
| 400 | `src/Views/errors/400.php` | Bad Request |
| 403 | `src/Views/errors/403.php` | Forbidden |
| 404 | `src/Views/errors/404.php` | Not Found |
| 500 | `src/Views/errors/500.php` | Internal Server Error |
| 503 | `src/Views/errors/503.php` | Service Unavailable |

### 19.3 Styling

Error pages use the same layout pattern as auth pages:
- Centered container with gradient background
- Card-style box with rounded corners and shadow
- Header with brand color and barbershop stripe accent
- Clear error message with helpful next steps

**Template structure:**
```php
<?php
$title = '404 Not Found - Razor Library';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-box-header">
                <h1>404</h1>
                <p class="mb-0">Page Not Found</p>
            </div>
            <div class="auth-box-body" style="text-align: center;">
                <p>The page you're looking for doesn't exist or has been moved.</p>
                <a href="<?= url('/') ?>" class="btn btn-primary">Return Home</a>
            </div>
        </div>
    </div>
</body>
</html>
```

### 19.4 Router Integration

Update `Router.php` to render custom error pages:
```php
public static function notFound(): void
{
    http_response_code(404);
    require BASE_PATH . '/src/Views/errors/404.php';
    exit;
}

public static function forbidden(): void
{
    http_response_code(403);
    require BASE_PATH . '/src/Views/errors/403.php';
    exit;
}

public static function error(int $code = 500): void
{
    http_response_code($code);
    $file = BASE_PATH . "/src/Views/errors/{$code}.php";
    if (file_exists($file)) {
        require $file;
    } else {
        require BASE_PATH . '/src/Views/errors/500.php';
    }
    exit;
}
```

---

## 20. Test Plan

### 20.1 Overview
Comprehensive test plan for v2 features. Tests are designed for manual verification or automated agent execution.

### 20.2 Test Environment Setup

**Prerequisites:**
1. Fresh database (run `php scripts/reset-database.php --force`)
2. Development server running (`php -S localhost:8080 -t public`)
3. Test email service configured (or use MailHog/Mailtrap for local testing)
4. Buy Me a Coffee webhook endpoint accessible (use ngrok for local testing)

**Test Users to Create:**
| Role | Username | Email | Password |
|------|----------|-------|----------|
| Admin | testadmin | admin@test.local | TestPass123! |
| Regular | testuser | user@test.local | TestPass123! |
| Trial | trialuser | trial@test.local | TestPass123! |

### 20.3 Test Categories

#### Category 1: Authentication & Security

**TEST-AUTH-001: Login rate limiting**
```
Steps:
1. Navigate to /login
2. Enter email: user@test.local
3. Enter incorrect password 5 times
4. Attempt 6th login with correct password
Expected: Login blocked with rate limit message
Verify: Wait 15 minutes, login succeeds
```

**TEST-AUTH-002: CSRF protection**
```
Steps:
1. Open browser dev tools, Network tab
2. Submit login form normally
3. Copy the POST request as cURL
4. Modify/remove csrf_token parameter
5. Execute modified cURL command
Expected: 403 Forbidden or CSRF error message
```

**TEST-AUTH-003: Session fixation prevention**
```
Steps:
1. Note session ID from cookie before login
2. Login successfully
3. Note session ID after login
Expected: Session ID has changed
```

**TEST-AUTH-004: Password hashing**
```
Steps:
1. Create new user via admin panel
2. Query database: SELECT password_hash FROM users WHERE email = 'newuser@test.local'
Expected: Password is bcrypt hash (starts with $2y$)
```

**TEST-AUTH-005: XSS prevention in flash messages**
```
Steps:
1. Attempt login with email: <script>alert('xss')</script>@test.local
2. Observe error message display
Expected: Script tags are escaped, no alert popup
```

#### Category 2: Account Requests

**TEST-REQ-001: Account request submission**
```
Steps:
1. Navigate to /request-account
2. Fill form: name, email, reason
3. Submit
Expected: Success message, request appears in admin panel
```

**TEST-REQ-002: Admin approval flow**
```
Steps:
1. Login as admin
2. Navigate to Admin > Account Requests
3. Click "Approve" on pending request
Expected: User created, welcome email sent, request marked approved
```

**TEST-REQ-003: Admin rejection flow**
```
Steps:
1. Login as admin
2. Navigate to Admin > Account Requests
3. Click "Reject" on pending request
4. Enter rejection reason
Expected: Rejection email sent, request marked rejected
```

**TEST-REQ-004: Duplicate request prevention**
```
Steps:
1. Submit account request for email@test.local
2. Submit another request for email@test.local
Expected: Error message about existing request
```

#### Category 3: Subscription System

**TEST-SUB-001: Trial activation on first login**
```
Steps:
1. Create new user via admin or approval
2. Login as new user
Expected: trial_started_at set, trial_expires_at = +7 days
```

**TEST-SUB-002: Trial expiry blocking**
```
Steps:
1. Set user's trial_expires_at to past date in database
2. Attempt to access /razors
Expected: Redirect to subscription/trial-expired page
```

**TEST-SUB-003: Trial warning email (3 days before)**
```
Steps:
1. Set user's trial_expires_at to 3 days from now
2. Run cron job or trigger email check
Expected: Trial warning email sent (if email preferences allow)
```

**TEST-SUB-004: BMaC webhook - subscription activation**
```
Steps:
1. Send POST to /webhooks/buymeacoffee with valid membership payload
2. Include supporter_email matching test user
Expected: User's subscription_status = 'active', subscription_expires_at set
```

**TEST-SUB-005: BMaC webhook - signature verification**
```
Steps:
1. Send POST to /webhooks/buymeacoffee with invalid signature
Expected: 401 Unauthorized response
```

**TEST-SUB-006: Admin subscription override**
```
Steps:
1. Login as admin
2. Navigate to user management
3. Set subscription_status to 'lifetime'
Expected: User has permanent access, no expiry checks
```

#### Category 4: Account Deletion

**TEST-DEL-001: Self-service deletion request**
```
Steps:
1. Login as regular user
2. Navigate to Profile > Delete Account
3. Enter password, confirm deletion
Expected: Account marked deleted, logged out, deletion email sent
```

**TEST-DEL-002: 30-day recovery window**
```
Steps:
1. Delete account (TEST-DEL-001)
2. Navigate to /recover-account
3. Enter email and password
Expected: Account restored, can login again
```

**TEST-DEL-003: Recovery after 30 days**
```
Steps:
1. Set user's deletion_scheduled_at to 31 days ago
2. Attempt recovery
Expected: Recovery failed, account permanently deleted
```

**TEST-DEL-004: Admin can view deleted users**
```
Steps:
1. Login as admin
2. Navigate to Admin > Users
3. Check "Show deleted" filter
Expected: Deleted users visible with deletion status
```

#### Category 5: REST API

**TEST-API-001: API key generation**
```
Steps:
1. Login as regular user
2. Navigate to Profile > API Keys
3. Click "Generate New API Key"
Expected: API key displayed (shown once), key appears in list
```

**TEST-API-002: API authentication**
```
Steps:
1. Generate API key
2. Make request: curl -H "Authorization: Bearer {key}" http://localhost:8080/api/v1/razors
Expected: JSON response with user's razors
```

**TEST-API-003: Invalid API key rejection**
```
Steps:
1. Make request with invalid key: curl -H "Authorization: Bearer invalid123" http://localhost:8080/api/v1/razors
Expected: 401 Unauthorized JSON response
```

**TEST-API-004: API rate limiting**
```
Steps:
1. Make 101 API requests within 1 minute
Expected: 429 Too Many Requests on 101st request
```

**TEST-API-005: CRUD operations**
```
Steps:
1. POST /api/v1/razors - Create razor
2. GET /api/v1/razors/{id} - Read razor
3. PUT /api/v1/razors/{id} - Update razor
4. DELETE /api/v1/razors/{id} - Delete razor
Expected: All operations succeed with correct HTTP status codes
```

**TEST-API-006: Cross-user data isolation**
```
Steps:
1. Create razor as user A via API
2. Attempt to GET/PUT/DELETE that razor as user B
Expected: 404 Not Found (not 403, to prevent enumeration)
```

#### Category 6: New Item Fields

**TEST-FIELD-001: Year manufactured on razors**
```
Steps:
1. Create new razor
2. Enter year_manufactured: 1965
3. Save and view
Expected: Year displayed on detail page
```

**TEST-FIELD-002: Year validation**
```
Steps:
1. Create razor with year_manufactured: 2099
2. Create razor with year_manufactured: 1800
Expected: Validation errors for unreasonable years
```

**TEST-FIELD-003: Country manufactured on razors**
```
Steps:
1. Create razor with country_manufactured: Germany
2. Save and view
Expected: Country displayed on detail page
```

**TEST-FIELD-004: Country manufactured on blades**
```
Steps:
1. Create blade with country_manufactured: Japan
2. Save and view
Expected: Country displayed on detail page
```

**TEST-FIELD-005: Sorting by year**
```
Steps:
1. Create razors with years: 1950, 1970, 1960
2. Sort by "Year (Oldest First)"
3. Sort by "Year (Newest First)"
Expected: Correct sort order in both cases
```

**TEST-FIELD-006: Sorting by country**
```
Steps:
1. Create razors from: Germany, USA, Japan
2. Sort by "Country (A-Z)"
Expected: Germany, Japan, USA order
```

#### Category 7: Search & Filtering

**TEST-SEARCH-001: Basic search**
```
Steps:
1. Create razors: "Gillette Slim", "Merkur 34C", "Gillette Tech"
2. Search for "Gillette"
Expected: Shows "Gillette Slim" and "Gillette Tech"
```

**TEST-SEARCH-002: Search across fields**
```
Steps:
1. Create razor with notes: "Birthday gift from wife"
2. Search for "birthday"
Expected: Razor appears in results
```

**TEST-SEARCH-003: Advanced filter - date range**
```
Steps:
1. Create items on different dates
2. Filter by date range
Expected: Only items within range shown
```

**TEST-SEARCH-004: Advanced filter - country**
```
Steps:
1. Create razors from Germany, USA, Japan
2. Filter by country: Germany
Expected: Only German razors shown
```

**TEST-SEARCH-005: Combined search and filter**
```
Steps:
1. Search "Gillette" AND filter country: USA
Expected: Only US-made Gillette razors
```

#### Category 8: Email Preferences

**TEST-EMAIL-001: Default preferences on new user**
```
Steps:
1. Create new user
2. Check database email preference columns
Expected: trial_warnings=1, renewal_reminders=1, account_updates=1, marketing=0
```

**TEST-EMAIL-002: Update preferences**
```
Steps:
1. Login and go to Profile > Email Preferences
2. Toggle off "Trial & Subscription Alerts"
3. Save
Expected: email_trial_warnings = 0 in database
```

**TEST-EMAIL-003: Security emails cannot be disabled**
```
Steps:
1. Go to Email Preferences
2. Look for "Password changes" option
Expected: Option is not available or disabled (security emails always sent)
```

**TEST-EMAIL-004: Unsubscribe link**
```
Steps:
1. Trigger a non-security email (trial warning)
2. Check email footer for unsubscribe link
3. Click unsubscribe link
Expected: Taken to email preferences page with relevant option pre-selected
```

**TEST-EMAIL-005: Email preference respected**
```
Steps:
1. Disable trial warnings in preferences
2. Set trial to expire in 3 days
3. Run email job
Expected: No trial warning email sent
```

#### Category 9: Email Change Verification

**TEST-EMAILCHANGE-001: Initiate email change**
```
Steps:
1. Login and go to Profile
2. Change email to newemail@test.local
3. Save
Expected: Verification email sent to new address, pending_email stored
```

**TEST-EMAILCHANGE-002: Verify new email**
```
Steps:
1. Initiate email change
2. Click verification link in email
Expected: Email updated, pending_email cleared, confirmation shown
```

**TEST-EMAILCHANGE-003: Old email notification**
```
Steps:
1. Initiate email change
Expected: Notification sent to OLD email about pending change
```

**TEST-EMAILCHANGE-004: Verification token expiry**
```
Steps:
1. Initiate email change
2. Wait 24 hours (or set token expiry in past)
3. Click verification link
Expected: Error - token expired, change cancelled
```

#### Category 10: Activity Logging

**TEST-LOG-001: Login logged**
```
Steps:
1. Login as any user
2. Check admin > Activity Log as admin
Expected: Login event recorded with timestamp, IP, user
```

**TEST-LOG-002: Critical actions logged**
```
Steps:
1. Delete a razor
2. Check activity log
Expected: Delete event recorded
```

**TEST-LOG-003: Admin-only access**
```
Steps:
1. Login as regular user
2. Navigate to /admin/activity-log
Expected: 403 Forbidden
```

#### Category 11: Error Pages

**TEST-ERR-001: 404 page styling**
```
Steps:
1. Navigate to /nonexistent-page-12345
Expected: Custom 404 page with auth-style layout, brand colors, helpful message
```

**TEST-ERR-002: 403 page styling**
```
Steps:
1. Login as regular user
2. Navigate to /admin
Expected: Custom 403 page with same styling
```

**TEST-ERR-003: Error pages work without login**
```
Steps:
1. Logout
2. Navigate to /nonexistent-page
Expected: 404 page renders correctly (no PHP errors)
```

### 20.4 Regression Tests

After implementing v2, verify existing functionality still works:

**TEST-REG-001: Image upload and gallery**
```
Steps:
1. Create new razor with 3 images
2. Set 2nd image as tile
3. Delete 3rd image
Expected: All operations work as before
```

**TEST-REG-002: Blade usage tracking**
```
Steps:
1. Go to razor detail page
2. Add blade usage record
3. Increment/decrement count
Expected: Usage tracking works
```

**TEST-REG-003: Share link**
```
Steps:
1. Get share link from profile
2. Open in incognito browser
Expected: Collection visible without login
```

**TEST-REG-004: CSV import**
```
Steps:
1. Download razor template
2. Add test data
3. Import
Expected: Items created successfully
```

**TEST-REG-005: Export collection**
```
Steps:
1. Click Export in profile
2. Open ZIP file
Expected: Contains markdown files and images
```

**TEST-REG-006: Admin backup/restore**
```
Steps:
1. Create backup
2. Make changes
3. Restore backup
Expected: Changes reverted
```

### 20.5 Agent Execution Format

For automated testing agents, each test can be expressed as:

```json
{
  "test_id": "TEST-AUTH-001",
  "category": "Authentication",
  "name": "Login rate limiting",
  "preconditions": ["user_exists:user@test.local"],
  "steps": [
    {"action": "navigate", "url": "/login"},
    {"action": "fill", "field": "email", "value": "user@test.local"},
    {"action": "fill", "field": "password", "value": "wrongpass"},
    {"action": "click", "element": "button[type=submit]"},
    {"action": "repeat", "times": 5},
    {"action": "fill", "field": "password", "value": "TestPass123!"},
    {"action": "click", "element": "button[type=submit]"}
  ],
  "expected": {
    "page_contains": "rate limit",
    "not_logged_in": true
  }
}
```

### 20.6 Test Execution Order

Run tests in this order to ensure dependencies are met:

1. **Setup Phase**
   - Reset database
   - Create test users
   - Configure test environment

2. **Authentication & Security** (Category 1)
   - Must pass before other tests

3. **Account Requests** (Category 2)
   - Tests new user creation flow

4. **Core Features** (Categories 3-10)
   - Can run in parallel

5. **Error Pages** (Category 11)
   - Visual verification

6. **Regression Tests** (Category 20.4)
   - Verify nothing broke

### 20.7 Continuous Integration

For CI/CD integration, create test scripts:

```bash
# scripts/run-tests.sh
#!/bin/bash
set -e

echo "Setting up test environment..."
php scripts/reset-database.php --force
php scripts/create-user.php "testadmin" "admin@test.local" "TestPass123!" --admin
php scripts/create-user.php "testuser" "user@test.local" "TestPass123!"

echo "Running tests..."
# Add test runner commands here (PHPUnit, Playwright, etc.)

echo "Tests complete!"
```

### 20.8 Coverage Requirements

Before v2 release, ensure:
- [ ] All TEST-* cases pass
- [ ] All regression tests pass
- [ ] No PHP errors in logs
- [ ] No JavaScript console errors
- [ ] Mobile responsive check on key pages
- [ ] Cross-browser check (Chrome, Firefox, Safari)
