<?php
/**
 * Webhook Controller
 * Handles incoming webhooks from external services
 */

class WebhookController
{
    /**
     * Handle Buy Me a Coffee webhook
     */
    public function bmac(): void
    {
        // Get raw request body
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_BMAC_SIGNATURE'] ?? '';

        // Log the incoming webhook
        $this->logWebhook('bmac', $payload, $signature);

        // Verify signature
        $config = SubscriptionChecker::getConfig();
        $secret = $config['bmac_webhook_secret'] ?? '';

        if (!empty($secret) && !$this->verifyBmacSignature($payload, $signature, $secret)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature']);
            exit;
        }

        // Parse payload
        $data = json_decode($payload, true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON payload']);
            exit;
        }

        // Get event type and supporter email
        $eventType = $data['type'] ?? '';
        $supporterEmail = $data['data']['supporter_email'] ?? $data['data']['payer_email'] ?? '';
        $memberId = $data['data']['supporter_id'] ?? $data['data']['id'] ?? null;

        if (empty($supporterEmail)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing supporter email']);
            exit;
        }

        // Find user by email
        $user = Database::fetch(
            "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL",
            [$supporterEmail]
        );

        // Handle event
        switch ($eventType) {
            case 'membership.started':
            case 'membership.renewed':
                $this->handleMembershipActivation($user, $supporterEmail, $memberId, $data);
                break;

            case 'membership.cancelled':
                $this->handleMembershipCancelled($user, $supporterEmail, $data);
                break;

            case 'membership.expired':
                $this->handleMembershipExpired($user, $supporterEmail, $data);
                break;

            default:
                // Log unknown event type
                ActivityLogger::log('bmac_webhook_unknown', null, null, [
                    'event_type' => $eventType,
                    'email' => $supporterEmail,
                ]);
        }

        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    }

    /**
     * Verify Buy Me a Coffee webhook signature
     */
    private function verifyBmacSignature(string $payload, string $signature, string $secret): bool
    {
        if (empty($signature)) {
            return false;
        }

        // BMaC uses HMAC-SHA256
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle membership activation/renewal
     */
    private function handleMembershipActivation(?array $user, string $email, ?string $memberId, array $data): void
    {
        // Calculate subscription duration (default 30 days for monthly)
        $durationDays = 30;
        if (isset($data['data']['membership_level'])) {
            $level = strtolower($data['data']['membership_level']);
            if (str_contains($level, 'year')) {
                $durationDays = 365;
            }
        }

        if ($user) {
            // Activate subscription
            SubscriptionChecker::activateSubscription($user['id'], $memberId, $durationDays);

            // Log event with transaction details
            SubscriptionChecker::logEvent($user['id'], 'bmac_membership_activated', [
                'member_id' => $memberId,
                'duration_days' => $durationDays,
                'transaction_id' => $data['data']['id'] ?? null,
            ]);

            ActivityLogger::log('subscription_activated', 'user', $user['id'], [
                'source' => 'bmac_webhook',
                'member_id' => $memberId,
            ], $user['id']);
        } else {
            // User not found - log for admin review
            $this->logUnmatchedWebhook($email, 'membership.started', $data);
        }
    }

    /**
     * Handle membership cancellation
     */
    private function handleMembershipCancelled(?array $user, string $email, array $data): void
    {
        if ($user) {
            SubscriptionChecker::cancelSubscription($user['id']);

            SubscriptionChecker::logEvent($user['id'], 'bmac_membership_cancelled', [
                'transaction_id' => $data['data']['id'] ?? null,
            ]);

            ActivityLogger::log('subscription_cancelled', 'user', $user['id'], [
                'source' => 'bmac_webhook',
            ], $user['id']);
        } else {
            $this->logUnmatchedWebhook($email, 'membership.cancelled', $data);
        }
    }

    /**
     * Handle membership expiry
     */
    private function handleMembershipExpired(?array $user, string $email, array $data): void
    {
        if ($user) {
            SubscriptionChecker::expireSubscription($user['id']);

            SubscriptionChecker::logEvent($user['id'], 'bmac_membership_expired', [
                'transaction_id' => $data['data']['id'] ?? null,
            ]);

            // Send notification email
            if ($user['email']) {
                Mailer::sendSubscriptionExpired($user['email'], $user['username']);
            }

            ActivityLogger::log('subscription_expired', 'user', $user['id'], [
                'source' => 'bmac_webhook',
            ], $user['id']);
        } else {
            $this->logUnmatchedWebhook($email, 'membership.expired', $data);
        }
    }

    /**
     * Log unmatched webhook for admin review
     */
    private function logUnmatchedWebhook(string $email, string $eventType, array $data): void
    {
        // Log to database
        Database::query(
            "INSERT INTO subscription_events (user_id, event_type, details) VALUES (NULL, ?, ?)",
            ['bmac_unmatched_' . $eventType, json_encode([
                'email' => $email,
                'event_type' => $eventType,
                'data' => $data,
            ])]
        );

        ActivityLogger::log('bmac_webhook_unmatched', null, null, [
            'email' => $email,
            'event_type' => $eventType,
        ]);

        // Notify admin via email
        $this->notifyAdminOfUnmatchedWebhook($email, $eventType, $data);
    }

    /**
     * Notify admin of unmatched webhook
     */
    private function notifyAdminOfUnmatchedWebhook(string $email, string $eventType, array $data): void
    {
        // Get admin emails
        $admins = Database::fetchAll(
            "SELECT email FROM users WHERE is_admin = 1 AND email IS NOT NULL AND deleted_at IS NULL"
        );

        if (empty($admins)) {
            return;
        }

        $appName = config('APP_NAME');
        $subject = "[{$appName}] Unmatched BMaC webhook - action required";

        $body = "A Buy Me a Coffee webhook was received but could not be matched to a user.\n\n";
        $body .= "Event type: {$eventType}\n";
        $body .= "Supporter email: {$email}\n\n";
        $body .= "Please check if this email matches any pending account requests or manually link the payment to a user.\n\n";
        $body .= "Admin URL: " . config('APP_URL') . "/admin\n\n";
        $body .= "Raw data:\n" . json_encode($data, JSON_PRETTY_PRINT);

        foreach ($admins as $admin) {
            Mailer::send($admin['email'], $subject, $body);
        }
    }

    /**
     * Log incoming webhook to file for debugging
     */
    private function logWebhook(string $source, string $payload, string $signature): void
    {
        $logDir = BASE_PATH . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/webhooks.log';
        $logEntry = date('[Y-m-d H:i:s] ') . strtoupper($source) . " webhook received\n";
        $logEntry .= "Signature: " . ($signature ?: 'none') . "\n";
        $logEntry .= "Payload: " . $payload . "\n";
        $logEntry .= str_repeat('-', 80) . "\n";

        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
