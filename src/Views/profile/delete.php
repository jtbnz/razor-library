<?php ob_start(); ?>

<div class="page-header">
    <h1>Delete Account</h1>
    <div class="page-header-actions">
        <a href="<?= url('/profile') ?>" class="btn btn-outline">Back to Profile</a>
    </div>
</div>

<div class="card border-danger mb-4">
    <div class="card-header bg-danger text-white">
        <h2 class="h4 mb-0">Warning: This Action Cannot Be Undone</h2>
    </div>
    <div class="card-body">
        <?php if (has_flash('error')): ?>
        <div class="alert alert-error"><?= get_flash('error') ?></div>
        <?php endif; ?>

        <p>Deleting your account will permanently remove:</p>

        <ul class="mb-4">
            <li><strong><?= $stats['razors'] ?></strong> razor(s)</li>
            <li><strong><?= $stats['blades'] ?></strong> blade(s)</li>
            <li><strong><?= $stats['brushes'] ?></strong> brush(es)</li>
            <li><strong><?= $stats['other'] ?></strong> other item(s)</li>
            <li>All associated images and URLs</li>
            <li>All blade usage records</li>
            <li>Your profile and login credentials</li>
        </ul>

        <div class="alert alert-warning mb-4">
            <strong>Before you delete:</strong> We recommend downloading a backup of your collection.
            <br><br>
            <a href="<?= url('/profile/export') ?>" class="btn btn-outline btn-sm">Download My Collection</a>
        </div>

        <h3 class="h5 mb-3">Recovery Period</h3>
        <p>After requesting deletion, you have <strong>30 days</strong> to change your mind. During this time, you can log back in to cancel the deletion. After 30 days, your data will be permanently and irreversibly deleted.</p>

        <hr class="my-4">

        <h3 class="h5 mb-3">Confirm Deletion</h3>
        <p>To proceed, type <strong>DELETE MY ACCOUNT</strong> below and click the button.</p>

        <form action="<?= url('/profile/delete') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="confirm_text" class="form-label">Type "DELETE MY ACCOUNT" to confirm:</label>
                <input type="text" id="confirm_text" name="confirm_text" class="form-input"
                       autocomplete="off" placeholder="DELETE MY ACCOUNT" required>
            </div>

            <button type="submit" class="btn btn-danger" data-confirm="Are you absolutely sure? This will schedule your account for permanent deletion.">
                Delete My Account
            </button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
