<?php
/**
 * Razor Controller
 */

class RazorController
{
    private int $userId;

    public function __construct()
    {
        $this->userId = $_SESSION['user_id'];
    }

    /**
     * List all razors
     */
    public function index(): void
    {
        $sort = $_GET['sort'] ?? 'name_asc';

        $orderBy = match ($sort) {
            'name_desc' => 'name DESC',
            'date_asc' => 'created_at ASC',
            'date_desc' => 'created_at DESC',
            'usage' => '(SELECT COALESCE(SUM(count), 0) FROM blade_usage WHERE razor_id = razors.id) DESC',
            default => 'name ASC',
        };

        $razors = Database::fetchAll(
            "SELECT r.*,
                    (SELECT COALESCE(SUM(count), 0) FROM blade_usage WHERE razor_id = r.id) as total_usage
             FROM razors r
             WHERE r.user_id = ? AND r.deleted_at IS NULL
             ORDER BY {$orderBy}",
            [$this->userId]
        );

        echo view('razors/index', [
            'title' => 'Razors - Razor Library',
            'razors' => $razors,
            'sort' => $sort,
        ]);
    }

    /**
     * Show create form
     */
    public function create(): void
    {
        echo view('razors/create', [
            'title' => 'Add Razor - Razor Library',
        ]);
    }

    /**
     * Store new razor
     */
    public function store(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request. Please try again.');
            redirect('/razors/new');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name)) {
            flash('error', 'Name is required.');
            set_old($_POST);
            redirect('/razors/new');
            return;
        }

        // Create razor first
        Database::query(
            "INSERT INTO razors (user_id, name, description, notes) VALUES (?, ?, ?, ?)",
            [$this->userId, $name, $description ?: null, $notes ?: null]
        );

        $razorId = Database::lastInsertId();

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

                $result = ImageHandler::processUpload($file, "users/{$this->userId}/razors");

                if ($result) {
                    Database::query(
                        "INSERT INTO razor_images (razor_id, filename) VALUES (?, ?)",
                        [$razorId, $result['filename']]
                    );

                    // First image becomes the hero image
                    if ($heroImage === null) {
                        $heroImage = $result['filename'];
                    }
                }
            }

            // Update razor with hero image
            if ($heroImage) {
                Database::query(
                    "UPDATE razors SET hero_image = ? WHERE id = ?",
                    [$heroImage, $razorId]
                );
            }
        }

        clear_old();
        flash('success', 'Razor added successfully.');
        redirect('/razors/' . $razorId);
    }

    /**
     * Show single razor
     */
    public function show(string $id): void
    {
        $razor = $this->getRazor($id);
        if (!$razor) {
            redirect('/razors');
            return;
        }

        // Get additional images
        $images = Database::fetchAll(
            "SELECT * FROM razor_images WHERE razor_id = ? ORDER BY created_at",
            [$razor['id']]
        );

        // Get related URLs
        $urls = Database::fetchAll(
            "SELECT * FROM razor_urls WHERE razor_id = ? ORDER BY created_at",
            [$razor['id']]
        );

        // Get blade usage
        $bladeUsage = Database::fetchAll(
            "SELECT bu.*, b.name as blade_name, b.hero_image as blade_image
             FROM blade_usage bu
             JOIN blades b ON b.id = bu.blade_id
             WHERE bu.razor_id = ? AND b.deleted_at IS NULL
             ORDER BY b.name",
            [$razor['id']]
        );

        // Get all user's blades for the usage dropdown
        $allBlades = Database::fetchAll(
            "SELECT id, name FROM blades WHERE user_id = ? AND deleted_at IS NULL ORDER BY name",
            [$this->userId]
        );

        echo view('razors/show', [
            'title' => $razor['name'] . ' - Razor Library',
            'razor' => $razor,
            'images' => $images,
            'urls' => $urls,
            'bladeUsage' => $bladeUsage,
            'allBlades' => $allBlades,
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(string $id): void
    {
        $razor = $this->getRazor($id);
        if (!$razor) {
            redirect('/razors');
            return;
        }

        echo view('razors/edit', [
            'title' => 'Edit ' . $razor['name'] . ' - Razor Library',
            'razor' => $razor,
        ]);
    }

    /**
     * Update razor
     */
    public function update(string $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request. Please try again.');
            redirect('/razors/' . $id . '/edit');
            return;
        }

        $razor = $this->getRazor($id);
        if (!$razor) {
            redirect('/razors');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name)) {
            flash('error', 'Name is required.');
            redirect('/razors/' . $id . '/edit');
            return;
        }

        // Handle hero image upload
        $heroImage = $razor['hero_image'];
        if (!empty($_FILES['hero_image']['name'])) {
            // Delete old image
            if ($heroImage) {
                ImageHandler::delete("users/{$this->userId}/razors/{$heroImage}");
            }

            $result = ImageHandler::processUpload(
                $_FILES['hero_image'],
                "users/{$this->userId}/razors"
            );
            if ($result) {
                $heroImage = $result['filename'];
            }
        }

        Database::query(
            "UPDATE razors SET name = ?, description = ?, notes = ?, hero_image = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$name, $description ?: null, $notes ?: null, $heroImage, $razor['id']]
        );

        flash('success', 'Razor updated successfully.');
        redirect('/razors/' . $id);
    }

    /**
     * Delete razor (soft delete)
     */
    public function delete(string $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/razors');
            return;
        }

        $razor = $this->getRazor($id);
        if (!$razor) {
            redirect('/razors');
            return;
        }

        Database::query(
            "UPDATE razors SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$razor['id']]
        );

        flash('success', 'Razor deleted.');
        redirect('/razors');
    }

    /**
     * Upload additional image(s) - supports multiple file upload
     */
    public function uploadImage(string $id): void
    {
        if (!verify_csrf()) {
            if (is_ajax()) {
                json_response(['error' => 'Invalid request'], 400);
            }
            flash('error', 'Invalid request.');
            redirect('/razors/' . $id);
            return;
        }

        $razor = $this->getRazor($id);
        if (!$razor) {
            if (is_ajax()) {
                json_response(['error' => 'Razor not found'], 404);
            }
            redirect('/razors');
            return;
        }

        // Handle multiple file uploads
        $files = $_FILES['images'] ?? $_FILES['image'] ?? null;
        if (!$files || empty($files['name'][0] ?? $files['name'])) {
            flash('error', 'No images selected.');
            redirect('/razors/' . $id);
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

        $uploaded = 0;
        $firstImageId = null;

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

            $result = ImageHandler::processUpload($file, "users/{$this->userId}/razors");

            if ($result) {
                Database::query(
                    "INSERT INTO razor_images (razor_id, filename) VALUES (?, ?)",
                    [$razor['id'], $result['filename']]
                );
                $uploaded++;

                if ($firstImageId === null) {
                    $firstImageId = Database::lastInsertId();
                }

                // If no hero image set yet, use the first uploaded image
                if (!$razor['hero_image']) {
                    Database::query(
                        "UPDATE razors SET hero_image = ? WHERE id = ?",
                        [$result['filename'], $razor['id']]
                    );
                    $razor['hero_image'] = $result['filename'];
                }
            }
        }

        if (is_ajax()) {
            json_response(['success' => true, 'uploaded' => $uploaded]);
            return;
        }

        if ($uploaded > 0) {
            flash('success', $uploaded . ' image(s) uploaded.');
        } else {
            flash('error', 'Failed to upload images.');
        }
        redirect('/razors/' . $id);
    }

    /**
     * Set an image as the hero/tile image
     */
    public function setHeroImage(string $id, string $imageId): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/razors/' . $id);
            return;
        }

        $razor = $this->getRazor($id);
        if (!$razor) {
            redirect('/razors');
            return;
        }

        $image = Database::fetch(
            "SELECT * FROM razor_images WHERE id = ? AND razor_id = ?",
            [$imageId, $razor['id']]
        );

        if ($image) {
            Database::query(
                "UPDATE razors SET hero_image = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$image['filename'], $razor['id']]
            );
            flash('success', 'Hero image updated.');
        }

        redirect('/razors/' . $id);
    }

    /**
     * Delete additional image
     */
    public function deleteImage(string $id, string $imageId): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/razors/' . $id);
            return;
        }

        $razor = $this->getRazor($id);
        if (!$razor) {
            redirect('/razors');
            return;
        }

        $image = Database::fetch(
            "SELECT * FROM razor_images WHERE id = ? AND razor_id = ?",
            [$imageId, $razor['id']]
        );

        if ($image) {
            ImageHandler::delete("users/{$this->userId}/razors/{$image['filename']}");
            Database::query("DELETE FROM razor_images WHERE id = ?", [$imageId]);
            flash('success', 'Image deleted.');
        }

        redirect('/razors/' . $id);
    }

    /**
     * Add URL
     */
    public function addUrl(string $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/razors/' . $id);
            return;
        }

        $razor = $this->getRazor($id);
        if (!$razor) {
            redirect('/razors');
            return;
        }

        $url = trim($_POST['url'] ?? '');
        $description = trim($_POST['url_description'] ?? '');

        if (empty($url)) {
            flash('error', 'URL is required.');
            redirect('/razors/' . $id);
            return;
        }

        // Add protocol if missing
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }

        Database::query(
            "INSERT INTO razor_urls (razor_id, url, description) VALUES (?, ?, ?)",
            [$razor['id'], $url, $description ?: null]
        );

        flash('success', 'URL added.');
        redirect('/razors/' . $id);
    }

    /**
     * Delete URL
     */
    public function deleteUrl(string $id, string $urlId): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/razors/' . $id);
            return;
        }

        $razor = $this->getRazor($id);
        if (!$razor) {
            redirect('/razors');
            return;
        }

        Database::query(
            "DELETE FROM razor_urls WHERE id = ? AND razor_id = ?",
            [$urlId, $razor['id']]
        );

        flash('success', 'URL deleted.');
        redirect('/razors/' . $id);
    }

    /**
     * Update blade usage
     */
    public function updateUsage(string $id): void
    {
        if (!verify_csrf()) {
            if (is_ajax()) {
                json_response(['error' => 'Invalid request'], 400);
            }
            flash('error', 'Invalid request.');
            redirect('/razors/' . $id);
            return;
        }

        $razor = $this->getRazor($id);
        if (!$razor) {
            if (is_ajax()) {
                json_response(['error' => 'Razor not found'], 404);
            }
            redirect('/razors');
            return;
        }

        $bladeId = (int) ($_POST['blade_id'] ?? 0);
        $count = max(0, (int) ($_POST['count'] ?? 0));

        // Verify blade belongs to user
        $blade = Database::fetch(
            "SELECT id FROM blades WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$bladeId, $this->userId]
        );

        if (!$blade) {
            if (is_ajax()) {
                json_response(['error' => 'Blade not found'], 404);
            }
            flash('error', 'Blade not found.');
            redirect('/razors/' . $id);
            return;
        }

        // Upsert blade usage
        $existing = Database::fetch(
            "SELECT id FROM blade_usage WHERE razor_id = ? AND blade_id = ?",
            [$razor['id'], $bladeId]
        );

        if ($existing) {
            if ($count > 0) {
                Database::query(
                    "UPDATE blade_usage SET count = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                    [$count, $existing['id']]
                );
            } else {
                Database::query("DELETE FROM blade_usage WHERE id = ?", [$existing['id']]);
            }
        } elseif ($count > 0) {
            Database::query(
                "INSERT INTO blade_usage (razor_id, blade_id, count) VALUES (?, ?, ?)",
                [$razor['id'], $bladeId, $count]
            );
        }

        if (is_ajax()) {
            json_response(['success' => true, 'count' => $count]);
            return;
        }

        flash('success', 'Usage updated.');
        redirect('/razors/' . $id);
    }

    /**
     * Get razor by ID for current user
     */
    private function getRazor(string $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM razors WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $this->userId]
        );
    }
}
