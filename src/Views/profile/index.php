<?php ob_start(); ?>

<div class="page-header">
    <h1>My Profile</h1>
</div>

<?php if (!empty($user['deletion_scheduled_at'])): ?>
<div class="alert alert-danger mb-4">
    <strong>Account Scheduled for Deletion</strong>
    <p class="mb-2">Your account is scheduled for permanent deletion on <?= format_date($user['deletion_scheduled_at']) ?>.</p>
    <form action="<?= url('/profile/cancel-deletion') ?>" method="POST" style="display: inline;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline">Cancel Deletion</button>
    </form>
</div>
<?php endif; ?>

<div class="grid-2">
    <div>
        <!-- Profile Form -->
        <div class="card">
            <div class="card-header">
                <h3>Account Settings</h3>
            </div>
            <div class="card-body">
                <form action="<?= url('/profile') ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" id="username" name="username" class="form-input" value="<?= e($user['username']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-input" value="<?= e($user['email'] ?? '') ?>" placeholder="your@email.com">
                        <p class="form-hint">Used for password reset and notifications.</p>
                        <?php if (!empty($user['pending_email'])): ?>
                        <div class="alert alert-info mt-2">
                            <strong>Pending email change:</strong> <?= e($user['pending_email']) ?>
                            <br><small>Please check your new email address for a verification link.</small>
                            <form action="<?= url('/profile/cancel-email-change') ?>" method="POST" style="display: inline;">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline mt-2">Cancel Change</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>

                    <hr class="my-4">

                    <h4 class="mb-3">Change Password</h4>
                    <p class="text-muted mb-3">Leave blank to keep current password.</p>

                    <div class="form-group">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-input" minlength="8">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div>
        <!-- Collection Stats -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>Collection Stats</h3>
            </div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-value"><?= $stats['razors'] ?></span>
                        <span class="stat-label">Razors</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?= $stats['blades'] ?></span>
                        <span class="stat-label">Blades</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?= $stats['brushes'] ?></span>
                        <span class="stat-label">Brushes</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?= $stats['other'] ?></span>
                        <span class="stat-label">Other</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Share Link -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>Share Collection</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Share your collection with this private link. Anyone with the link can view (but not edit) your collection.</p>

                <div class="form-group">
                    <label class="form-label">Your Share Link</label>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-input" value="<?= e(base_url('/share/' . $user['share_token'])) ?>" readonly id="share-link">
                        <button type="button" class="btn btn-outline" onclick="copyShareLink()">Copy</button>
                    </div>
                </div>

                <form action="<?= url('/profile/regenerate-share-token') ?>" method="POST" class="mt-3">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline" data-confirm="Are you sure? Your current share link will stop working.">Regenerate Link</button>
                </form>
            </div>
        </div>

        <!-- Export -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>Export Collection</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Download your entire collection as a ZIP file containing markdown files for each item and all your images.</p>

                <a href="<?= url('/profile/export') ?>" class="btn btn-primary">Download Collection</a>
            </div>
        </div>

        <!-- API Access -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>API Access</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Create API keys to access your collection programmatically via our REST API.</p>
                <a href="<?= url('/profile/api-keys') ?>" class="btn btn-outline">Manage API Keys</a>
            </div>
        </div>

        <!-- Email Preferences -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>Email Preferences</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Control which emails you receive from Razor Library.</p>
                <a href="<?= url('/profile/email-preferences') ?>" class="btn btn-outline">Manage Email Preferences</a>
            </div>
        </div>

        <!-- Import CSV -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>Import from CSV</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Bulk import items from a CSV file. Duplicates will be skipped automatically.</p>

                <form action="<?= url('/profile/import-csv') ?>" method="POST" enctype="multipart/form-data" id="import-form">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="import_type" class="form-label">Import Type</label>
                        <select id="import_type" name="import_type" class="form-select" required>
                            <option value="razors">Razors</option>
                            <option value="blades">Blades</option>
                            <option value="brushes">Brushes</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="csv_file" class="form-label">CSV File</label>
                        <input type="file" id="csv_file" name="csv_file" class="form-input" accept=".csv,text/csv" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>

                <hr class="my-4">

                <h4 class="mb-3">CSV Format Guide</h4>
                <p class="text-muted mb-2">Download a template or follow these formatting guidelines:</p>

                <div class="mb-3">
                    <strong>Download Templates:</strong>
                    <div class="d-flex gap-2 mt-2">
                        <a href="<?= url('/profile/csv-template?type=razors') ?>" class="btn btn-sm btn-outline">Razors Template</a>
                        <a href="<?= url('/profile/csv-template?type=blades') ?>" class="btn btn-sm btn-outline">Blades Template</a>
                        <a href="<?= url('/profile/csv-template?type=brushes') ?>" class="btn btn-sm btn-outline">Brushes Template</a>
                    </div>
                </div>

                <details class="mt-3">
                    <summary class="cursor-pointer"><strong>Column Reference</strong></summary>
                    <div class="mt-2 text-small">
                        <p><strong>Razors:</strong> Brand, Name, UseCount, Notes</p>
                        <p><strong>Blades:</strong> Brand, Name, Notes</p>
                        <p><strong>Brushes:</strong> Brand, Name, BristleType, KnotSize, Loft, HandleMaterial, UseCount, Notes</p>

                        <p class="mt-2 text-muted">
                            <strong>Tips:</strong><br>
                            - First row must be headers<br>
                            - Name is required for all types<br>
                            - Brand is combined with Name (e.g., "Gillette" + "Slim" = "Gillette Slim")<br>
                            - Column names are case-insensitive<br>
                            - Duplicates (same name) are automatically skipped<br>
                            - You can add images and more details via the web interface after import
                        </p>
                    </div>
                </details>
            </div>
        </div>

        <!-- Delete Account -->
        <div class="card border-danger">
            <div class="card-header">
                <h3>Delete Account</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Permanently delete your account and all associated data. This action has a 30-day recovery window.</p>
                <a href="<?= url('/profile/delete') ?>" class="btn btn-outline">Delete My Account</a>
            </div>
        </div>
    </div>
</div>

<script>
function copyShareLink() {
    const input = document.getElementById('share-link');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value);

    // Show feedback
    const btn = event.target;
    const originalText = btn.textContent;
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = originalText, 2000);
}

// Debug CSV import form
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('import-form');
    if (form) {
        console.log('[CSV Import] Form found');
        form.addEventListener('submit', function(e) {
            const fileInput = document.getElementById('csv_file');
            const typeSelect = document.getElementById('import_type');
            console.log('[CSV Import] Form submitting...');
            console.log('[CSV Import] File selected:', fileInput.files.length > 0 ? fileInput.files[0].name : 'none');
            console.log('[CSV Import] Import type:', typeSelect.value);
            console.log('[CSV Import] Form action:', form.action);
            // Let the form submit normally
        });
    } else {
        console.log('[CSV Import] Form NOT found');
    }
});
</script>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
