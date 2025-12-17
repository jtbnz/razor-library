<?php
/**
 * Admin Controller
 */

class AdminController
{
    /**
     * Admin dashboard - list all users
     */
    public function index(): string
    {
        if (!is_admin()) {
            http_response_code(403);
            return view('errors/403');
        }

        $users = Database::fetchAll(
            "SELECT u.*,
                    (SELECT COUNT(*) FROM razors WHERE user_id = u.id AND deleted_at IS NULL) as razor_count,
                    (SELECT COUNT(*) FROM blades WHERE user_id = u.id AND deleted_at IS NULL) as blade_count,
                    (SELECT COUNT(*) FROM brushes WHERE user_id = u.id AND deleted_at IS NULL) as brush_count,
                    (SELECT COUNT(*) FROM other_items WHERE user_id = u.id AND deleted_at IS NULL) as other_count
             FROM users u
             WHERE u.deleted_at IS NULL
             ORDER BY u.created_at DESC"
        );

        return view('admin/index', [
            'users' => $users,
        ]);
    }

    /**
     * Show create user form
     */
    public function create(): string
    {
        if (!is_admin()) {
            http_response_code(403);
            return view('errors/403');
        }

        return view('admin/create');
    }

    /**
     * Store a new user
     */
    public function store(): void
    {
        if (!is_admin()) {
            http_response_code(403);
            echo view('errors/403');
            return;
        }

        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/admin/users/new');
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;

        // Validate
        if (empty($username)) {
            flash('error', 'Username is required.');
            set_old($_POST);
            redirect('/admin/users/new');
        }

        if (strlen($password) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            set_old($_POST);
            redirect('/admin/users/new');
        }

        // Check if username exists
        $existingUser = Database::fetch(
            "SELECT id FROM users WHERE username = ? AND deleted_at IS NULL",
            [$username]
        );

        if ($existingUser) {
            flash('error', 'Username is already taken.');
            set_old($_POST);
            redirect('/admin/users/new');
        }

        // Check if email exists (if provided)
        if (!empty($email)) {
            if (!is_valid_email($email)) {
                flash('error', 'Please enter a valid email address.');
                set_old($_POST);
                redirect('/admin/users/new');
            }

            $existingEmail = Database::fetch(
                "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL",
                [$email]
            );

            if ($existingEmail) {
                flash('error', 'Email is already in use.');
                set_old($_POST);
                redirect('/admin/users/new');
            }
        }

        // Create user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $shareToken = generate_token(16);

        Database::query(
            "INSERT INTO users (username, email, password, is_admin, share_token) VALUES (?, ?, ?, ?, ?)",
            [$username, $email ?: null, $hashedPassword, $isAdmin, $shareToken]
        );

        clear_old();
        flash('success', 'User created successfully.');
        redirect('/admin');
    }

    /**
     * Show edit user form
     */
    public function edit(int $id): string
    {
        if (!is_admin()) {
            http_response_code(403);
            return view('errors/403');
        }

        $user = Database::fetch(
            "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );

        if (!$user) {
            http_response_code(404);
            return view('errors/404');
        }

        return view('admin/edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update a user
     */
    public function update(int $id): void
    {
        if (!is_admin()) {
            http_response_code(403);
            echo view('errors/403');
            return;
        }

        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/admin/users/{$id}/edit");
        }

        $user = Database::fetch(
            "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );

        if (!$user) {
            http_response_code(404);
            echo view('errors/404');
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;

        // Validate
        if (empty($username)) {
            flash('error', 'Username is required.');
            redirect("/admin/users/{$id}/edit");
        }

        // Check if username exists for other users
        $existingUser = Database::fetch(
            "SELECT id FROM users WHERE username = ? AND id != ? AND deleted_at IS NULL",
            [$username, $id]
        );

        if ($existingUser) {
            flash('error', 'Username is already taken.');
            redirect("/admin/users/{$id}/edit");
        }

        // Check if email exists for other users (if provided)
        if (!empty($email)) {
            if (!is_valid_email($email)) {
                flash('error', 'Please enter a valid email address.');
                redirect("/admin/users/{$id}/edit");
            }

            $existingEmail = Database::fetch(
                "SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL",
                [$email, $id]
            );

            if ($existingEmail) {
                flash('error', 'Email is already in use.');
                redirect("/admin/users/{$id}/edit");
            }
        }

        // Prevent removing admin status from yourself
        if ($id === $_SESSION['user_id'] && !$isAdmin) {
            flash('error', 'You cannot remove admin status from yourself.');
            redirect("/admin/users/{$id}/edit");
        }

        // Update user
        if (!empty($password)) {
            if (strlen($password) < 8) {
                flash('error', 'Password must be at least 8 characters.');
                redirect("/admin/users/{$id}/edit");
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            Database::query(
                "UPDATE users SET username = ?, email = ?, password = ?, is_admin = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$username, $email ?: null, $hashedPassword, $isAdmin, $id]
            );
        } else {
            Database::query(
                "UPDATE users SET username = ?, email = ?, is_admin = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$username, $email ?: null, $isAdmin, $id]
            );
        }

        flash('success', 'User updated successfully.');
        redirect('/admin');
    }

    /**
     * Delete a user (soft delete)
     */
    public function delete(int $id): void
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

        // Prevent deleting yourself
        if ($id === $_SESSION['user_id']) {
            flash('error', 'You cannot delete your own account.');
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

        Database::query(
            "UPDATE users SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$id]
        );

        flash('success', 'User deleted successfully.');
        redirect('/admin');
    }
}
