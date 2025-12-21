<?php
/**
 * Profile Controller
 */

class ProfileController
{
    /**
     * Display user profile
     */
    public function index(): string
    {
        $userId = $_SESSION['user_id'];

        $user = Database::fetch(
            "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL",
            [$userId]
        );

        if (!$user) {
            redirect('/logout');
        }

        // Get collection stats
        $stats = [
            'razors' => Database::fetch("SELECT COUNT(*) as count FROM razors WHERE user_id = ? AND deleted_at IS NULL", [$userId])['count'],
            'blades' => Database::fetch("SELECT COUNT(*) as count FROM blades WHERE user_id = ? AND deleted_at IS NULL", [$userId])['count'],
            'brushes' => Database::fetch("SELECT COUNT(*) as count FROM brushes WHERE user_id = ? AND deleted_at IS NULL", [$userId])['count'],
            'other' => Database::fetch("SELECT COUNT(*) as count FROM other_items WHERE user_id = ? AND deleted_at IS NULL", [$userId])['count'],
        ];

        return view('profile/index', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    /**
     * Update profile
     */
    public function update(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/profile');
        }

        $userId = $_SESSION['user_id'];

        $user = Database::fetch(
            "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL",
            [$userId]
        );

        if (!$user) {
            redirect('/logout');
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate username
        if (empty($username)) {
            flash('error', 'Username is required.');
            redirect('/profile');
        }

        // Check if username is taken by another user
        $existingUser = Database::fetch(
            "SELECT id FROM users WHERE username = ? AND id != ? AND deleted_at IS NULL",
            [$username, $userId]
        );

        if ($existingUser) {
            flash('error', 'Username is already taken.');
            redirect('/profile');
        }

        // Validate email if provided and different from current
        $emailChanged = false;
        if (!empty($email) && $email !== $user['email']) {
            if (!is_valid_email($email)) {
                flash('error', 'Please enter a valid email address.');
                redirect('/profile');
            }

            // Check if email is taken by another user
            $existingEmail = Database::fetch(
                "SELECT id FROM users WHERE (email = ? OR pending_email = ?) AND id != ? AND deleted_at IS NULL",
                [$email, $email, $userId]
            );

            if ($existingEmail) {
                flash('error', 'Email is already in use.');
                redirect('/profile');
            }

            // Rate limit email changes (1 per hour)
            if (RateLimiter::isLimited($userId, 'email_change', 1, 3600)) {
                flash('error', 'You can only change your email once per hour. Please try again later.');
                redirect('/profile');
            }

            $emailChanged = true;
        }

        // Handle password change
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                flash('error', 'Current password is required to change password.');
                redirect('/profile');
            }

            if (!password_verify($currentPassword, $user['password_hash'])) {
                flash('error', 'Current password is incorrect.');
                redirect('/profile');
            }

            if (strlen($newPassword) < 8) {
                flash('error', 'New password must be at least 8 characters.');
                redirect('/profile');
            }

            if ($newPassword !== $confirmPassword) {
                flash('error', 'New passwords do not match.');
                redirect('/profile');
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            Database::query(
                "UPDATE users SET password_hash = ? WHERE id = ?",
                [$hashedPassword, $userId]
            );

            // Log password change
            ActivityLogger::logPasswordChange($userId);
        }

        // Update username
        Database::query(
            "UPDATE users SET username = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$username, $userId]
        );

        // Handle email change with verification
        if ($emailChanged) {
            $token = generate_token(32);
            $expires = date('Y-m-d H:i:s', time() + 86400); // 24 hours

            Database::query(
                "UPDATE users SET pending_email = ?, email_verification_token = ?, email_verification_expires = ? WHERE id = ?",
                [$email, $token, $expires, $userId]
            );

            RateLimiter::hit($userId, 'email_change');

            // Send verification email to NEW address
            Mailer::sendEmailVerification($email, $user['username'], $token);

            // Log email change initiation
            ActivityLogger::log('email_change_initiated', 'user', $userId, ['new_email' => $email]);

            flash('success', 'Profile updated. Please check your new email address for a verification link.');
        } else {
            flash('success', 'Profile updated successfully.');
        }

        redirect('/profile');
    }

    /**
     * Verify email change
     */
    public function verifyEmail(string $token): void
    {
        $user = Database::fetch(
            "SELECT * FROM users WHERE email_verification_token = ? AND email_verification_expires > CURRENT_TIMESTAMP AND deleted_at IS NULL",
            [$token]
        );

        if (!$user) {
            flash('error', 'Invalid or expired verification link. Please request a new one from your profile.');
            redirect('/login');
            return;
        }

        $oldEmail = $user['email'];
        $newEmail = $user['pending_email'];

        // Update email and clear verification fields
        Database::query(
            "UPDATE users SET email = ?, pending_email = NULL, email_verification_token = NULL, email_verification_expires = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$newEmail, $user['id']]
        );

        // Log email change completion
        ActivityLogger::log('email_changed', 'user', $user['id'], ['old_email' => $oldEmail, 'new_email' => $newEmail], $user['id']);

        // Notify old email about the change (security alert)
        if ($oldEmail) {
            Mailer::sendEmailChangedNotification($oldEmail, $user['username'], $newEmail);
        }

        flash('success', 'Your email address has been successfully updated.');
        redirect('/profile');
    }

    /**
     * Cancel pending email change
     */
    public function cancelEmailChange(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/profile');
        }

        $userId = $_SESSION['user_id'];

        Database::query(
            "UPDATE users SET pending_email = NULL, email_verification_token = NULL, email_verification_expires = NULL WHERE id = ?",
            [$userId]
        );

        // Log cancellation
        ActivityLogger::log('email_change_cancelled', 'user', $userId);

        flash('success', 'Pending email change has been cancelled.');
        redirect('/profile');
    }

    /**
     * Regenerate share token
     */
    public function regenerateShareToken(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/profile');
        }

        $userId = $_SESSION['user_id'];

        $newToken = generate_token(16);

        Database::query(
            "UPDATE users SET share_token = ? WHERE id = ?",
            [$newToken, $userId]
        );

        flash('success', 'Share link regenerated. Your old link will no longer work.');
        redirect('/profile');
    }

    /**
     * Export collection as ZIP
     */
    public function export(): void
    {
        $userId = $_SESSION['user_id'];

        $user = Database::fetch(
            "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL",
            [$userId]
        );

        if (!$user) {
            redirect('/logout');
        }

        // Create temp directory for export
        $tempDir = sys_get_temp_dir() . '/razor-export-' . $userId . '-' . time();
        mkdir($tempDir, 0755, true);

        try {
            // Export razors
            $this->exportRazors($userId, $tempDir);

            // Export blades
            $this->exportBlades($userId, $tempDir);

            // Export brushes
            $this->exportBrushes($userId, $tempDir);

            // Export other items
            $this->exportOtherItems($userId, $tempDir);

            // Create ZIP file
            $zipPath = $tempDir . '.zip';
            $this->createZip($tempDir, $zipPath);

            // Clean up temp directory
            $this->deleteDirectory($tempDir);

            // Send ZIP to browser
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="razor-collection-' . date('Y-m-d') . '.zip"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);

            // Clean up ZIP file
            unlink($zipPath);
            exit;

        } catch (Exception $e) {
            // Clean up on error
            if (is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
            flash('error', 'Failed to export collection: ' . $e->getMessage());
            redirect('/profile');
        }
    }

    /**
     * Export razors to markdown files
     */
    private function exportRazors(int $userId, string $baseDir): void
    {
        $razors = Database::fetchAll(
            "SELECT * FROM razors WHERE user_id = ? AND deleted_at IS NULL ORDER BY name",
            [$userId]
        );

        if (empty($razors)) return;

        $dir = $baseDir . '/razors';
        mkdir($dir, 0755, true);
        mkdir($dir . '/images', 0755, true);

        foreach ($razors as $razor) {
            $slug = slugify($razor['name']);
            $md = "# {$razor['name']}\n\n";

            if ($razor['description']) {
                $md .= "## Description\n{$razor['description']}\n\n";
            }

            if ($razor['notes']) {
                $md .= "## Notes\n{$razor['notes']}\n\n";
            }

            // Get URLs
            $urls = Database::fetchAll(
                "SELECT * FROM razor_urls WHERE razor_id = ?",
                [$razor['id']]
            );
            if (!empty($urls)) {
                $md .= "## Related URLs\n";
                foreach ($urls as $url) {
                    $md .= "- [{$url['url']}]({$url['url']})";
                    if ($url['description']) {
                        $md .= " - {$url['description']}";
                    }
                    $md .= "\n";
                }
                $md .= "\n";
            }

            // Get blade usage
            $usage = Database::fetchAll(
                "SELECT bu.count, b.name as blade_name
                 FROM blade_usage bu
                 JOIN blades b ON bu.blade_id = b.id
                 WHERE bu.razor_id = ?",
                [$razor['id']]
            );
            if (!empty($usage)) {
                $md .= "## Blade Usage\n";
                foreach ($usage as $u) {
                    $md .= "- {$u['blade_name']}: {$u['count']} uses\n";
                }
                $md .= "\n";
            }

            $md .= "---\n*Created: {$razor['created_at']}*\n";

            file_put_contents("{$dir}/{$slug}.md", $md);

            // Copy images
            $this->copyItemImages($userId, 'razors', $razor, $dir . '/images', $slug);
        }
    }

    /**
     * Export blades to markdown files
     */
    private function exportBlades(int $userId, string $baseDir): void
    {
        $blades = Database::fetchAll(
            "SELECT * FROM blades WHERE user_id = ? AND deleted_at IS NULL ORDER BY name",
            [$userId]
        );

        if (empty($blades)) return;

        $dir = $baseDir . '/blades';
        mkdir($dir, 0755, true);
        mkdir($dir . '/images', 0755, true);

        foreach ($blades as $blade) {
            $slug = slugify($blade['name']);
            $md = "# {$blade['name']}\n\n";

            if ($blade['brand']) {
                $md .= "**Brand:** {$blade['brand']}\n\n";
            }

            if ($blade['description']) {
                $md .= "## Description\n{$blade['description']}\n\n";
            }

            if ($blade['notes']) {
                $md .= "## Notes\n{$blade['notes']}\n\n";
            }

            // Get URLs
            $urls = Database::fetchAll(
                "SELECT * FROM blade_urls WHERE blade_id = ?",
                [$blade['id']]
            );
            if (!empty($urls)) {
                $md .= "## Related URLs\n";
                foreach ($urls as $url) {
                    $md .= "- [{$url['url']}]({$url['url']})";
                    if ($url['description']) {
                        $md .= " - {$url['description']}";
                    }
                    $md .= "\n";
                }
                $md .= "\n";
            }

            $md .= "---\n*Created: {$blade['created_at']}*\n";

            file_put_contents("{$dir}/{$slug}.md", $md);

            // Copy images
            $this->copyItemImages($userId, 'blades', $blade, $dir . '/images', $slug);
        }
    }

    /**
     * Export brushes to markdown files
     */
    private function exportBrushes(int $userId, string $baseDir): void
    {
        $brushes = Database::fetchAll(
            "SELECT * FROM brushes WHERE user_id = ? AND deleted_at IS NULL ORDER BY name",
            [$userId]
        );

        if (empty($brushes)) return;

        $dir = $baseDir . '/brushes';
        mkdir($dir, 0755, true);
        mkdir($dir . '/images', 0755, true);

        foreach ($brushes as $brush) {
            $slug = slugify($brush['name']);
            $md = "# {$brush['name']}\n\n";

            $md .= "## Specifications\n";
            if ($brush['brand']) $md .= "- **Brand:** {$brush['brand']}\n";
            if ($brush['bristle_type']) $md .= "- **Bristle Type:** {$brush['bristle_type']}\n";
            if ($brush['knot_size']) $md .= "- **Knot Size:** {$brush['knot_size']}\n";
            if ($brush['loft']) $md .= "- **Loft:** {$brush['loft']}\n";
            if ($brush['handle_material']) $md .= "- **Handle Material:** {$brush['handle_material']}\n";
            if ($brush['use_count'] > 0) $md .= "- **Total Uses:** {$brush['use_count']}\n";
            $md .= "\n";

            if ($brush['description']) {
                $md .= "## Description\n{$brush['description']}\n\n";
            }

            if ($brush['notes']) {
                $md .= "## Notes\n{$brush['notes']}\n\n";
            }

            // Get URLs
            $urls = Database::fetchAll(
                "SELECT * FROM brush_urls WHERE brush_id = ?",
                [$brush['id']]
            );
            if (!empty($urls)) {
                $md .= "## Related URLs\n";
                foreach ($urls as $url) {
                    $md .= "- [{$url['url']}]({$url['url']})";
                    if ($url['description']) {
                        $md .= " - {$url['description']}";
                    }
                    $md .= "\n";
                }
                $md .= "\n";
            }

            $md .= "---\n*Created: {$brush['created_at']}*\n";

            file_put_contents("{$dir}/{$slug}.md", $md);

            // Copy images
            $this->copyItemImages($userId, 'brushes', $brush, $dir . '/images', $slug);
        }
    }

    /**
     * Export other items to markdown files
     */
    private function exportOtherItems(int $userId, string $baseDir): void
    {
        $items = Database::fetchAll(
            "SELECT * FROM other_items WHERE user_id = ? AND deleted_at IS NULL ORDER BY category, name",
            [$userId]
        );

        if (empty($items)) return;

        $dir = $baseDir . '/other';
        mkdir($dir, 0755, true);
        mkdir($dir . '/images', 0755, true);

        foreach ($items as $item) {
            $slug = slugify($item['name']);
            $md = "# {$item['name']}\n\n";
            $md .= "**Category:** " . ucfirst($item['category']) . "\n\n";

            if ($item['brand']) {
                $md .= "**Brand:** {$item['brand']}\n\n";
            }

            // Get attributes
            $attrs = Database::fetchAll(
                "SELECT * FROM other_item_attributes WHERE item_id = ?",
                [$item['id']]
            );
            if (!empty($attrs)) {
                $md .= "## Details\n";
                foreach ($attrs as $attr) {
                    $label = ucwords(str_replace('_', ' ', $attr['attribute_name']));
                    $md .= "- **{$label}:** {$attr['attribute_value']}\n";
                }
                $md .= "\n";
            }

            if ($item['scent_notes']) {
                $md .= "## Scent Notes\n{$item['scent_notes']}\n\n";
            }

            if ($item['description']) {
                $md .= "## Description\n{$item['description']}\n\n";
            }

            if ($item['notes']) {
                $md .= "## Notes\n{$item['notes']}\n\n";
            }

            // Get URLs
            $urls = Database::fetchAll(
                "SELECT * FROM other_item_urls WHERE item_id = ?",
                [$item['id']]
            );
            if (!empty($urls)) {
                $md .= "## Related URLs\n";
                foreach ($urls as $url) {
                    $md .= "- [{$url['url']}]({$url['url']})";
                    if ($url['description']) {
                        $md .= " - {$url['description']}";
                    }
                    $md .= "\n";
                }
                $md .= "\n";
            }

            $md .= "---\n*Created: {$item['created_at']}*\n";

            file_put_contents("{$dir}/{$slug}.md", $md);

            // Copy images
            $this->copyOtherItemImages($userId, $item, $dir . '/images', $slug);
        }
    }

    /**
     * Copy item images to export directory
     */
    private function copyItemImages(int $userId, string $type, array $item, string $destDir, string $slug): void
    {
        $uploadDir = config('UPLOAD_PATH') . "/users/{$userId}/{$type}";

        // Copy hero image
        if ($item['hero_image'] && file_exists("{$uploadDir}/{$item['hero_image']}")) {
            $ext = pathinfo($item['hero_image'], PATHINFO_EXTENSION);
            copy("{$uploadDir}/{$item['hero_image']}", "{$destDir}/{$slug}.{$ext}");
        }

        // Copy additional images
        $imageTable = match ($type) {
            'razors' => 'razor_images',
            'blades' => 'blade_images',
            'brushes' => 'brush_images',
            default => null,
        };

        $idColumn = match ($type) {
            'razors' => 'razor_id',
            'blades' => 'blade_id',
            'brushes' => 'brush_id',
            default => null,
        };

        if ($imageTable && $idColumn) {
            $images = Database::fetchAll(
                "SELECT filename FROM {$imageTable} WHERE {$idColumn} = ?",
                [$item['id']]
            );

            $i = 1;
            foreach ($images as $image) {
                if (file_exists("{$uploadDir}/{$image['filename']}")) {
                    $ext = pathinfo($image['filename'], PATHINFO_EXTENSION);
                    copy("{$uploadDir}/{$image['filename']}", "{$destDir}/{$slug}-{$i}.{$ext}");
                    $i++;
                }
            }
        }
    }

    /**
     * Copy other item images to export directory
     */
    private function copyOtherItemImages(int $userId, array $item, string $destDir, string $slug): void
    {
        $uploadDir = config('UPLOAD_PATH') . "/users/{$userId}/other";

        // Copy hero image
        if ($item['hero_image'] && file_exists("{$uploadDir}/{$item['hero_image']}")) {
            $ext = pathinfo($item['hero_image'], PATHINFO_EXTENSION);
            copy("{$uploadDir}/{$item['hero_image']}", "{$destDir}/{$slug}.{$ext}");
        }

        // Copy additional images
        $images = Database::fetchAll(
            "SELECT filename FROM other_item_images WHERE item_id = ?",
            [$item['id']]
        );

        $i = 1;
        foreach ($images as $image) {
            if (file_exists("{$uploadDir}/{$image['filename']}")) {
                $ext = pathinfo($image['filename'], PATHINFO_EXTENSION);
                copy("{$uploadDir}/{$image['filename']}", "{$destDir}/{$slug}-{$i}.{$ext}");
                $i++;
            }
        }
    }

    /**
     * Create ZIP file from directory
     */
    private function createZip(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Could not create ZIP file');
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    }

    /**
     * Recursively delete directory
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Import items from CSV
     */
    public function importCsv(): void
    {
        error_log('[CSV Import] importCsv() called');

        if (!verify_csrf()) {
            error_log('[CSV Import] CSRF verification failed');
            flash('error', 'Invalid request.');
            redirect('/profile');
        }

        $userId = $_SESSION['user_id'];
        $type = $_POST['import_type'] ?? '';
        error_log("[CSV Import] Type: {$type}, User: {$userId}");

        if (!in_array($type, ['razors', 'blades', 'brushes'])) {
            error_log('[CSV Import] Invalid import type');
            flash('error', 'Invalid import type.');
            redirect('/profile');
        }

        if (empty($_FILES['csv_file']['tmp_name']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $errorCode = $_FILES['csv_file']['error'] ?? 'no file';
            error_log("[CSV Import] File upload error: {$errorCode}");
            flash('error', 'Please select a CSV file to upload.');
            redirect('/profile');
        }

        $file = $_FILES['csv_file'];

        // Validate file type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])) {
            flash('error', 'Please upload a valid CSV file.');
            redirect('/profile');
        }

        // Parse CSV
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            flash('error', 'Could not read the CSV file.');
            redirect('/profile');
        }

        // Read header row
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            flash('error', 'CSV file is empty or invalid.');
            redirect('/profile');
        }

        // Normalize header names (lowercase, trim)
        $header = array_map(function($h) {
            return strtolower(trim($h));
        }, $header);

        // Remove BOM if present
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }

        // Import based on type
        $imported = 0;
        $skipped = 0;
        $skipReasons = [];
        $rowNum = 1; // Header is row 1
        $totalRows = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            $totalRows++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Create associative array from row
            $data = [];
            foreach ($header as $index => $column) {
                $data[$column] = isset($row[$index]) ? trim($row[$index]) : '';
            }

            try {
                $result = match ($type) {
                    'razors' => $this->importRazorRow($userId, $data),
                    'blades' => $this->importBladeRow($userId, $data),
                    'brushes' => $this->importBrushRow($userId, $data),
                };

                if ($result === true) {
                    $imported++;
                } else {
                    $skipped++;
                    $skipReasons[] = $result; // Now returns reason string
                }
            } catch (\Throwable $e) {
                $skipReasons[] = "Row {$rowNum}: " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($handle);

        $message = "Imported {$imported} {$type}.";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} rows.";
            if (!empty($skipReasons)) {
                // Show first 5 reasons
                $reasons = array_slice($skipReasons, 0, 5);
                $message .= " Reasons: " . implode('; ', $reasons);
                if (count($skipReasons) > 5) {
                    $message .= " (and " . (count($skipReasons) - 5) . " more)";
                }
            }
        }

        // Debug: if nothing happened, show what headers were found
        if ($imported === 0 && $skipped === 0) {
            $message .= " No data rows found (read {$totalRows} rows). Headers detected: [" . implode(', ', $header) . "].";
        } elseif ($imported === 0 && $totalRows > 0) {
            $message .= " (processed {$totalRows} data rows)";
        }

        error_log("[CSV Import] Result: {$message}");
        $flashType = $imported > 0 ? 'success' : 'error';
        flash($flashType, $message);
        redirect('/profile');
    }

    /**
     * Import a single razor row
     * @return bool|string True on success, or error message string on failure
     */
    private function importRazorRow(int $userId, array $data): bool|string
    {
        // Get name from 'name' column, or combine 'brand' and 'name'
        $name = '';
        if (!empty($data['name'])) {
            // If brand exists and name doesn't already contain it, prepend brand
            if (!empty($data['brand']) && stripos($data['name'], $data['brand']) === false) {
                $name = trim($data['brand'] . ' ' . $data['name']);
            } else {
                $name = trim($data['name']);
            }
        } elseif (!empty($data['brand'])) {
            $name = trim($data['brand']);
        }

        if (empty($name)) {
            return "Missing name";
        }

        // Check for duplicate
        $existing = Database::fetch(
            "SELECT id FROM razors WHERE user_id = ? AND name = ? AND deleted_at IS NULL",
            [$userId, $name]
        );

        if ($existing) {
            return "Duplicate: '{$name}'";
        }

        $description = $data['description'] ?? null;
        $notes = $data['notes'] ?? null;

        // Handle use_count if present (store in notes if not empty)
        $useCount = intval($data['usecount'] ?? $data['use_count'] ?? 0);
        if ($useCount > 0 && empty($notes)) {
            $notes = "Use count: {$useCount}";
        } elseif ($useCount > 0 && !empty($notes)) {
            $notes .= "\nUse count: {$useCount}";
        }

        Database::query(
            "INSERT INTO razors (user_id, name, description, notes) VALUES (?, ?, ?, ?)",
            [$userId, $name, $description ?: null, $notes ?: null]
        );

        return true;
    }

    /**
     * Import a single blade row
     * @return bool|string True on success, or error message string on failure
     */
    private function importBladeRow(int $userId, array $data): bool|string
    {
        $name = trim($data['name'] ?? '');
        $brand = trim($data['brand'] ?? '');

        if (empty($name)) {
            return "Missing name (brand='{$brand}')";
        }

        // Check for duplicate
        $existing = Database::fetch(
            "SELECT id FROM blades WHERE user_id = ? AND name = ? AND deleted_at IS NULL",
            [$userId, $name]
        );

        if ($existing) {
            return "Duplicate: '{$name}'";
        }

        $description = $data['description'] ?? null;
        $notes = $data['notes'] ?? null;

        Database::query(
            "INSERT INTO blades (user_id, name, brand, description, notes) VALUES (?, ?, ?, ?, ?)",
            [$userId, $name, $brand ?: null, $description ?: null, $notes ?: null]
        );

        return true;
    }

    /**
     * Import a single brush row
     * @return bool|string True on success, or error message string on failure
     */
    private function importBrushRow(int $userId, array $data): bool|string
    {
        // Get name from 'name' column, or combine 'brand' and 'name'
        $name = '';
        if (!empty($data['name'])) {
            if (!empty($data['brand']) && stripos($data['name'], $data['brand']) === false) {
                $name = trim($data['brand'] . ' ' . $data['name']);
            } else {
                $name = trim($data['name']);
            }
        } elseif (!empty($data['brand'])) {
            $name = trim($data['brand']);
        }

        if (empty($name)) {
            return "Missing name";
        }

        // Check for duplicate
        $existing = Database::fetch(
            "SELECT id FROM brushes WHERE user_id = ? AND name = ? AND deleted_at IS NULL",
            [$userId, $name]
        );

        if ($existing) {
            return "Duplicate: '{$name}'";
        }

        $brand = $data['brand'] ?? null;
        $description = $data['description'] ?? null;
        $notes = $data['notes'] ?? null;
        $bristleType = $data['bristle_type'] ?? $data['bristletype'] ?? null;
        $knotSize = $data['knot_size'] ?? $data['knotsize'] ?? null;
        $loft = $data['loft'] ?? null;
        $handleMaterial = $data['handle_material'] ?? $data['handlematerial'] ?? null;

        // Handle use_count if present
        $useCount = intval($data['usecount'] ?? $data['use_count'] ?? 0);

        Database::query(
            "INSERT INTO brushes (user_id, name, brand, bristle_type, knot_size, loft, handle_material, description, notes, use_count)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$userId, $name, $brand ?: null, $bristleType ?: null, $knotSize ?: null, $loft ?: null, $handleMaterial ?: null, $description ?: null, $notes ?: null, $useCount]
        );

        return true;
    }

    /**
     * Show account deletion confirmation page
     */
    public function showDelete(): string
    {
        $userId = $_SESSION['user_id'];

        $user = Database::fetch(
            "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL",
            [$userId]
        );

        if (!$user) {
            redirect('/logout');
        }

        // Get collection stats for warning
        $stats = [
            'razors' => Database::fetch("SELECT COUNT(*) as count FROM razors WHERE user_id = ? AND deleted_at IS NULL", [$userId])['count'],
            'blades' => Database::fetch("SELECT COUNT(*) as count FROM blades WHERE user_id = ? AND deleted_at IS NULL", [$userId])['count'],
            'brushes' => Database::fetch("SELECT COUNT(*) as count FROM brushes WHERE user_id = ? AND deleted_at IS NULL", [$userId])['count'],
            'other' => Database::fetch("SELECT COUNT(*) as count FROM other_items WHERE user_id = ? AND deleted_at IS NULL", [$userId])['count'],
        ];

        return view('profile/delete', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    /**
     * Request account deletion
     */
    public function requestDeletion(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/profile/delete');
        }

        $userId = $_SESSION['user_id'];

        $user = Database::fetch(
            "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL",
            [$userId]
        );

        if (!$user) {
            redirect('/logout');
        }

        // Verify confirmation text
        $confirmText = trim($_POST['confirm_text'] ?? '');
        if ($confirmText !== 'DELETE MY ACCOUNT') {
            flash('error', 'Please type "DELETE MY ACCOUNT" exactly to confirm.');
            redirect('/profile/delete');
        }

        // Set deletion schedule (30 days from now)
        $deletionDays = config('DELETION_RECOVERY_DAYS', 30);
        Database::query(
            "UPDATE users SET deletion_requested_at = CURRENT_TIMESTAMP, deletion_scheduled_at = datetime('now', '+' || ? || ' days') WHERE id = ?",
            [$deletionDays, $userId]
        );

        // Log the deletion request
        if (class_exists('ActivityLogger')) {
            ActivityLogger::log('account_deletion_requested', 'user', $userId, [
                'scheduled_for' => date('Y-m-d H:i:s', time() + ($deletionDays * 86400)),
            ]);
        }

        // Send confirmation email
        $this->sendDeletionConfirmationEmail($user, $deletionDays);

        // Log the user out
        session_destroy();
        session_start();

        flash('success', "Your account has been scheduled for deletion. You have {$deletionDays} days to recover it by logging back in.");
        redirect('/login');
    }

    /**
     * Cancel account deletion (called when user logs back in during recovery window)
     */
    public function cancelDeletion(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/profile');
        }

        $userId = $_SESSION['user_id'];

        Database::query(
            "UPDATE users SET deletion_requested_at = NULL, deletion_scheduled_at = NULL WHERE id = ?",
            [$userId]
        );

        // Log the cancellation
        if (class_exists('ActivityLogger')) {
            ActivityLogger::log('account_deletion_cancelled', 'user', $userId);
        }

        flash('success', 'Account deletion has been cancelled. Your account is safe.');
        redirect('/profile');
    }

    /**
     * Send deletion confirmation email
     */
    private function sendDeletionConfirmationEmail(array $user, int $days): void
    {
        if (!class_exists('Mailer')) {
            return;
        }

        $scheduledDate = date('F j, Y', time() + ($days * 86400));

        $subject = "Razor Library - Account Deletion Scheduled";
        $body = "
            <h2>Account Deletion Request</h2>
            <p>Hello {$user['username']},</p>
            <p>We've received your request to delete your Razor Library account.</p>

            <h3>What happens next?</h3>
            <ul>
                <li>Your account and all associated data will be permanently deleted on <strong>{$scheduledDate}</strong></li>
                <li>This includes all your razors, blades, brushes, other items, and images</li>
                <li>This action cannot be undone after the scheduled date</li>
            </ul>

            <h3>Changed your mind?</h3>
            <p>If you want to keep your account, simply log back in before {$scheduledDate} and click \"Cancel Deletion\" on the banner that appears.</p>

            <h3>Need your data?</h3>
            <p>If you haven't already, you can still log in and download a backup of your collection before the deletion date.</p>

            <p>If you did not request this deletion, please contact the site administrator immediately.</p>
        ";

        Mailer::send($user['email'], $subject, $body);
    }

    /**
     * Download sample CSV template
     */
    public function downloadTemplate(): void
    {
        $type = $_GET['type'] ?? 'razors';

        $templates = [
            'razors' => [
                'header' => ['Brand', 'Name', 'UseCount', 'Notes'],
                'sample' => [
                    ['Gillette', 'Slim 1963 L1', '26', 'Great mild razor'],
                    ['Merkur', '34C', '6', 'HD handle'],
                ],
            ],
            'blades' => [
                'header' => ['Brand', 'Name', 'Notes'],
                'sample' => [
                    ['Feather', 'Hi-Stainless', 'Very sharp'],
                    ['Astra', 'Superior Platinum', 'Good value'],
                ],
            ],
            'brushes' => [
                'header' => ['Brand', 'Name', 'BristleType', 'KnotSize', 'Loft', 'HandleMaterial', 'UseCount', 'Notes'],
                'sample' => [
                    ['Simpson', 'Chubby 2', 'Badger - Best', '27mm', '54mm', 'Resin', '15', 'Excellent brush'],
                    ['Yaqi', 'Tuxedo', 'Synthetic', '24mm', '50mm', 'Resin', '30', 'Great for travel'],
                ],
            ],
        ];

        if (!isset($templates[$type])) {
            $type = 'razors';
        }

        $template = $templates[$type];

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $type . '_template.csv"');

        $output = fopen('php://output', 'w');

        // Write header
        fputcsv($output, $template['header']);

        // Write sample rows
        foreach ($template['sample'] as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}
