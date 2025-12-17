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

        // Validate email if provided
        if (!empty($email) && !is_valid_email($email)) {
            flash('error', 'Please enter a valid email address.');
            redirect('/profile');
        }

        // Check if email is taken by another user
        if (!empty($email)) {
            $existingEmail = Database::fetch(
                "SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL",
                [$email, $userId]
            );

            if ($existingEmail) {
                flash('error', 'Email is already in use.');
                redirect('/profile');
            }
        }

        // Handle password change
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                flash('error', 'Current password is required to change password.');
                redirect('/profile');
            }

            if (!password_verify($currentPassword, $user['password'])) {
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
                "UPDATE users SET password = ? WHERE id = ?",
                [$hashedPassword, $userId]
            );
        }

        // Update user
        Database::query(
            "UPDATE users SET username = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$username, $email ?: null, $userId]
        );

        flash('success', 'Profile updated successfully.');
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
}
