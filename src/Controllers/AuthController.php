<?php
/**
 * Authentication Controller
 */

class AuthController
{
    /**
     * Show login form
     */
    public function loginForm(): void
    {
        // Check if setup is needed (no users exist)
        $userCount = Database::fetch("SELECT COUNT(*) as count FROM users WHERE deleted_at IS NULL");
        if ($userCount['count'] == 0) {
            redirect('/setup');
            return;
        }

        // Already logged in?
        if (is_authenticated()) {
            redirect('/dashboard');
            return;
        }

        echo view('auth/login');
    }

    /**
     * Process login
     */
    public function login(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request. Please try again.');
            redirect('/login');
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate input
        if (empty($email) || empty($password)) {
            flash('error', 'Please enter your email and password.');
            set_old(['email' => $email]);
            redirect('/login');
            return;
        }

        // Check rate limiting
        $ip = $_SERVER['REMOTE_ADDR'];
        if (RateLimiter::isLimited(
            $ip,
            'login',
            config('RATE_LIMIT_LOGIN_ATTEMPTS'),
            config('RATE_LIMIT_LOGIN_WINDOW')
        )) {
            flash('error', 'Too many login attempts. Please try again later.');
            redirect('/login');
            return;
        }

        // Find user
        $user = Database::fetch(
            "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL",
            [$email]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            RateLimiter::hit($ip, 'login');
            ActivityLogger::logLoginFailed($email);
            flash('error', 'Invalid email or password.');
            set_old(['email' => $email]);
            redirect('/login');
            return;
        }

        // Clear rate limit on successful login
        RateLimiter::clear($ip, 'login');

        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = (bool) $user['is_admin'];

        // Log successful login
        ActivityLogger::logLogin($user['id'], $user['email']);

        clear_old();
        redirect('/dashboard');
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        session_destroy();
        redirect('/');
    }

    /**
     * Show initial setup form (first user creation)
     */
    public function setupForm(): void
    {
        // Check if setup is already done
        $userCount = Database::fetch("SELECT COUNT(*) as count FROM users");
        if ($userCount['count'] > 0) {
            redirect('/login');
            return;
        }

        echo view('auth/setup');
    }

    /**
     * Process initial setup
     */
    public function setup(): void
    {
        // Check if setup is already done
        $userCount = Database::fetch("SELECT COUNT(*) as count FROM users");
        if ($userCount['count'] > 0) {
            redirect('/login');
            return;
        }

        if (!verify_csrf()) {
            flash('error', 'Invalid request. Please try again.');
            redirect('/setup');
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $errors = [];

        // Validate username
        if (empty($username)) {
            $errors[] = 'Username is required.';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
        }

        // Validate email
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!is_valid_email($email)) {
            $errors[] = 'Please enter a valid email address.';
        }

        // Validate password
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < config('PASSWORD_MIN_LENGTH')) {
            $errors[] = 'Password must be at least ' . config('PASSWORD_MIN_LENGTH') . ' characters.';
        } elseif ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            flash('error', implode('<br>', $errors));
            set_old(['username' => $username, 'email' => $email]);
            redirect('/setup');
            return;
        }

        // Create the admin user
        $shareToken = generate_token(16);
        Database::query(
            "INSERT INTO users (username, email, password_hash, is_admin, share_token) VALUES (?, ?, ?, 1, ?)",
            [$username, $email, password_hash($password, PASSWORD_BCRYPT), $shareToken]
        );

        // Create upload directories
        $userId = Database::lastInsertId();
        $uploadPath = config('UPLOAD_PATH') . '/users/' . $userId;
        foreach (['razors', 'blades', 'brushes', 'other'] as $dir) {
            @mkdir($uploadPath . '/' . $dir, 0755, true);
        }
        @mkdir(config('UPLOAD_PATH') . '/system', 0755, true);

        flash('success', 'Account created successfully! Please log in.');
        redirect('/login');
    }

    /**
     * Show forgot password form
     */
    public function forgotPasswordForm(): void
    {
        if (is_authenticated()) {
            redirect('/dashboard');
            return;
        }

        echo view('auth/forgot-password');
    }

    /**
     * Process forgot password request
     */
    public function forgotPassword(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request. Please try again.');
            redirect('/forgot-password');
            return;
        }

        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            flash('error', 'Please enter your email address.');
            redirect('/forgot-password');
            return;
        }

        // Check rate limiting
        if (RateLimiter::isLimited(
            $email,
            'password_reset',
            config('RATE_LIMIT_RESET_REQUESTS'),
            config('RATE_LIMIT_RESET_WINDOW')
        )) {
            // Don't reveal rate limiting - show same message
            flash('success', 'If an account with that email exists, we\'ve sent a password reset link.');
            redirect('/forgot-password');
            return;
        }

        RateLimiter::hit($email, 'password_reset');

        // Find user
        $user = Database::fetch(
            "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL",
            [$email]
        );

        // Always show success message to prevent email enumeration
        if ($user) {
            // Generate reset token
            $token = generate_token(32);
            $expires = date('Y-m-d H:i:s', time() + config('RESET_TOKEN_EXPIRY'));

            Database::query(
                "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?",
                [$token, $expires, $user['id']]
            );

            // Send email
            Mailer::sendPasswordReset($user['email'], $user['username'], $token);
        }

        flash('success', 'If an account with that email exists, we\'ve sent a password reset link.');
        redirect('/forgot-password');
    }

    /**
     * Show reset password form
     */
    public function resetPasswordForm(string $token): void
    {
        if (is_authenticated()) {
            redirect('/dashboard');
            return;
        }

        // Validate token
        $user = Database::fetch(
            "SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > CURRENT_TIMESTAMP AND deleted_at IS NULL",
            [$token]
        );

        if (!$user) {
            flash('error', 'Invalid or expired reset link. Please request a new one.');
            redirect('/forgot-password');
            return;
        }

        echo view('auth/reset-password', ['token' => $token]);
    }

    /**
     * Process password reset
     */
    public function resetPassword(string $token): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request. Please try again.');
            redirect('/reset-password/' . $token);
            return;
        }

        // Validate token
        $user = Database::fetch(
            "SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > CURRENT_TIMESTAMP AND deleted_at IS NULL",
            [$token]
        );

        if (!$user) {
            flash('error', 'Invalid or expired reset link. Please request a new one.');
            redirect('/forgot-password');
            return;
        }

        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $errors = [];

        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < config('PASSWORD_MIN_LENGTH')) {
            $errors[] = 'Password must be at least ' . config('PASSWORD_MIN_LENGTH') . ' characters.';
        } elseif ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            flash('error', implode('<br>', $errors));
            redirect('/reset-password/' . $token);
            return;
        }

        // Update password and clear token
        Database::query(
            "UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?",
            [password_hash($password, PASSWORD_BCRYPT), $user['id']]
        );

        // Log password reset
        ActivityLogger::log('password_reset', 'user', $user['id'], null, $user['id']);

        // Clear rate limit
        RateLimiter::clear($user['email'], 'password_reset');

        flash('success', 'Password updated successfully! Please log in with your new password.');
        redirect('/login');
    }
}
