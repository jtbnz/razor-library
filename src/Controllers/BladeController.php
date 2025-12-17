<?php
/**
 * Blade Controller
 */

class BladeController
{
    /**
     * Display all blades for current user
     */
    public function index(): string
    {
        $userId = $_SESSION['user_id'];
        $sort = $_GET['sort'] ?? 'name';

        $orderBy = match ($sort) {
            'date' => 'created_at DESC',
            'usage' => '(SELECT COALESCE(SUM(bu.count), 0) FROM blade_usage bu WHERE bu.blade_id = blades.id) DESC',
            default => 'name ASC',
        };

        $blades = Database::fetchAll(
            "SELECT blades.*,
                    (SELECT COALESCE(SUM(bu.count), 0) FROM blade_usage bu WHERE bu.blade_id = blades.id) as total_usage
             FROM blades
             WHERE user_id = ? AND deleted_at IS NULL
             ORDER BY {$orderBy}",
            [$userId]
        );

        return view('blades/index', [
            'blades' => $blades,
            'sort' => $sort,
        ]);
    }

    /**
     * Show create blade form
     */
    public function create(): string
    {
        return view('blades/create');
    }

    /**
     * Store a new blade
     */
    public function store(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/blades/new');
        }

        $name = trim($_POST['name'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name)) {
            flash('error', 'Name is required.');
            set_old($_POST);
            redirect('/blades/new');
        }

        $userId = $_SESSION['user_id'];
        $heroImage = null;

        // Handle hero image upload
        if (!empty($_FILES['hero_image']['tmp_name'])) {
            $uploadDir = "users/{$userId}/blades";
            $result = ImageHandler::upload($_FILES['hero_image'], $uploadDir);
            if ($result['success']) {
                $heroImage = $result['filename'];
            } else {
                flash('error', $result['error']);
                set_old($_POST);
                redirect('/blades/new');
            }
        }

        Database::query(
            "INSERT INTO blades (user_id, name, brand, description, notes, hero_image) VALUES (?, ?, ?, ?, ?, ?)",
            [$userId, $name, $brand ?: null, $description ?: null, $notes ?: null, $heroImage]
        );

        $bladeId = Database::lastInsertId();
        clear_old();
        flash('success', 'Blade added successfully.');
        redirect("/blades/{$bladeId}");
    }

    /**
     * Show blade details
     */
    public function show(int $id): string
    {
        $userId = $_SESSION['user_id'];

        $blade = Database::fetch(
            "SELECT * FROM blades WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$blade) {
            http_response_code(404);
            return view('errors/404');
        }

        // Get additional images
        $images = Database::fetchAll(
            "SELECT * FROM blade_images WHERE blade_id = ? ORDER BY created_at DESC",
            [$id]
        );

        // Get URLs
        $urls = Database::fetchAll(
            "SELECT * FROM blade_urls WHERE blade_id = ? ORDER BY created_at DESC",
            [$id]
        );

        // Get usage by razor
        $usage = Database::fetchAll(
            "SELECT bu.*, r.name as razor_name
             FROM blade_usage bu
             JOIN razors r ON bu.razor_id = r.id
             WHERE bu.blade_id = ?
             ORDER BY r.name",
            [$id]
        );

        // Calculate total usage
        $totalUsage = array_sum(array_column($usage, 'count'));

        return view('blades/show', [
            'blade' => $blade,
            'images' => $images,
            'urls' => $urls,
            'usage' => $usage,
            'totalUsage' => $totalUsage,
        ]);
    }

    /**
     * Show edit blade form
     */
    public function edit(int $id): string
    {
        $userId = $_SESSION['user_id'];

        $blade = Database::fetch(
            "SELECT * FROM blades WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$blade) {
            http_response_code(404);
            return view('errors/404');
        }

        return view('blades/edit', [
            'blade' => $blade,
        ]);
    }

    /**
     * Update a blade
     */
    public function update(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/blades/{$id}/edit");
        }

        $userId = $_SESSION['user_id'];

        $blade = Database::fetch(
            "SELECT * FROM blades WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$blade) {
            http_response_code(404);
            echo view('errors/404');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name)) {
            flash('error', 'Name is required.');
            redirect("/blades/{$id}/edit");
        }

        $heroImage = $blade['hero_image'];

        // Handle hero image upload
        if (!empty($_FILES['hero_image']['tmp_name'])) {
            $uploadDir = "users/{$userId}/blades";
            $result = ImageHandler::upload($_FILES['hero_image'], $uploadDir);
            if ($result['success']) {
                // Delete old image
                if ($heroImage) {
                    ImageHandler::delete($uploadDir, $heroImage);
                }
                $heroImage = $result['filename'];
            } else {
                flash('error', $result['error']);
                redirect("/blades/{$id}/edit");
            }
        }

        Database::query(
            "UPDATE blades SET name = ?, brand = ?, description = ?, notes = ?, hero_image = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$name, $brand ?: null, $description ?: null, $notes ?: null, $heroImage, $id]
        );

        flash('success', 'Blade updated successfully.');
        redirect("/blades/{$id}");
    }

    /**
     * Delete a blade (soft delete)
     */
    public function delete(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/blades');
        }

        $userId = $_SESSION['user_id'];

        $blade = Database::fetch(
            "SELECT * FROM blades WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$blade) {
            flash('error', 'Blade not found.');
            redirect('/blades');
        }

        Database::query(
            "UPDATE blades SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$id]
        );

        flash('success', 'Blade deleted successfully.');
        redirect('/blades');
    }

    /**
     * Upload additional image
     */
    public function uploadImage(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/blades/{$id}");
        }

        $userId = $_SESSION['user_id'];

        $blade = Database::fetch(
            "SELECT * FROM blades WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$blade) {
            flash('error', 'Blade not found.');
            redirect('/blades');
        }

        if (empty($_FILES['image']['tmp_name'])) {
            flash('error', 'Please select an image.');
            redirect("/blades/{$id}");
        }

        $uploadDir = "users/{$userId}/blades";
        $result = ImageHandler::upload($_FILES['image'], $uploadDir);

        if (!$result['success']) {
            flash('error', $result['error']);
            redirect("/blades/{$id}");
        }

        Database::query(
            "INSERT INTO blade_images (blade_id, filename) VALUES (?, ?)",
            [$id, $result['filename']]
        );

        flash('success', 'Image uploaded successfully.');
        redirect("/blades/{$id}");
    }

    /**
     * Delete additional image
     */
    public function deleteImage(int $bladeId, int $imageId): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/blades/{$bladeId}");
        }

        $userId = $_SESSION['user_id'];

        $blade = Database::fetch(
            "SELECT * FROM blades WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$bladeId, $userId]
        );

        if (!$blade) {
            flash('error', 'Blade not found.');
            redirect('/blades');
        }

        $image = Database::fetch(
            "SELECT * FROM blade_images WHERE id = ? AND blade_id = ?",
            [$imageId, $bladeId]
        );

        if (!$image) {
            flash('error', 'Image not found.');
            redirect("/blades/{$bladeId}");
        }

        // Delete file
        $uploadDir = "users/{$userId}/blades";
        ImageHandler::delete($uploadDir, $image['filename']);

        // Delete record
        Database::query("DELETE FROM blade_images WHERE id = ?", [$imageId]);

        flash('success', 'Image deleted successfully.');
        redirect("/blades/{$bladeId}");
    }

    /**
     * Add URL to blade
     */
    public function addUrl(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/blades/{$id}");
        }

        $userId = $_SESSION['user_id'];

        $blade = Database::fetch(
            "SELECT * FROM blades WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$blade) {
            flash('error', 'Blade not found.');
            redirect('/blades');
        }

        $url = trim($_POST['url'] ?? '');
        $description = trim($_POST['url_description'] ?? '');

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            flash('error', 'Please enter a valid URL.');
            redirect("/blades/{$id}");
        }

        Database::query(
            "INSERT INTO blade_urls (blade_id, url, description) VALUES (?, ?, ?)",
            [$id, $url, $description ?: null]
        );

        flash('success', 'URL added successfully.');
        redirect("/blades/{$id}");
    }

    /**
     * Delete URL from blade
     */
    public function deleteUrl(int $bladeId, int $urlId): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/blades/{$bladeId}");
        }

        $userId = $_SESSION['user_id'];

        $blade = Database::fetch(
            "SELECT * FROM blades WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$bladeId, $userId]
        );

        if (!$blade) {
            flash('error', 'Blade not found.');
            redirect('/blades');
        }

        Database::query(
            "DELETE FROM blade_urls WHERE id = ? AND blade_id = ?",
            [$urlId, $bladeId]
        );

        flash('success', 'URL deleted successfully.');
        redirect("/blades/{$bladeId}");
    }
}
