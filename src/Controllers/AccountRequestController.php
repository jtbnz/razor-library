<?php
/**
 * Account Request Controller
 * Handles account request submission and admin review
 */

class AccountRequestController
{
    /**
     * Show account request form
     */
    public function create(): string
    {
        return view('auth/request-account');
    }

    /**
     * Store a new account request
     */
    public function store(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/request-account');
        }

        // Rate limit: 3 requests per hour per IP
        $ip = $_SERVER['REMOTE_ADDR'];
        if (RateLimiter::isLimited($ip, 'account_request', 3, 3600)) {
            flash('error', 'Too many requests. Please try again later.');
            redirect('/request-account');
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $termsAccepted = isset($_POST['terms_accepted']);

        // Validation
        $errors = [];

        if (empty($username)) {
            $errors[] = 'Username is required.';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Username must be between 3 and 50 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, underscores, and hyphens.';
        }

        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (!$termsAccepted) {
            $errors[] = 'You must accept the Terms and Conditions.';
        }

        if (!empty($errors)) {
            flash('error', implode(' ', $errors));
            set_old($_POST);
            redirect('/request-account');
        }

        // Check if username already exists
        $existingUser = Database::fetch(
            "SELECT id FROM users WHERE LOWER(username) = LOWER(?)",
            [$username]
        );

        if ($existingUser) {
            flash('error', 'This username is already taken.');
            set_old($_POST);
            redirect('/request-account');
        }

        // Check if email already exists as a user
        $existingEmail = Database::fetch(
            "SELECT id FROM users WHERE LOWER(email) = LOWER(?)",
            [$email]
        );

        if ($existingEmail) {
            flash('error', 'An account with this email already exists.');
            set_old($_POST);
            redirect('/request-account');
        }

        // Check for pending request with same email
        $pendingRequest = Database::fetch(
            "SELECT id FROM account_requests WHERE LOWER(email) = LOWER(?) AND status = 'pending'",
            [$email]
        );

        if ($pendingRequest) {
            flash('error', 'A request for this email is already pending review.');
            set_old($_POST);
            redirect('/request-account');
        }

        // Create the request
        Database::query(
            "INSERT INTO account_requests (username, email, reason) VALUES (?, ?, ?)",
            [$username, $email, $reason ?: null]
        );

        // Log the activity
        if (class_exists('ActivityLogger')) {
            ActivityLogger::log('account_request_submitted', 'account_request', Database::lastInsertId(), [
                'username' => $username,
                'email' => $email,
                'ip' => $ip,
            ]);
        }

        // Notify admin
        $this->notifyAdminOfRequest($username, $email, $reason);

        clear_old();
        flash('success', 'Your account request has been submitted. You will receive an email when it has been reviewed.');
        redirect('/login');
    }

    /**
     * Show pending requests (admin only)
     */
    public function pending(): string
    {
        $requests = Database::fetchAll(
            "SELECT * FROM account_requests WHERE status = 'pending' ORDER BY created_at ASC"
        );

        return view('admin/pending-requests', [
            'requests' => $requests,
        ]);
    }

    /**
     * Approve an account request
     */
    public function approve(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/admin/requests');
        }

        $request = Database::fetch(
            "SELECT * FROM account_requests WHERE id = ? AND status = 'pending'",
            [$id]
        );

        if (!$request) {
            flash('error', 'Request not found or already processed.');
            redirect('/admin/requests');
        }

        // Generate a temporary password
        $tempPassword = $this->generateTempPassword();
        $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);

        // Create the user account
        Database::query(
            "INSERT INTO users (username, email, password, subscription_status, subscription_started_at, subscription_expires_at)
             VALUES (?, ?, ?, 'trial', CURRENT_TIMESTAMP, datetime('now', '+' || ? || ' days'))",
            [$request['username'], $request['email'], $passwordHash, config('TRIAL_DAYS', 7)]
        );

        $userId = Database::lastInsertId();

        // Initialize subscription if checker exists
        if (class_exists('SubscriptionChecker')) {
            SubscriptionChecker::initializeTrial($userId);
        }

        // Mark request as approved
        Database::query(
            "UPDATE account_requests SET status = 'approved', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$_SESSION['user_id'], $id]
        );

        // Log the activity
        if (class_exists('ActivityLogger')) {
            ActivityLogger::log('account_request_approved', 'user', $userId, [
                'request_id' => $id,
                'username' => $request['username'],
            ]);
        }

        // Send welcome email
        $this->sendApprovalEmail($request['email'], $request['username'], $tempPassword);

        flash('success', "Account created for {$request['username']}. Welcome email sent.");
        redirect('/admin/requests');
    }

    /**
     * Reject an account request
     */
    public function reject(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/admin/requests');
        }

        $request = Database::fetch(
            "SELECT * FROM account_requests WHERE id = ? AND status = 'pending'",
            [$id]
        );

        if (!$request) {
            flash('error', 'Request not found or already processed.');
            redirect('/admin/requests');
        }

        $rejectionReason = trim($_POST['rejection_reason'] ?? '');

        // Mark request as rejected
        Database::query(
            "UPDATE account_requests SET status = 'rejected', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP, rejection_reason = ? WHERE id = ?",
            [$_SESSION['user_id'], $rejectionReason ?: null, $id]
        );

        // Log the activity
        if (class_exists('ActivityLogger')) {
            ActivityLogger::log('account_request_rejected', 'account_request', $id, [
                'username' => $request['username'],
                'reason' => $rejectionReason,
            ]);
        }

        // Send rejection email
        $this->sendRejectionEmail($request['email'], $request['username'], $rejectionReason);

        flash('success', "Request from {$request['username']} has been rejected.");
        redirect('/admin/requests');
    }

    /**
     * Generate a temporary password
     */
    private function generateTempPassword(): string
    {
        $length = 12;
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }

    /**
     * Notify admin of new account request
     */
    private function notifyAdminOfRequest(string $username, string $email, ?string $reason): void
    {
        if (!class_exists('Mailer')) {
            return;
        }

        // Get admin emails
        $admins = Database::fetchAll("SELECT email FROM users WHERE is_admin = 1");

        foreach ($admins as $admin) {
            $subject = "New Account Request: {$username}";
            $body = "
                <h2>New Account Request</h2>
                <p>A new account request has been submitted:</p>
                <table>
                    <tr><td><strong>Username:</strong></td><td>{$username}</td></tr>
                    <tr><td><strong>Email:</strong></td><td>{$email}</td></tr>
                    <tr><td><strong>Reason:</strong></td><td>" . ($reason ?: 'Not provided') . "</td></tr>
                </table>
                <p><a href=\"" . url('/admin/requests') . "\" style=\"display: inline-block; padding: 10px 20px; background: #2D3748; color: white; text-decoration: none; border-radius: 5px;\">Review Request</a></p>
            ";

            Mailer::send($admin['email'], $subject, $body);
        }
    }

    /**
     * Send approval email with credentials
     */
    private function sendApprovalEmail(string $email, string $username, string $tempPassword): void
    {
        if (!class_exists('Mailer')) {
            return;
        }

        $trialDays = config('TRIAL_DAYS', 7);
        $subject = "Welcome to Razor Library - Your Account is Ready";
        $body = "
            <h2>Welcome to Razor Library!</h2>
            <p>Great news! Your account request has been approved.</p>

            <h3>Your Login Credentials</h3>
            <table>
                <tr><td><strong>Email:</strong></td><td>{$email}</td></tr>
                <tr><td><strong>Temporary Password:</strong></td><td>{$tempPassword}</td></tr>
            </table>

            <p><strong>Important:</strong> Please change your password after logging in.</p>

            <h3>Your Free Trial</h3>
            <p>You have a <strong>{$trialDays}-day free trial</strong> with full access to all features. After your trial, you'll need to subscribe to continue using Razor Library.</p>

            <p><a href=\"" . url('/login') . "\" style=\"display: inline-block; padding: 10px 20px; background: #2D3748; color: white; text-decoration: none; border-radius: 5px;\">Log In Now</a></p>

            <p>Happy shaving!</p>
        ";

        Mailer::send($email, $subject, $body);
    }

    /**
     * Send rejection email
     */
    private function sendRejectionEmail(string $email, string $username, ?string $reason): void
    {
        if (!class_exists('Mailer')) {
            return;
        }

        $subject = "Razor Library - Account Request Update";
        $body = "
            <h2>Account Request Update</h2>
            <p>Thank you for your interest in Razor Library.</p>
            <p>Unfortunately, we are unable to approve your account request at this time.</p>
        ";

        if ($reason) {
            $body .= "<p><strong>Reason:</strong> " . e($reason) . "</p>";
        }

        $body .= "
            <p>If you believe this was a mistake or would like more information, please contact the site administrator.</p>
        ";

        Mailer::send($email, $subject, $body);
    }
}
