<?php
/**
 * Subscription Checker
 * Handles subscription status checking and enforcement
 */

class SubscriptionChecker
{
    /**
     * Check if subscription enforcement is enabled
     */
    public static function isEnabled(): bool
    {
        $config = self::getConfig();
        return (bool) ($config['subscription_check_enabled'] ?? false);
    }

    /**
     * Get subscription config
     */
    public static function getConfig(): array
    {
        static $config = null;
        if ($config === null) {
            $config = Database::fetch("SELECT * FROM subscription_config WHERE id = 1") ?: [
                'trial_days' => 7,
                'subscription_check_enabled' => 0,
                'expired_message' => 'Your trial has expired.',
            ];
        }
        return $config;
    }

    /**
     * Check if user has valid subscription
     * Returns true if user can access the app
     */
    public static function hasValidSubscription(?int $userId = null): bool
    {
        if ($userId === null) {
            $userId = $_SESSION['user_id'] ?? null;
        }

        if (!$userId) {
            return false;
        }

        // Admins always have access
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
            return true;
        }

        // Check if subscription checking is enabled
        if (!self::isEnabled()) {
            return true;
        }

        $user = Database::fetch(
            "SELECT subscription_status, subscription_expires_at, is_admin FROM users WHERE id = ? AND deleted_at IS NULL",
            [$userId]
        );

        if (!$user) {
            return false;
        }

        // Admins bypass subscription checks
        if ($user['is_admin']) {
            return true;
        }

        // Check subscription status
        $status = $user['subscription_status'] ?? 'trial';

        switch ($status) {
            case 'active':
            case 'lifetime':
                return true;

            case 'trial':
                // Check if trial has expired
                if ($user['subscription_expires_at']) {
                    return strtotime($user['subscription_expires_at']) > time();
                }
                return true;

            case 'expired':
            case 'cancelled':
                return false;

            default:
                return false;
        }
    }

    /**
     * Get user's subscription status details
     */
    public static function getSubscriptionStatus(?int $userId = null): array
    {
        if ($userId === null) {
            $userId = $_SESSION['user_id'] ?? null;
        }

        if (!$userId) {
            return ['status' => 'unknown', 'valid' => false];
        }

        $user = Database::fetch(
            "SELECT subscription_status, subscription_started_at, subscription_expires_at, is_admin FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            return ['status' => 'unknown', 'valid' => false];
        }

        $status = $user['subscription_status'] ?? 'trial';
        $valid = self::hasValidSubscription($userId);
        $daysRemaining = null;

        if ($user['subscription_expires_at']) {
            $expiresAt = strtotime($user['subscription_expires_at']);
            $daysRemaining = max(0, ceil(($expiresAt - time()) / 86400));
        }

        return [
            'status' => $status,
            'valid' => $valid,
            'started_at' => $user['subscription_started_at'],
            'expires_at' => $user['subscription_expires_at'],
            'days_remaining' => $daysRemaining,
            'is_admin' => (bool) $user['is_admin'],
        ];
    }

    /**
     * Initialize trial for a new user
     */
    public static function initializeTrial(int $userId): void
    {
        $config = self::getConfig();
        $trialDays = $config['trial_days'] ?? 7;

        $now = date('Y-m-d H:i:s');
        $expires = date('Y-m-d H:i:s', time() + ($trialDays * 86400));

        Database::query(
            "UPDATE users SET subscription_status = 'trial', subscription_started_at = ?, subscription_expires_at = ? WHERE id = ?",
            [$now, $expires, $userId]
        );

        // Log event
        self::logEvent($userId, 'trial_started', ['expires_at' => $expires]);
    }

    /**
     * Activate subscription for a user
     */
    public static function activateSubscription(int $userId, ?string $bmacMemberId = null, ?int $durationDays = 30): void
    {
        $now = date('Y-m-d H:i:s');
        $expires = date('Y-m-d H:i:s', time() + ($durationDays * 86400));

        $updateFields = [
            "subscription_status = 'active'",
            "subscription_started_at = ?",
            "subscription_expires_at = ?",
        ];
        $params = [$now, $expires];

        if ($bmacMemberId) {
            $updateFields[] = "bmac_member_id = ?";
            $params[] = $bmacMemberId;
        }

        $params[] = $userId;

        Database::query(
            "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?",
            $params
        );

        // Log event
        self::logEvent($userId, 'subscription_activated', [
            'bmac_member_id' => $bmacMemberId,
            'expires_at' => $expires,
        ]);
    }

    /**
     * Set lifetime subscription for a user
     */
    public static function setLifetime(int $userId): void
    {
        Database::query(
            "UPDATE users SET subscription_status = 'lifetime', subscription_expires_at = NULL WHERE id = ?",
            [$userId]
        );

        self::logEvent($userId, 'subscription_lifetime');
    }

    /**
     * Expire a user's subscription
     */
    public static function expireSubscription(int $userId): void
    {
        Database::query(
            "UPDATE users SET subscription_status = 'expired' WHERE id = ?",
            [$userId]
        );

        self::logEvent($userId, 'subscription_expired');
    }

    /**
     * Cancel a user's subscription
     */
    public static function cancelSubscription(int $userId): void
    {
        Database::query(
            "UPDATE users SET subscription_status = 'cancelled' WHERE id = ?",
            [$userId]
        );

        self::logEvent($userId, 'subscription_cancelled');
    }

    /**
     * Log subscription event
     */
    public static function logEvent(int $userId, string $eventType, ?array $details = null): void
    {
        Database::query(
            "INSERT INTO subscription_events (user_id, event_type, details) VALUES (?, ?, ?)",
            [$userId, $eventType, $details ? json_encode($details) : null]
        );
    }

    /**
     * Update subscription config
     */
    public static function updateConfig(array $settings): void
    {
        $allowedFields = ['bmac_access_token', 'bmac_webhook_secret', 'trial_days', 'subscription_check_enabled', 'expired_message'];
        $updates = [];
        $params = [];

        foreach ($settings as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        if (empty($updates)) {
            return;
        }

        $updates[] = "updated_at = CURRENT_TIMESTAMP";

        Database::query(
            "UPDATE subscription_config SET " . implode(', ', $updates) . " WHERE id = 1",
            $params
        );

        // Clear cached config
        static $config = null;
    }
}
