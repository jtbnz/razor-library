<?php
/**
 * Brush Controller
 */

class BrushController
{
    /**
     * Display all brushes for current user
     */
    public function index(): string
    {
        $userId = $_SESSION['user_id'];
        $sort = $_GET['sort'] ?? 'name';

        $orderBy = match ($sort) {
            'date' => 'created_at DESC',
            'usage' => 'use_count DESC',
            'last_used' => 'last_used_at DESC NULLS LAST',
            default => 'name ASC',
        };

        $brushes = Database::fetchAll(
            "SELECT * FROM brushes
             WHERE user_id = ? AND deleted_at IS NULL
             ORDER BY {$orderBy}",
            [$userId]
        );

        return view('brushes/index', [
            'brushes' => $brushes,
            'sort' => $sort,
        ]);
    }

    /**
     * Show create brush form
     */
    public function create(): string
    {
        return view('brushes/create');
    }

    /**
     * Store a new brush
     */
    public function store(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/brushes/new');
        }

        $name = trim($_POST['name'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $bristleType = trim($_POST['bristle_type'] ?? '');
        $knotSize = trim($_POST['knot_size'] ?? '');
        $loft = trim($_POST['loft'] ?? '');
        $handleMaterial = trim($_POST['handle_material'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name)) {
            flash('error', 'Name is required.');
            set_old($_POST);
            redirect('/brushes/new');
        }

        $userId = $_SESSION['user_id'];

        // Create brush first
        Database::query(
            "INSERT INTO brushes (user_id, name, brand, bristle_type, knot_size, loft, handle_material, description, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$userId, $name, $brand ?: null, $bristleType ?: null, $knotSize ?: null, $loft ?: null, $handleMaterial ?: null, $description ?: null, $notes ?: null]
        );

        $brushId = Database::lastInsertId();

        // Handle multiple image uploads
        $files = $_FILES['images'] ?? $_FILES['hero_image'] ?? null;
        if ($files && !empty($files['name'][0] ?? $files['name'])) {
            // Normalize to array format for multiple files
            if (!is_array($files['name'])) {
                $files = [
                    'name' => [$files['name']],
                    'type' => [$files['type']],
                    'tmp_name' => [$files['tmp_name']],
                    'error' => [$files['error']],
                    'size' => [$files['size']],
                ];
            }

            $heroImage = null;
            for ($i = 0; $i < count($files['name']); $i++) {
                if (empty($files['name'][$i]) || $files['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];

                $result = ImageHandler::processUpload($file, "users/{$userId}/brushes");

                if ($result) {
                    Database::query(
                        "INSERT INTO brush_images (brush_id, filename) VALUES (?, ?)",
                        [$brushId, $result['filename']]
                    );

                    // First image becomes the hero image
                    if ($heroImage === null) {
                        $heroImage = $result['filename'];
                    }
                }
            }

            // Update brush with hero image
            if ($heroImage) {
                Database::query(
                    "UPDATE brushes SET hero_image = ? WHERE id = ?",
                    [$heroImage, $brushId]
                );
            }
        }

        clear_old();
        flash('success', 'Brush added successfully.');
        redirect("/brushes/{$brushId}");
    }

    /**
     * Show brush details
     */
    public function show(int $id): string
    {
        $userId = $_SESSION['user_id'];

        $brush = Database::fetch(
            "SELECT * FROM brushes WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$brush) {
            http_response_code(404);
            return view('errors/404');
        }

        // Get additional images
        $images = Database::fetchAll(
            "SELECT * FROM brush_images WHERE brush_id = ? ORDER BY created_at DESC",
            [$id]
        );

        // Get URLs
        $urls = Database::fetchAll(
            "SELECT * FROM brush_urls WHERE brush_id = ? ORDER BY created_at DESC",
            [$id]
        );

        return view('brushes/show', [
            'brush' => $brush,
            'images' => $images,
            'urls' => $urls,
        ]);
    }

    /**
     * Show edit brush form
     */
    public function edit(int $id): string
    {
        $userId = $_SESSION['user_id'];

        $brush = Database::fetch(
            "SELECT * FROM brushes WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$brush) {
            http_response_code(404);
            return view('errors/404');
        }

        return view('brushes/edit', [
            'brush' => $brush,
        ]);
    }

    /**
     * Update a brush
     */
    public function update(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/brushes/{$id}/edit");
        }

        $userId = $_SESSION['user_id'];

        $brush = Database::fetch(
            "SELECT * FROM brushes WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$brush) {
            http_response_code(404);
            echo view('errors/404');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $bristleType = trim($_POST['bristle_type'] ?? '');
        $knotSize = trim($_POST['knot_size'] ?? '');
        $loft = trim($_POST['loft'] ?? '');
        $handleMaterial = trim($_POST['handle_material'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name)) {
            flash('error', 'Name is required.');
            redirect("/brushes/{$id}/edit");
        }

        Database::query(
            "UPDATE brushes SET name = ?, brand = ?, bristle_type = ?, knot_size = ?, loft = ?, handle_material = ?, description = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$name, $brand ?: null, $bristleType ?: null, $knotSize ?: null, $loft ?: null, $handleMaterial ?: null, $description ?: null, $notes ?: null, $id]
        );

        flash('success', 'Brush updated successfully.');
        redirect("/brushes/{$id}");
    }

    /**
     * Delete a brush (soft delete)
     */
    public function delete(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/brushes');
        }

        $userId = $_SESSION['user_id'];

        $brush = Database::fetch(
            "SELECT * FROM brushes WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$brush) {
            flash('error', 'Brush not found.');
            redirect('/brushes');
        }

        Database::query(
            "UPDATE brushes SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$id]
        );

        flash('success', 'Brush deleted successfully.');
        redirect('/brushes');
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/brushes/{$id}");
        }

        $userId = $_SESSION['user_id'];

        $brush = Database::fetch(
            "SELECT * FROM brushes WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$brush) {
            flash('error', 'Brush not found.');
            redirect('/brushes');
        }

        Database::query(
            "UPDATE brushes SET use_count = use_count + 1, last_used_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$id]
        );

        flash('success', 'Usage recorded.');
        redirect("/brushes/{$id}");
    }

    /**
     * Upload additional image(s) - supports multiple file upload
     */
    public function uploadImage(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/brushes/{$id}");
        }

        $userId = $_SESSION['user_id'];

        $brush = Database::fetch(
            "SELECT * FROM brushes WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$brush) {
            flash('error', 'Brush not found.');
            redirect('/brushes');
        }

        // Handle multiple file uploads
        $files = $_FILES['images'] ?? $_FILES['image'] ?? null;
        if (!$files || empty($files['name'][0] ?? $files['name'])) {
            flash('error', 'No images selected.');
            redirect("/brushes/{$id}");
            return;
        }

        // Normalize to array format for multiple files
        if (!is_array($files['name'])) {
            $files = [
                'name' => [$files['name']],
                'type' => [$files['type']],
                'tmp_name' => [$files['tmp_name']],
                'error' => [$files['error']],
                'size' => [$files['size']],
            ];
        }

        $uploadDir = "users/{$userId}/brushes";
        $uploaded = 0;
        $errors = [];

        for ($i = 0; $i < count($files['name']); $i++) {
            if (empty($files['tmp_name'][$i]) || $files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $singleFile = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];

            $result = ImageHandler::upload($singleFile, $uploadDir);

            if ($result['success']) {
                Database::query(
                    "INSERT INTO brush_images (brush_id, filename) VALUES (?, ?)",
                    [$id, $result['filename']]
                );

                // If no hero image set yet, use the first uploaded image
                if (!$brush['hero_image']) {
                    Database::query(
                        "UPDATE brushes SET hero_image = ? WHERE id = ?",
                        [$result['filename'], $brush['id']]
                    );
                    $brush['hero_image'] = $result['filename'];
                }

                $uploaded++;
            } else {
                $errors[] = $files['name'][$i] . ': ' . $result['error'];
            }
        }

        if ($uploaded > 0) {
            $msg = $uploaded === 1 ? 'Image uploaded successfully.' : "{$uploaded} images uploaded successfully.";
            flash('success', $msg);
        }
        if (!empty($errors)) {
            flash('error', 'Some uploads failed: ' . implode(', ', $errors));
        }

        redirect("/brushes/{$id}");
    }

    /**
     * Set an image as the hero/tile image
     */
    public function setHeroImage(int $id, int $imageId): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/brushes/{$id}");
        }

        $userId = $_SESSION['user_id'];

        $brush = Database::fetch(
            "SELECT * FROM brushes WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$brush) {
            flash('error', 'Brush not found.');
            redirect('/brushes');
            return;
        }

        $image = Database::fetch(
            "SELECT * FROM brush_images WHERE id = ? AND brush_id = ?",
            [$imageId, $brush['id']]
        );

        if ($image) {
            Database::query(
                "UPDATE brushes SET hero_image = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$image['filename'], $brush['id']]
            );
            flash('success', 'Hero image updated.');
        } else {
            flash('error', 'Image not found.');
        }

        redirect("/brushes/{$id}");
    }

    /**
     * Delete additional image
     */
    public function deleteImage(int $brushId, int $imageId): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/brushes/{$brushId}");
        }

        $userId = $_SESSION['user_id'];

        $brush = Database::fetch(
            "SELECT * FROM brushes WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$brushId, $userId]
        );

        if (!$brush) {
            flash('error', 'Brush not found.');
            redirect('/brushes');
        }

        $image = Database::fetch(
            "SELECT * FROM brush_images WHERE id = ? AND brush_id = ?",
            [$imageId, $brushId]
        );

        if (!$image) {
            flash('error', 'Image not found.');
            redirect("/brushes/{$brushId}");
        }

        // Delete file
        $uploadDir = "users/{$userId}/brushes";
        ImageHandler::delete($uploadDir, $image['filename']);

        // Delete record
        Database::query("DELETE FROM brush_images WHERE id = ?", [$imageId]);

        flash('success', 'Image deleted successfully.');
        redirect("/brushes/{$brushId}");
    }

    /**
     * Add URL to brush
     */
    public function addUrl(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/brushes/{$id}");
        }

        $userId = $_SESSION['user_id'];

        $brush = Database::fetch(
            "SELECT * FROM brushes WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$brush) {
            flash('error', 'Brush not found.');
            redirect('/brushes');
        }

        $url = trim($_POST['url'] ?? '');
        $description = trim($_POST['url_description'] ?? '');

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            flash('error', 'Please enter a valid URL.');
            redirect("/brushes/{$id}");
        }

        Database::query(
            "INSERT INTO brush_urls (brush_id, url, description) VALUES (?, ?, ?)",
            [$id, $url, $description ?: null]
        );

        flash('success', 'URL added successfully.');
        redirect("/brushes/{$id}");
    }

    /**
     * Delete URL from brush
     */
    public function deleteUrl(int $brushId, int $urlId): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/brushes/{$brushId}");
        }

        $userId = $_SESSION['user_id'];

        $brush = Database::fetch(
            "SELECT * FROM brushes WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$brushId, $userId]
        );

        if (!$brush) {
            flash('error', 'Brush not found.');
            redirect('/brushes');
        }

        Database::query(
            "DELETE FROM brush_urls WHERE id = ? AND brush_id = ?",
            [$urlId, $brushId]
        );

        flash('success', 'URL deleted successfully.');
        redirect("/brushes/{$brushId}");
    }
}
