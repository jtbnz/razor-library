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
        $heroImage = null;

        // Handle hero image upload
        if (!empty($_FILES['hero_image']['tmp_name'])) {
            $uploadDir = "users/{$userId}/brushes";
            $result = ImageHandler::upload($_FILES['hero_image'], $uploadDir);
            if ($result['success']) {
                $heroImage = $result['filename'];
            } else {
                flash('error', $result['error']);
                set_old($_POST);
                redirect('/brushes/new');
            }
        }

        Database::query(
            "INSERT INTO brushes (user_id, name, brand, bristle_type, knot_size, loft, handle_material, description, notes, hero_image)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$userId, $name, $brand ?: null, $bristleType ?: null, $knotSize ?: null, $loft ?: null, $handleMaterial ?: null, $description ?: null, $notes ?: null, $heroImage]
        );

        $brushId = Database::lastInsertId();
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

        $heroImage = $brush['hero_image'];

        // Handle hero image upload
        if (!empty($_FILES['hero_image']['tmp_name'])) {
            $uploadDir = "users/{$userId}/brushes";
            $result = ImageHandler::upload($_FILES['hero_image'], $uploadDir);
            if ($result['success']) {
                // Delete old image
                if ($heroImage) {
                    ImageHandler::delete($uploadDir, $heroImage);
                }
                $heroImage = $result['filename'];
            } else {
                flash('error', $result['error']);
                redirect("/brushes/{$id}/edit");
            }
        }

        Database::query(
            "UPDATE brushes SET name = ?, brand = ?, bristle_type = ?, knot_size = ?, loft = ?, handle_material = ?, description = ?, notes = ?, hero_image = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$name, $brand ?: null, $bristleType ?: null, $knotSize ?: null, $loft ?: null, $handleMaterial ?: null, $description ?: null, $notes ?: null, $heroImage, $id]
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
            "UPDATE brushes SET use_count = use_count + 1 WHERE id = ?",
            [$id]
        );

        flash('success', 'Usage recorded.');
        redirect("/brushes/{$id}");
    }

    /**
     * Upload additional image
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

        if (empty($_FILES['image']['tmp_name'])) {
            flash('error', 'Please select an image.');
            redirect("/brushes/{$id}");
        }

        $uploadDir = "users/{$userId}/brushes";
        $result = ImageHandler::upload($_FILES['image'], $uploadDir);

        if (!$result['success']) {
            flash('error', $result['error']);
            redirect("/brushes/{$id}");
        }

        Database::query(
            "INSERT INTO brush_images (brush_id, filename) VALUES (?, ?)",
            [$id, $result['filename']]
        );

        flash('success', 'Image uploaded successfully.');
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
