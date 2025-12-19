<?php
/**
 * Other Items Controller
 * Handles bowls, soaps, balms, splashes, and fragrances
 */

class OtherController
{
    private const CATEGORIES = [
        'bowl' => 'Bowls',
        'soap' => 'Soaps',
        'balm' => 'Balms',
        'splash' => 'Splashes',
        'fragrance' => 'Fragrances',
    ];

    /**
     * Display all other items for current user
     */
    public function index(): string
    {
        $userId = $_SESSION['user_id'];
        $category = $_GET['category'] ?? 'all';
        $sort = $_GET['sort'] ?? 'name';

        $orderBy = match ($sort) {
            'date' => 'created_at DESC',
            'last_used' => 'last_used_at DESC NULLS LAST',
            default => 'name ASC',
        };

        $params = [$userId];
        $categoryFilter = '';
        if ($category !== 'all' && isset(self::CATEGORIES[$category])) {
            $categoryFilter = 'AND category = ?';
            $params[] = $category;
        }

        $items = Database::fetchAll(
            "SELECT * FROM other_items
             WHERE user_id = ? AND deleted_at IS NULL {$categoryFilter}
             ORDER BY {$orderBy}",
            $params
        );

        return view('other/index', [
            'items' => $items,
            'categories' => self::CATEGORIES,
            'currentCategory' => $category,
            'sort' => $sort,
        ]);
    }

    /**
     * Show create item form
     */
    public function create(): string
    {
        $category = $_GET['category'] ?? 'soap';
        if (!isset(self::CATEGORIES[$category])) {
            $category = 'soap';
        }

        return view('other/create', [
            'categories' => self::CATEGORIES,
            'selectedCategory' => $category,
        ]);
    }

    /**
     * Store a new item
     */
    public function store(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/other/new');
        }

        $name = trim($_POST['name'] ?? '');
        $category = $_POST['category'] ?? '';
        $brand = trim($_POST['brand'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $scentNotes = trim($_POST['scent_notes'] ?? '');

        if (empty($name)) {
            flash('error', 'Name is required.');
            set_old($_POST);
            redirect('/other/new?category=' . $category);
        }

        if (!isset(self::CATEGORIES[$category])) {
            flash('error', 'Invalid category.');
            set_old($_POST);
            redirect('/other/new');
        }

        $userId = $_SESSION['user_id'];

        // Create item first
        Database::query(
            "INSERT INTO other_items (user_id, category, name, brand, description, notes, scent_notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$userId, $category, $name, $brand ?: null, $description ?: null, $notes ?: null, $scentNotes ?: null]
        );

        $itemId = Database::lastInsertId();

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

                $result = ImageHandler::processUpload($file, "users/{$userId}/other");

                if ($result) {
                    Database::query(
                        "INSERT INTO other_item_images (other_item_id, filename) VALUES (?, ?)",
                        [$itemId, $result['filename']]
                    );

                    // First image becomes the hero image
                    if ($heroImage === null) {
                        $heroImage = $result['filename'];
                    }
                }
            }

            // Update item with hero image
            if ($heroImage) {
                Database::query(
                    "UPDATE other_items SET hero_image = ? WHERE id = ?",
                    [$heroImage, $itemId]
                );
            }
        }

        // Handle attributes based on category
        $this->saveAttributes($itemId, $category, $_POST);

        clear_old();
        flash('success', ucfirst($category) . ' added successfully.');
        redirect("/other/{$itemId}");
    }

    /**
     * Show item details
     */
    public function show(int $id): string
    {
        $userId = $_SESSION['user_id'];

        $item = Database::fetch(
            "SELECT * FROM other_items WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$item) {
            http_response_code(404);
            return view('errors/404');
        }

        // Get attributes
        $attributes = Database::fetchAll(
            "SELECT * FROM other_item_attributes WHERE item_id = ? ORDER BY attribute_name",
            [$id]
        );

        // Get additional images
        $images = Database::fetchAll(
            "SELECT * FROM other_item_images WHERE item_id = ? ORDER BY created_at DESC",
            [$id]
        );

        // Get URLs
        $urls = Database::fetchAll(
            "SELECT * FROM other_item_urls WHERE item_id = ? ORDER BY created_at DESC",
            [$id]
        );

        return view('other/show', [
            'item' => $item,
            'attributes' => $attributes,
            'images' => $images,
            'urls' => $urls,
            'categories' => self::CATEGORIES,
        ]);
    }

    /**
     * Show edit item form
     */
    public function edit(int $id): string
    {
        $userId = $_SESSION['user_id'];

        $item = Database::fetch(
            "SELECT * FROM other_items WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$item) {
            http_response_code(404);
            return view('errors/404');
        }

        // Get attributes as key-value array
        $attributeRows = Database::fetchAll(
            "SELECT attribute_name, attribute_value FROM other_item_attributes WHERE item_id = ?",
            [$id]
        );
        $attributes = [];
        foreach ($attributeRows as $row) {
            $attributes[$row['attribute_name']] = $row['attribute_value'];
        }

        return view('other/edit', [
            'item' => $item,
            'attributes' => $attributes,
            'categories' => self::CATEGORIES,
        ]);
    }

    /**
     * Update an item
     */
    public function update(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/other/{$id}/edit");
        }

        $userId = $_SESSION['user_id'];

        $item = Database::fetch(
            "SELECT * FROM other_items WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$item) {
            http_response_code(404);
            echo view('errors/404');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $category = $_POST['category'] ?? $item['category'];
        $brand = trim($_POST['brand'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $scentNotes = trim($_POST['scent_notes'] ?? '');

        if (empty($name)) {
            flash('error', 'Name is required.');
            redirect("/other/{$id}/edit");
        }

        if (!isset(self::CATEGORIES[$category])) {
            $category = $item['category'];
        }

        Database::query(
            "UPDATE other_items SET category = ?, name = ?, brand = ?, description = ?, notes = ?, scent_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$category, $name, $brand ?: null, $description ?: null, $notes ?: null, $scentNotes ?: null, $id]
        );

        // Update attributes
        Database::query("DELETE FROM other_item_attributes WHERE item_id = ?", [$id]);
        $this->saveAttributes($id, $category, $_POST);

        flash('success', ucfirst($category) . ' updated successfully.');
        redirect("/other/{$id}");
    }

    /**
     * Delete an item (soft delete)
     */
    public function delete(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/other');
        }

        $userId = $_SESSION['user_id'];

        $item = Database::fetch(
            "SELECT * FROM other_items WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$item) {
            flash('error', 'Item not found.');
            redirect('/other');
        }

        Database::query(
            "UPDATE other_items SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$id]
        );

        flash('success', 'Item deleted successfully.');
        redirect('/other');
    }

    /**
     * Upload additional image(s) - supports multiple file upload
     */
    public function uploadImage(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/other/{$id}");
        }

        $userId = $_SESSION['user_id'];

        $item = Database::fetch(
            "SELECT * FROM other_items WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$item) {
            flash('error', 'Item not found.');
            redirect('/other');
        }

        // Handle multiple file uploads
        $files = $_FILES['images'] ?? $_FILES['image'] ?? null;
        if (!$files || empty($files['name'][0] ?? $files['name'])) {
            flash('error', 'No images selected.');
            redirect("/other/{$id}");
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

        $uploadDir = "users/{$userId}/other";
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
                    "INSERT INTO other_item_images (item_id, filename) VALUES (?, ?)",
                    [$id, $result['filename']]
                );

                // If no hero image set yet, use the first uploaded image
                if (!$item['hero_image']) {
                    Database::query(
                        "UPDATE other_items SET hero_image = ? WHERE id = ?",
                        [$result['filename'], $item['id']]
                    );
                    $item['hero_image'] = $result['filename'];
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

        redirect("/other/{$id}");
    }

    /**
     * Set an image as the hero/tile image
     */
    public function setHeroImage(int $id, int $imageId): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/other/{$id}");
        }

        $userId = $_SESSION['user_id'];

        $item = Database::fetch(
            "SELECT * FROM other_items WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$item) {
            flash('error', 'Item not found.');
            redirect('/other');
            return;
        }

        $image = Database::fetch(
            "SELECT * FROM other_item_images WHERE id = ? AND item_id = ?",
            [$imageId, $item['id']]
        );

        if ($image) {
            Database::query(
                "UPDATE other_items SET hero_image = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$image['filename'], $item['id']]
            );
            flash('success', 'Hero image updated.');
        } else {
            flash('error', 'Image not found.');
        }

        redirect("/other/{$id}");
    }

    /**
     * Delete additional image
     */
    public function deleteImage(int $itemId, int $imageId): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/other/{$itemId}");
        }

        $userId = $_SESSION['user_id'];

        $item = Database::fetch(
            "SELECT * FROM other_items WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$itemId, $userId]
        );

        if (!$item) {
            flash('error', 'Item not found.');
            redirect('/other');
        }

        $image = Database::fetch(
            "SELECT * FROM other_item_images WHERE id = ? AND item_id = ?",
            [$imageId, $itemId]
        );

        if (!$image) {
            flash('error', 'Image not found.');
            redirect("/other/{$itemId}");
        }

        // Delete file
        $uploadDir = "users/{$userId}/other";
        ImageHandler::delete($uploadDir, $image['filename']);

        // Delete record
        Database::query("DELETE FROM other_item_images WHERE id = ?", [$imageId]);

        flash('success', 'Image deleted successfully.');
        redirect("/other/{$itemId}");
    }

    /**
     * Add URL to item
     */
    public function addUrl(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/other/{$id}");
        }

        $userId = $_SESSION['user_id'];

        $item = Database::fetch(
            "SELECT * FROM other_items WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$item) {
            flash('error', 'Item not found.');
            redirect('/other');
        }

        $url = trim($_POST['url'] ?? '');
        $description = trim($_POST['url_description'] ?? '');

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            flash('error', 'Please enter a valid URL.');
            redirect("/other/{$id}");
        }

        Database::query(
            "INSERT INTO other_item_urls (item_id, url, description) VALUES (?, ?, ?)",
            [$id, $url, $description ?: null]
        );

        flash('success', 'URL added successfully.');
        redirect("/other/{$id}");
    }

    /**
     * Delete URL from item
     */
    public function deleteUrl(int $itemId, int $urlId): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/other/{$itemId}");
        }

        $userId = $_SESSION['user_id'];

        $item = Database::fetch(
            "SELECT * FROM other_items WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$itemId, $userId]
        );

        if (!$item) {
            flash('error', 'Item not found.');
            redirect('/other');
        }

        Database::query(
            "DELETE FROM other_item_urls WHERE id = ? AND item_id = ?",
            [$urlId, $itemId]
        );

        flash('success', 'URL deleted successfully.');
        redirect("/other/{$itemId}");
    }

    /**
     * Update last used date
     */
    public function updateLastUsed(int $id): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect("/other/{$id}");
            return;
        }

        $userId = $_SESSION['user_id'];

        $item = Database::fetch(
            "SELECT * FROM other_items WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $userId]
        );

        if (!$item) {
            redirect('/other');
            return;
        }

        $lastUsedAt = trim($_POST['last_used_at'] ?? '');

        if (empty($lastUsedAt)) {
            // Clear the last used date
            Database::query(
                "UPDATE other_items SET last_used_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$id]
            );
        } else {
            // Validate and set the date
            $date = date('Y-m-d H:i:s', strtotime($lastUsedAt));
            Database::query(
                "UPDATE other_items SET last_used_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$date, $id]
            );
        }

        flash('success', 'Last used date updated.');
        redirect("/other/{$id}");
    }

    /**
     * Save category-specific attributes
     */
    private function saveAttributes(int $itemId, string $category, array $data): void
    {
        $attributeFields = $this->getAttributeFields($category);

        foreach ($attributeFields as $field => $label) {
            $value = trim($data[$field] ?? '');
            if (!empty($value)) {
                Database::query(
                    "INSERT INTO other_item_attributes (item_id, attribute_name, attribute_value) VALUES (?, ?, ?)",
                    [$itemId, $field, $value]
                );
            }
        }
    }

    /**
     * Get attribute fields for a category
     */
    private function getAttributeFields(string $category): array
    {
        return match ($category) {
            'bowl' => [
                'material' => 'Material',
                'size' => 'Size',
                'color' => 'Color',
            ],
            'soap' => [
                'base' => 'Base/Formula',
                'size' => 'Size',
                'scent_strength' => 'Scent Strength',
            ],
            'balm' => [
                'size' => 'Size',
                'skin_type' => 'Skin Type',
            ],
            'splash' => [
                'size' => 'Size',
                'alcohol_content' => 'Alcohol Content',
            ],
            'fragrance' => [
                'type' => 'Type',
                'size' => 'Size',
                'concentration' => 'Concentration',
            ],
            default => [],
        };
    }

    /**
     * Get attribute fields for views
     */
    public static function getCategoryAttributes(string $category): array
    {
        return match ($category) {
            'bowl' => [
                'material' => 'Material',
                'size' => 'Size',
                'color' => 'Color',
            ],
            'soap' => [
                'base' => 'Base/Formula',
                'size' => 'Size',
                'scent_strength' => 'Scent Strength',
            ],
            'balm' => [
                'size' => 'Size',
                'skin_type' => 'Skin Type',
            ],
            'splash' => [
                'size' => 'Size',
                'alcohol_content' => 'Alcohol Content',
            ],
            'fragrance' => [
                'type' => 'Type',
                'size' => 'Size',
                'concentration' => 'Concentration',
            ],
            default => [],
        };
    }
}
