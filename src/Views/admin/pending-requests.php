<?php ob_start(); ?>

<div class="page-header">
    <h1>Account Requests</h1>
    <div class="page-header-actions">
        <a href="<?= url('/admin') ?>" class="btn btn-outline">Back to Admin</a>
    </div>
</div>

<?php if (!empty($requests)): ?>
<div class="card">
    <div class="card-header">
        <h2 class="h4 mb-0">Pending Requests</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Reason</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><strong><?= e($request['username']) ?></strong></td>
                        <td><?= e($request['email']) ?></td>
                        <td>
                            <?php if ($request['reason']): ?>
                            <span class="text-muted" title="<?= e($request['reason']) ?>">
                                <?= e(substr($request['reason'], 0, 50)) ?><?= strlen($request['reason']) > 50 ? '...' : '' ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= format_date($request['created_at']) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <form action="<?= url('/admin/requests/' . $request['id'] . '/approve') ?>" method="POST" style="display: inline;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-primary" data-confirm="Approve this account request? A welcome email with login credentials will be sent.">Approve</button>
                                </form>
                                <button type="button" class="btn btn-sm btn-danger" onclick="showRejectModal(<?= $request['id'] ?>, '<?= e($request['username']) ?>')">Reject</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 48px; height: 48px; margin: 0 auto 1rem;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3>No Pending Requests</h3>
            <p class="text-muted">There are no account requests awaiting review.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Rejection Modal -->
<div id="rejectModal" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="hideRejectModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reject Request</h3>
            <button type="button" class="modal-close" onclick="hideRejectModal()">&times;</button>
        </div>
        <form id="rejectForm" method="POST">
            <?= csrf_field() ?>
            <div class="modal-body">
                <p>Reject the account request from <strong id="rejectUsername"></strong>?</p>
                <div class="form-group">
                    <label for="rejection_reason" class="form-label">Reason (optional)</label>
                    <textarea id="rejection_reason" name="rejection_reason" class="form-input" rows="3" placeholder="Provide a reason for rejection (will be sent to the user)"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject Request</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}
.modal-content {
    position: relative;
    background: white;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}
.modal-header h3 {
    margin: 0;
}
.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #718096;
}
.modal-close:hover {
    color: #2d3748;
}
.modal-body {
    padding: 1.5rem;
}
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
}
</style>

<script>
function showRejectModal(id, username) {
    document.getElementById('rejectModal').style.display = 'flex';
    document.getElementById('rejectUsername').textContent = username;
    document.getElementById('rejectForm').action = '<?= url('/admin/requests/') ?>' + id + '/reject';
}

function hideRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    document.getElementById('rejection_reason').value = '';
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideRejectModal();
    }
});
</script>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
