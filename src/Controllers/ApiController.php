<?php
/**
 * API Controller
 * Handles REST API endpoints for the Razor Library
 */

class ApiController
{
    private ?array $user = null;

    /**
     * Authenticate via API key
     */
    private function authenticate(): bool
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return false;
        }

        $apiKey = $matches[1];

        // Rate limit API requests by key
        if (RateLimiter::isLimited(substr($apiKey, 0, 8), 'api_request', 100, 60)) {
            $this->jsonError('Rate limit exceeded', 429);
            return false;
        }

        // Find API key
        $keyHash = hash('sha256', $apiKey);
        $keyRecord = Database::fetch(
            "SELECT ak.*, u.id as user_id, u.username, u.email, u.is_admin
             FROM api_keys ak
             JOIN users u ON ak.user_id = u.id
             WHERE ak.key_hash = ? AND ak.revoked_at IS NULL AND u.deleted_at IS NULL",
            [$keyHash]
        );

        if (!$keyRecord) {
            return false;
        }

        // Update last used
        Database::query(
            "UPDATE api_keys SET last_used_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$keyRecord['id']]
        );

        $this->user = [
            'id' => $keyRecord['user_id'],
            'username' => $keyRecord['username'],
            'email' => $keyRecord['email'],
            'is_admin' => $keyRecord['is_admin'],
        ];

        return true;
    }

    /**
     * Require authentication
     */
    private function requireAuth(): bool
    {
        if (!$this->authenticate()) {
            $this->jsonError('Unauthorized', 401);
            return false;
        }
        return true;
    }

    /**
     * Send JSON response
     */
    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send JSON error response
     */
    private function jsonError(string $message, int $status = 400): void
    {
        $this->json(['error' => $message], $status);
    }

    /**
     * Get JSON request body
     */
    private function getRequestBody(): array
    {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        return is_array($data) ? $data : [];
    }

    // ==================== RAZORS ====================

    /**
     * List all razors
     */
    public function listRazors(): void
    {
        if (!$this->requireAuth()) return;

        $razors = Database::fetchAll(
            "SELECT id, name, brand, description, notes, year_manufactured, country_manufactured, hero_image, created_at, updated_at
             FROM razors
             WHERE user_id = ? AND deleted_at IS NULL
             ORDER BY name ASC",
            [$this->user['id']]
        );

        $this->json(['razors' => $razors]);
    }

    /**
     * Get a single razor
     */
    public function getRazor(int $id): void
    {
        if (!$this->requireAuth()) return;

        $razor = Database::fetch(
            "SELECT * FROM razors WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $this->user['id']]
        );

        if (!$razor) {
            $this->jsonError('Razor not found', 404);
            return;
        }

        // Get images
        $images = Database::fetchAll(
            "SELECT id, filename, created_at FROM razor_images WHERE razor_id = ?",
            [$id]
        );

        // Get URLs
        $urls = Database::fetchAll(
            "SELECT id, url, description, created_at FROM razor_urls WHERE razor_id = ?",
            [$id]
        );

        // Get blade usage
        $usage = Database::fetchAll(
            "SELECT bu.blade_id, b.name as blade_name, bu.count
             FROM blade_usage bu
             JOIN blades b ON bu.blade_id = b.id
             WHERE bu.razor_id = ?",
            [$id]
        );

        $razor['images'] = $images;
        $razor['urls'] = $urls;
        $razor['blade_usage'] = $usage;

        $this->json(['razor' => $razor]);
    }

    /**
     * Create a new razor
     */
    public function createRazor(): void
    {
        if (!$this->requireAuth()) return;

        $data = $this->getRequestBody();

        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $this->jsonError('Name is required');
            return;
        }

        $brand = trim($data['brand'] ?? '');
        $description = trim($data['description'] ?? '');
        $notes = trim($data['notes'] ?? '');
        $yearManufactured = isset($data['year_manufactured']) ? intval($data['year_manufactured']) : null;
        $countryManufactured = trim($data['country_manufactured'] ?? '');

        Database::query(
            "INSERT INTO razors (user_id, name, brand, description, notes, year_manufactured, country_manufactured)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $this->user['id'],
                $name,
                $brand ?: null,
                $description ?: null,
                $notes ?: null,
                $yearManufactured ?: null,
                $countryManufactured ?: null
            ]
        );

        $id = Database::lastInsertId();
        $this->getRazor($id);
    }

    /**
     * Update a razor
     */
    public function updateRazor(int $id): void
    {
        if (!$this->requireAuth()) return;

        $razor = Database::fetch(
            "SELECT * FROM razors WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $this->user['id']]
        );

        if (!$razor) {
            $this->jsonError('Razor not found', 404);
            return;
        }

        $data = $this->getRequestBody();

        $name = isset($data['name']) ? trim($data['name']) : $razor['name'];
        if (empty($name)) {
            $this->jsonError('Name cannot be empty');
            return;
        }

        $updates = [
            'name' => $name,
            'brand' => isset($data['brand']) ? (trim($data['brand']) ?: null) : $razor['brand'],
            'description' => isset($data['description']) ? (trim($data['description']) ?: null) : $razor['description'],
            'notes' => isset($data['notes']) ? (trim($data['notes']) ?: null) : $razor['notes'],
            'year_manufactured' => isset($data['year_manufactured']) ? (intval($data['year_manufactured']) ?: null) : $razor['year_manufactured'],
            'country_manufactured' => isset($data['country_manufactured']) ? (trim($data['country_manufactured']) ?: null) : $razor['country_manufactured'],
        ];

        Database::query(
            "UPDATE razors SET name = ?, brand = ?, description = ?, notes = ?, year_manufactured = ?, country_manufactured = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$updates['name'], $updates['brand'], $updates['description'], $updates['notes'], $updates['year_manufactured'], $updates['country_manufactured'], $id]
        );

        $this->getRazor($id);
    }

    /**
     * Delete a razor
     */
    public function deleteRazor(int $id): void
    {
        if (!$this->requireAuth()) return;

        $razor = Database::fetch(
            "SELECT * FROM razors WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $this->user['id']]
        );

        if (!$razor) {
            $this->jsonError('Razor not found', 404);
            return;
        }

        Database::query(
            "UPDATE razors SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$id]
        );

        $this->json(['message' => 'Razor deleted']);
    }

    // ==================== BLADES ====================

    /**
     * List all blades
     */
    public function listBlades(): void
    {
        if (!$this->requireAuth()) return;

        $blades = Database::fetchAll(
            "SELECT id, name, brand, description, notes, country_manufactured, hero_image, created_at, updated_at
             FROM blades
             WHERE user_id = ? AND deleted_at IS NULL
             ORDER BY name ASC",
            [$this->user['id']]
        );

        $this->json(['blades' => $blades]);
    }

    /**
     * Get a single blade
     */
    public function getBlade(int $id): void
    {
        if (!$this->requireAuth()) return;

        $blade = Database::fetch(
            "SELECT * FROM blades WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $this->user['id']]
        );

        if (!$blade) {
            $this->jsonError('Blade not found', 404);
            return;
        }

        // Get images
        $images = Database::fetchAll(
            "SELECT id, filename, created_at FROM blade_images WHERE blade_id = ?",
            [$id]
        );

        // Get URLs
        $urls = Database::fetchAll(
            "SELECT id, url, description, created_at FROM blade_urls WHERE blade_id = ?",
            [$id]
        );

        // Get usage by razor
        $usage = Database::fetchAll(
            "SELECT bu.razor_id, r.name as razor_name, bu.count
             FROM blade_usage bu
             JOIN razors r ON bu.razor_id = r.id
             WHERE bu.blade_id = ?",
            [$id]
        );

        $blade['images'] = $images;
        $blade['urls'] = $urls;
        $blade['razor_usage'] = $usage;

        $this->json(['blade' => $blade]);
    }

    /**
     * Create a new blade
     */
    public function createBlade(): void
    {
        if (!$this->requireAuth()) return;

        $data = $this->getRequestBody();

        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $this->jsonError('Name is required');
            return;
        }

        $brand = trim($data['brand'] ?? '');
        $description = trim($data['description'] ?? '');
        $notes = trim($data['notes'] ?? '');
        $countryManufactured = trim($data['country_manufactured'] ?? '');

        Database::query(
            "INSERT INTO blades (user_id, name, brand, description, notes, country_manufactured)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $this->user['id'],
                $name,
                $brand ?: null,
                $description ?: null,
                $notes ?: null,
                $countryManufactured ?: null
            ]
        );

        $id = Database::lastInsertId();
        $this->getBlade($id);
    }

    /**
     * Update a blade
     */
    public function updateBlade(int $id): void
    {
        if (!$this->requireAuth()) return;

        $blade = Database::fetch(
            "SELECT * FROM blades WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $this->user['id']]
        );

        if (!$blade) {
            $this->jsonError('Blade not found', 404);
            return;
        }

        $data = $this->getRequestBody();

        $name = isset($data['name']) ? trim($data['name']) : $blade['name'];
        if (empty($name)) {
            $this->jsonError('Name cannot be empty');
            return;
        }

        $updates = [
            'name' => $name,
            'brand' => isset($data['brand']) ? (trim($data['brand']) ?: null) : $blade['brand'],
            'description' => isset($data['description']) ? (trim($data['description']) ?: null) : $blade['description'],
            'notes' => isset($data['notes']) ? (trim($data['notes']) ?: null) : $blade['notes'],
            'country_manufactured' => isset($data['country_manufactured']) ? (trim($data['country_manufactured']) ?: null) : $blade['country_manufactured'],
        ];

        Database::query(
            "UPDATE blades SET name = ?, brand = ?, description = ?, notes = ?, country_manufactured = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$updates['name'], $updates['brand'], $updates['description'], $updates['notes'], $updates['country_manufactured'], $id]
        );

        $this->getBlade($id);
    }

    /**
     * Delete a blade
     */
    public function deleteBlade(int $id): void
    {
        if (!$this->requireAuth()) return;

        $blade = Database::fetch(
            "SELECT * FROM blades WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $this->user['id']]
        );

        if (!$blade) {
            $this->jsonError('Blade not found', 404);
            return;
        }

        Database::query(
            "UPDATE blades SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$id]
        );

        $this->json(['message' => 'Blade deleted']);
    }

    // ==================== BRUSHES ====================

    /**
     * List all brushes
     */
    public function listBrushes(): void
    {
        if (!$this->requireAuth()) return;

        $brushes = Database::fetchAll(
            "SELECT id, name, brand, bristle_type, knot_size, loft, handle_material, description, notes, use_count, hero_image, created_at, updated_at
             FROM brushes
             WHERE user_id = ? AND deleted_at IS NULL
             ORDER BY name ASC",
            [$this->user['id']]
        );

        $this->json(['brushes' => $brushes]);
    }

    /**
     * Get a single brush
     */
    public function getBrush(int $id): void
    {
        if (!$this->requireAuth()) return;

        $brush = Database::fetch(
            "SELECT * FROM brushes WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $this->user['id']]
        );

        if (!$brush) {
            $this->jsonError('Brush not found', 404);
            return;
        }

        // Get images
        $images = Database::fetchAll(
            "SELECT id, filename, created_at FROM brush_images WHERE brush_id = ?",
            [$id]
        );

        // Get URLs
        $urls = Database::fetchAll(
            "SELECT id, url, description, created_at FROM brush_urls WHERE brush_id = ?",
            [$id]
        );

        $brush['images'] = $images;
        $brush['urls'] = $urls;

        $this->json(['brush' => $brush]);
    }

    /**
     * Create a new brush
     */
    public function createBrush(): void
    {
        if (!$this->requireAuth()) return;

        $data = $this->getRequestBody();

        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $this->jsonError('Name is required');
            return;
        }

        Database::query(
            "INSERT INTO brushes (user_id, name, brand, bristle_type, knot_size, loft, handle_material, description, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $this->user['id'],
                $name,
                trim($data['brand'] ?? '') ?: null,
                trim($data['bristle_type'] ?? '') ?: null,
                trim($data['knot_size'] ?? '') ?: null,
                trim($data['loft'] ?? '') ?: null,
                trim($data['handle_material'] ?? '') ?: null,
                trim($data['description'] ?? '') ?: null,
                trim($data['notes'] ?? '') ?: null
            ]
        );

        $id = Database::lastInsertId();
        $this->getBrush($id);
    }

    /**
     * Update a brush
     */
    public function updateBrush(int $id): void
    {
        if (!$this->requireAuth()) return;

        $brush = Database::fetch(
            "SELECT * FROM brushes WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $this->user['id']]
        );

        if (!$brush) {
            $this->jsonError('Brush not found', 404);
            return;
        }

        $data = $this->getRequestBody();

        $name = isset($data['name']) ? trim($data['name']) : $brush['name'];
        if (empty($name)) {
            $this->jsonError('Name cannot be empty');
            return;
        }

        Database::query(
            "UPDATE brushes SET name = ?, brand = ?, bristle_type = ?, knot_size = ?, loft = ?, handle_material = ?, description = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [
                $name,
                isset($data['brand']) ? (trim($data['brand']) ?: null) : $brush['brand'],
                isset($data['bristle_type']) ? (trim($data['bristle_type']) ?: null) : $brush['bristle_type'],
                isset($data['knot_size']) ? (trim($data['knot_size']) ?: null) : $brush['knot_size'],
                isset($data['loft']) ? (trim($data['loft']) ?: null) : $brush['loft'],
                isset($data['handle_material']) ? (trim($data['handle_material']) ?: null) : $brush['handle_material'],
                isset($data['description']) ? (trim($data['description']) ?: null) : $brush['description'],
                isset($data['notes']) ? (trim($data['notes']) ?: null) : $brush['notes'],
                $id
            ]
        );

        $this->getBrush($id);
    }

    /**
     * Delete a brush
     */
    public function deleteBrush(int $id): void
    {
        if (!$this->requireAuth()) return;

        $brush = Database::fetch(
            "SELECT * FROM brushes WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $this->user['id']]
        );

        if (!$brush) {
            $this->jsonError('Brush not found', 404);
            return;
        }

        Database::query(
            "UPDATE brushes SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$id]
        );

        $this->json(['message' => 'Brush deleted']);
    }

    // ==================== OTHER ITEMS ====================

    /**
     * List all other items
     */
    public function listOtherItems(): void
    {
        if (!$this->requireAuth()) return;

        $items = Database::fetchAll(
            "SELECT id, name, brand, category, scent_notes, description, notes, use_count, hero_image, created_at, updated_at
             FROM other_items
             WHERE user_id = ? AND deleted_at IS NULL
             ORDER BY category, name ASC",
            [$this->user['id']]
        );

        $this->json(['other_items' => $items]);
    }

    /**
     * Get a single other item
     */
    public function getOtherItem(int $id): void
    {
        if (!$this->requireAuth()) return;

        $item = Database::fetch(
            "SELECT * FROM other_items WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $this->user['id']]
        );

        if (!$item) {
            $this->jsonError('Item not found', 404);
            return;
        }

        // Get images
        $images = Database::fetchAll(
            "SELECT id, filename, created_at FROM other_item_images WHERE item_id = ?",
            [$id]
        );

        // Get URLs
        $urls = Database::fetchAll(
            "SELECT id, url, description, created_at FROM other_item_urls WHERE item_id = ?",
            [$id]
        );

        // Get attributes
        $attributes = Database::fetchAll(
            "SELECT id, attribute_name, attribute_value FROM other_item_attributes WHERE item_id = ?",
            [$id]
        );

        $item['images'] = $images;
        $item['urls'] = $urls;
        $item['attributes'] = $attributes;

        $this->json(['other_item' => $item]);
    }

    /**
     * Create a new other item
     */
    public function createOtherItem(): void
    {
        if (!$this->requireAuth()) return;

        $data = $this->getRequestBody();

        $name = trim($data['name'] ?? '');
        $category = trim($data['category'] ?? '');

        if (empty($name)) {
            $this->jsonError('Name is required');
            return;
        }

        if (empty($category)) {
            $this->jsonError('Category is required');
            return;
        }

        $validCategories = ['soap', 'cream', 'aftershave', 'preshave', 'bowl', 'stand', 'case', 'strop', 'other'];
        if (!in_array($category, $validCategories)) {
            $this->jsonError('Invalid category');
            return;
        }

        Database::query(
            "INSERT INTO other_items (user_id, name, brand, category, scent_notes, description, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $this->user['id'],
                $name,
                trim($data['brand'] ?? '') ?: null,
                $category,
                trim($data['scent_notes'] ?? '') ?: null,
                trim($data['description'] ?? '') ?: null,
                trim($data['notes'] ?? '') ?: null
            ]
        );

        $id = Database::lastInsertId();
        $this->getOtherItem($id);
    }

    /**
     * Update an other item
     */
    public function updateOtherItem(int $id): void
    {
        if (!$this->requireAuth()) return;

        $item = Database::fetch(
            "SELECT * FROM other_items WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $this->user['id']]
        );

        if (!$item) {
            $this->jsonError('Item not found', 404);
            return;
        }

        $data = $this->getRequestBody();

        $name = isset($data['name']) ? trim($data['name']) : $item['name'];
        if (empty($name)) {
            $this->jsonError('Name cannot be empty');
            return;
        }

        $category = isset($data['category']) ? trim($data['category']) : $item['category'];
        $validCategories = ['soap', 'cream', 'aftershave', 'preshave', 'bowl', 'stand', 'case', 'strop', 'other'];
        if (!in_array($category, $validCategories)) {
            $this->jsonError('Invalid category');
            return;
        }

        Database::query(
            "UPDATE other_items SET name = ?, brand = ?, category = ?, scent_notes = ?, description = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [
                $name,
                isset($data['brand']) ? (trim($data['brand']) ?: null) : $item['brand'],
                $category,
                isset($data['scent_notes']) ? (trim($data['scent_notes']) ?: null) : $item['scent_notes'],
                isset($data['description']) ? (trim($data['description']) ?: null) : $item['description'],
                isset($data['notes']) ? (trim($data['notes']) ?: null) : $item['notes'],
                $id
            ]
        );

        $this->getOtherItem($id);
    }

    /**
     * Delete an other item
     */
    public function deleteOtherItem(int $id): void
    {
        if (!$this->requireAuth()) return;

        $item = Database::fetch(
            "SELECT * FROM other_items WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $this->user['id']]
        );

        if (!$item) {
            $this->jsonError('Item not found', 404);
            return;
        }

        Database::query(
            "UPDATE other_items SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$id]
        );

        $this->json(['message' => 'Item deleted']);
    }

    // ==================== COLLECTION STATS ====================

    /**
     * Get collection statistics
     */
    public function getStats(): void
    {
        if (!$this->requireAuth()) return;

        $stats = [
            'razors' => Database::fetch("SELECT COUNT(*) as count FROM razors WHERE user_id = ? AND deleted_at IS NULL", [$this->user['id']])['count'],
            'blades' => Database::fetch("SELECT COUNT(*) as count FROM blades WHERE user_id = ? AND deleted_at IS NULL", [$this->user['id']])['count'],
            'brushes' => Database::fetch("SELECT COUNT(*) as count FROM brushes WHERE user_id = ? AND deleted_at IS NULL", [$this->user['id']])['count'],
            'other_items' => Database::fetch("SELECT COUNT(*) as count FROM other_items WHERE user_id = ? AND deleted_at IS NULL", [$this->user['id']])['count'],
        ];

        $this->json(['stats' => $stats]);
    }
}
