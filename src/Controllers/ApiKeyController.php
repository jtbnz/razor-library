<?php
/**
 * API Key Controller
 * Handles API key generation and management
 */

class ApiKeyController
{
    /**
     * List user's API keys
     */
    public function index(): string
    {
        $userId = $_SESSION['user_id'];

        $keys = Database::fetchAll(
            "SELECT id, name, key_prefix, last_used_at, created_at, revoked_at
             FROM api_keys
             WHERE user_id = ?
             ORDER BY created_at DESC",
            [$userId]
        );

        return view('profile/api-keys', [
            'keys' => $keys,
        ]);
    }

    /**
     * Create a new API key
     */
    public function create(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/profile/api-keys');
        }

        $userId = $_SESSION['user_id'];
        $name = trim($_POST['name'] ?? '');

        if (empty($name)) {
            flash('error', 'Please provide a name for the API key.');
            redirect('/profile/api-keys');
        }

        if (strlen($name) > 50) {
            flash('error', 'Key name must be 50 characters or less.');
            redirect('/profile/api-keys');
        }

        // Generate a secure API key
        $apiKey = 'rl_' . bin2hex(random_bytes(32)); // 64 hex chars + prefix
        $keyHash = hash('sha256', $apiKey);
        $keyPrefix = substr($apiKey, 0, 8); // First 8 chars for identification

        // Check limit (max 5 active keys per user)
        $activeCount = Database::fetch(
            "SELECT COUNT(*) as count FROM api_keys WHERE user_id = ? AND revoked_at IS NULL",
            [$userId]
        )['count'];

        if ($activeCount >= 5) {
            flash('error', 'Maximum of 5 API keys allowed. Please revoke an existing key first.');
            redirect('/profile/api-keys');
        }

        Database::query(
            "INSERT INTO api_keys (user_id, name, key_hash, key_prefix) VALUES (?, ?, ?, ?)",
            [$userId, $name, $keyHash, $keyPrefix]
        );

        // Log key creation
        if (class_exists('ActivityLogger')) {
            ActivityLogger::log('api_key_created', 'api_key', Database::lastInsertId(), ['name' => $name]);
        }

        // Store key in session for one-time display
        $_SESSION['new_api_key'] = $apiKey;

        flash('success', 'API key created. Copy it now - you won\'t be able to see it again!');
        redirect('/profile/api-keys');
    }

    /**
     * Revoke an API key
     */
    public function revoke(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/profile/api-keys');
        }

        $userId = $_SESSION['user_id'];

        $key = Database::fetch(
            "SELECT * FROM api_keys WHERE id = ? AND user_id = ? AND revoked_at IS NULL",
            [$id, $userId]
        );

        if (!$key) {
            flash('error', 'API key not found.');
            redirect('/profile/api-keys');
        }

        Database::query(
            "UPDATE api_keys SET revoked_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$id]
        );

        // Log key revocation
        if (class_exists('ActivityLogger')) {
            ActivityLogger::log('api_key_revoked', 'api_key', $id, ['name' => $key['name']]);
        }

        flash('success', 'API key revoked.');
        redirect('/profile/api-keys');
    }
}
