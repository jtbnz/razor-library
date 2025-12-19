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

        // Get backup info
        $backupDir = config('UPLOAD_PATH') . '/backups';
        $lastBackup = null;
        $backups = [];

        if (is_dir($backupDir)) {
            $files = glob($backupDir . '/razor_library_backup_*.zip');
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            foreach ($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'size' => filesize($file),
                    'date' => date('Y-m-d H:i:s', filemtime($file)),
                ];
            }

            if (!empty($backups)) {
                $lastBackup = $backups[0];
            }
        }

        return view('admin/index', [
            'users' => $users,
            'lastBackup' => $lastBackup,
            'backups' => $backups,
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

    /**
     * Create a database backup
     */
    public function backup(): void
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

        $dbPath = config('DB_PATH');
        $uploadPath = config('UPLOAD_PATH');
        $backupDir = $uploadPath . '/backups';

        // Create backup directory if it doesn't exist
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backupFilename = "razor_library_backup_{$timestamp}.zip";
        $backupPath = $backupDir . '/' . $backupFilename;

        $zip = new ZipArchive();
        if ($zip->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            flash('error', 'Failed to create backup archive.');
            redirect('/admin');
            return;
        }

        // Add database file
        if (file_exists($dbPath)) {
            $zip->addFile($dbPath, 'razor_library.db');
        }

        // Add uploads directory (excluding backups)
        $this->addDirectoryToZip($zip, $uploadPath, 'uploads', ['backups']);

        $zip->close();

        flash('success', 'Backup created successfully: ' . $backupFilename);
        redirect('/admin');
    }

    /**
     * Download a backup file
     */
    public function downloadBackup(string $filename): void
    {
        if (!is_admin()) {
            http_response_code(403);
            echo view('errors/403');
            return;
        }

        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);
        $backupPath = config('UPLOAD_PATH') . '/backups/' . $filename;

        if (!file_exists($backupPath) || !preg_match('/^razor_library_backup_.*\.zip$/', $filename)) {
            flash('error', 'Backup not found.');
            redirect('/admin');
            return;
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($backupPath));
        readfile($backupPath);
        exit;
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup(string $filename): void
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

        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);
        $backupPath = config('UPLOAD_PATH') . '/backups/' . $filename;

        if (!file_exists($backupPath) || !preg_match('/^razor_library_backup_.*\.zip$/', $filename)) {
            flash('error', 'Backup not found.');
            redirect('/admin');
            return;
        }

        if (unlink($backupPath)) {
            flash('success', 'Backup deleted successfully.');
        } else {
            flash('error', 'Failed to delete backup.');
        }
        redirect('/admin');
    }

    /**
     * Restore from a backup file
     */
    public function restore(): void
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

        $filename = $_POST['backup_file'] ?? '';
        if (empty($filename)) {
            flash('error', 'No backup file selected.');
            redirect('/admin');
            return;
        }

        // Sanitize filename
        $filename = basename($filename);
        $backupPath = config('UPLOAD_PATH') . '/backups/' . $filename;

        if (!file_exists($backupPath) || !preg_match('/^razor_library_backup_.*\.zip$/', $filename)) {
            flash('error', 'Backup not found.');
            redirect('/admin');
            return;
        }

        $zip = new ZipArchive();
        if ($zip->open($backupPath) !== true) {
            flash('error', 'Failed to open backup archive.');
            redirect('/admin');
            return;
        }

        $tempDir = sys_get_temp_dir() . '/razor_restore_' . uniqid();
        mkdir($tempDir, 0755, true);

        $zip->extractTo($tempDir);
        $zip->close();

        // Restore database
        $dbPath = config('DB_PATH');
        $restoredDb = $tempDir . '/razor_library.db';

        if (file_exists($restoredDb)) {
            // Close current database connection
            Database::close();

            // Backup current database before restore
            if (file_exists($dbPath)) {
                copy($dbPath, $dbPath . '.pre_restore');
            }

            // Copy restored database
            if (copy($restoredDb, $dbPath)) {
                // Reinitialize database
                Database::init();
                flash('success', 'Database restored successfully from: ' . $filename);
            } else {
                flash('error', 'Failed to restore database.');
            }
        }

        // Restore uploads
        $restoredUploads = $tempDir . '/uploads';
        if (is_dir($restoredUploads)) {
            $uploadPath = config('UPLOAD_PATH');
            $this->recursiveCopy($restoredUploads, $uploadPath);
        }

        // Clean up temp directory
        $this->recursiveDelete($tempDir);

        redirect('/admin');
    }

    /**
     * Reset database with confirmation
     */
    public function resetDatabase(): void
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

        $confirmText = $_POST['confirm_text'] ?? '';
        $keepUploads = isset($_POST['keep_uploads']);

        if ($confirmText !== 'RESET DATABASE') {
            flash('error', 'Please type "RESET DATABASE" exactly to confirm.');
            redirect('/admin');
            return;
        }

        $dbPath = config('DB_PATH');
        $uploadPath = config('UPLOAD_PATH');

        // Close database connection
        Database::close();

        // Delete database file
        if (file_exists($dbPath)) {
            if (!unlink($dbPath)) {
                flash('error', 'Failed to delete database file.');
                redirect('/admin');
                return;
            }
        }

        // Delete uploads if not keeping them
        if (!$keepUploads) {
            $this->recursiveDelete($uploadPath, false);
        }

        // Reinitialize database (creates fresh one with migrations)
        Database::init();

        // Log out the user since their session is no longer valid
        session_destroy();

        // Start new session for flash message
        session_start();
        flash('success', 'Database has been reset. Please create a new admin account.');
        redirect('/setup');
    }

    /**
     * Add directory contents to ZIP archive
     */
    private function addDirectoryToZip(ZipArchive $zip, string $path, string $zipPath, array $exclude = []): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = scandir($path);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (in_array($file, $exclude)) {
                continue;
            }

            $filePath = $path . '/' . $file;
            $zipFilePath = $zipPath . '/' . $file;

            if (is_dir($filePath)) {
                $this->addDirectoryToZip($zip, $filePath, $zipFilePath, $exclude);
            } else {
                $zip->addFile($filePath, $zipFilePath);
            }
        }
    }

    /**
     * Recursively copy directory
     */
    private function recursiveCopy(string $src, string $dst): void
    {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }

        $dir = opendir($src);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;

            if (is_dir($srcPath)) {
                $this->recursiveCopy($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
        closedir($dir);
    }

    /**
     * Recursively delete directory
     */
    private function recursiveDelete(string $dir, bool $removeDir = true): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            // Don't delete backups directory
            if ($file === 'backups') {
                continue;
            }

            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->recursiveDelete($path, true);
            } else {
                unlink($path);
            }
        }

        if ($removeDir) {
            rmdir($dir);
        }
    }
}
