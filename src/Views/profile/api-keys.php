<?php ob_start(); ?>

<div class="page-header">
    <h1>API Keys</h1>
    <div class="page-header-actions">
        <a href="<?= url('/profile') ?>" class="btn btn-outline">Back to Profile</a>
    </div>
</div>

<?php if (isset($_SESSION['new_api_key'])): ?>
<div class="alert alert-success mb-4">
    <strong>New API Key Created!</strong>
    <p class="mb-2">Copy this key now. You won't be able to see it again:</p>
    <div class="d-flex gap-2 align-items-center">
        <code id="new-key" style="background: #f8f9fa; padding: 0.5rem 1rem; border-radius: 4px; word-break: break-all;"><?= e($_SESSION['new_api_key']) ?></code>
        <button type="button" class="btn btn-sm btn-outline" onclick="copyKey()">Copy</button>
    </div>
</div>
<?php unset($_SESSION['new_api_key']); ?>
<?php endif; ?>

<div class="grid-2">
    <div>
        <div class="card mb-4">
            <div class="card-header">
                <h3>Create New Key</h3>
            </div>
            <div class="card-body">
                <form action="<?= url('/profile/api-keys') ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="name" class="form-label">Key Name</label>
                        <input type="text" id="name" name="name" class="form-input"
                               placeholder="e.g., My App, Automation Script" required maxlength="50">
                        <p class="form-hint">A name to help you identify this key.</p>
                    </div>

                    <button type="submit" class="btn btn-primary">Create Key</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>API Documentation</h3>
            </div>
            <div class="card-body">
                <h4 class="h5">Authentication</h4>
                <p>Include your API key in the <code>Authorization</code> header:</p>
                <pre style="background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto;">Authorization: Bearer rl_your_api_key_here</pre>

                <h4 class="h5 mt-4">Endpoints</h4>
                <ul>
                    <li><code>GET /api/razors</code> - List all razors</li>
                    <li><code>GET /api/razors/{id}</code> - Get a razor</li>
                    <li><code>POST /api/razors</code> - Create a razor</li>
                    <li><code>PUT /api/razors/{id}</code> - Update a razor</li>
                    <li><code>DELETE /api/razors/{id}</code> - Delete a razor</li>
                </ul>
                <p class="text-muted">Same pattern for <code>/api/blades</code>, <code>/api/brushes</code>, <code>/api/other</code></p>

                <h4 class="h5 mt-4">Rate Limits</h4>
                <p>100 requests per minute per API key.</p>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-header">
                <h3>Your API Keys</h3>
            </div>
            <div class="card-body">
                <?php if (empty($keys)): ?>
                <p class="text-muted">You haven't created any API keys yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Key</th>
                                <th>Last Used</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($keys as $key): ?>
                            <tr class="<?= $key['revoked_at'] ? 'text-muted' : '' ?>">
                                <td><?= e($key['name']) ?></td>
                                <td><code><?= e($key['key_prefix']) ?>...</code></td>
                                <td>
                                    <?php if ($key['last_used_at']): ?>
                                    <?= format_date($key['last_used_at']) ?>
                                    <?php else: ?>
                                    <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($key['revoked_at']): ?>
                                    <span class="badge badge-danger">Revoked</span>
                                    <?php else: ?>
                                    <span class="badge badge-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$key['revoked_at']): ?>
                                    <form action="<?= url('/profile/api-keys/' . $key['id'] . '/revoke') ?>" method="POST" style="display: inline;">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-danger" data-confirm="Revoke this API key? Any applications using it will stop working.">Revoke</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function copyKey() {
    const keyEl = document.getElementById('new-key');
    navigator.clipboard.writeText(keyEl.textContent).then(function() {
        const btn = event.target;
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = 'Copy', 2000);
    });
}
</script>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
