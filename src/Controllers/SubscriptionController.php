<?php
/**
 * Subscription Controller
 * Handles subscription management and expired page
 */

class SubscriptionController
{
    /**
     * Show subscription expired page
     */
    public function expired(): string
    {
        // If user is admin, redirect to dashboard
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
            redirect('/dashboard');
        }

        // If subscription is valid, redirect to dashboard
        if (SubscriptionChecker::hasValidSubscription()) {
            redirect('/dashboard');
        }

        $config = SubscriptionChecker::getConfig();
        $status = SubscriptionChecker::getSubscriptionStatus();

        return view('subscription/expired', [
            'message' => $config['expired_message'] ?? 'Your subscription has expired.',
            'status' => $status,
            'bmac_url' => $config['bmac_membership_url'] ?? 'https://buymeacoffee.com/',
        ]);
    }

    /**
     * Admin subscription settings page
     */
    public function settings(): string
    {
        if (!is_admin()) {
            http_response_code(403);
            return view('errors/403');
        }

        // Ensure tables exist
        SubscriptionChecker::ensureTables();

        $config = SubscriptionChecker::getConfig();

        // Get recent subscription events
        $events = Database::fetchAll(
            "SELECT e.*, u.username, u.email
             FROM subscription_events e
             LEFT JOIN users u ON e.user_id = u.id
             ORDER BY e.created_at DESC
             LIMIT 50"
        );

        return view('admin/subscription', [
            'config' => $config,
            'events' => $events,
        ]);
    }

    /**
     * Update subscription settings
     */
    public function updateSettings(): void
    {
        try {
            if (!is_admin()) {
                http_response_code(403);
                echo view('errors/403');
                return;
            }

            if (!verify_csrf()) {
                flash('error', 'Invalid request.');
                redirect('/admin/subscription');
            }

            $settings = [
                'trial_days' => max(1, intval($_POST['trial_days'] ?? 7)),
                'subscription_check_enabled' => isset($_POST['subscription_check_enabled']) ? 1 : 0,
                'expired_message' => trim($_POST['expired_message'] ?? ''),
                'bmac_webhook_secret' => trim($_POST['bmac_webhook_secret'] ?? ''),
                'bmac_membership_url' => trim($_POST['bmac_membership_url'] ?? ''),
            ];

            // Only update access token if provided (don't clear it)
            if (!empty($_POST['bmac_access_token'])) {
                $settings['bmac_access_token'] = trim($_POST['bmac_access_token']);
            }

            SubscriptionChecker::updateConfig($settings);

            // Log the change
            ActivityLogger::logAdminAction('subscription_settings_updated', null, null, [
                'subscription_check_enabled' => $settings['subscription_check_enabled'],
                'trial_days' => $settings['trial_days'],
            ]);

            flash('success', 'Subscription settings updated successfully.');
            redirect('/admin/subscription');
        } catch (\Throwable $e) {
            error_log("Subscription settings update error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            flash('error', 'An error occurred: ' . $e->getMessage());
            redirect('/admin/subscription');
        }
    }

    /**
     * Manually update a user's subscription status
     */
    public function updateUserSubscription(int $id): void
    {
        if (!is_admin()) {
            http_response_code(403);
            echo view('errors/403');
            return;
        }

        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/admin');
        }

        $user = Database::fetch(
            "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );

        if (!$user) {
            flash('error', 'User not found.');
            redirect('/admin');
        }

        $status = $_POST['subscription_status'] ?? '';
        $validStatuses = ['trial', 'active', 'lifetime', 'expired', 'cancelled'];

        if (!in_array($status, $validStatuses)) {
            flash('error', 'Invalid subscription status.');
            redirect('/admin/users/' . $id . '/edit');
        }

        switch ($status) {
            case 'trial':
                SubscriptionChecker::initializeTrial($id);
                break;
            case 'active':
                SubscriptionChecker::activateSubscription($id);
                break;
            case 'lifetime':
                SubscriptionChecker::setLifetime($id);
                break;
            case 'expired':
                SubscriptionChecker::expireSubscription($id);
                break;
            case 'cancelled':
                SubscriptionChecker::cancelSubscription($id);
                break;
        }

        // Log admin action
        ActivityLogger::logAdminAction('subscription_override', 'user', $id, [
            'new_status' => $status,
            'old_status' => $user['subscription_status'],
        ]);

        flash('success', "User subscription status updated to '{$status}'.");
        redirect('/admin/users/' . $id . '/edit');
    }
}
