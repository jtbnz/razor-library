<?php
/**
 * Share Controller - Public collection viewing
 */

class ShareController
{
    /**
     * Show shared collection overview
     */
    public function index(string $token): string
    {
        $user = Database::fetch(
            "SELECT id, username, share_token FROM users WHERE share_token = ? AND deleted_at IS NULL",
            [$token]
        );

        if (!$user) {
            http_response_code(404);
            return view('errors/404');
        }

        $userId = $user['id'];

        // Get collection stats
        $stats = [
            'razors' => Database::fetch("SELECT COUNT(*) as count FROM razors WHERE user_id = ? AND deleted_at IS NULL", [$userId])['count'],
            'blades' => Database::fetch("SELECT COUNT(*) as count FROM blades WHERE user_id = ? AND deleted_at IS NULL", [$userId])['count'],
            'brushes' => Database::fetch("SELECT COUNT(*) as count FROM brushes WHERE user_id = ? AND deleted_at IS NULL", [$userId])['count'],
            'other' => Database::fetch("SELECT COUNT(*) as count FROM other_items WHERE user_id = ? AND deleted_at IS NULL", [$userId])['count'],
        ];

        // Get recent items
        $recentRazors = Database::fetchAll(
            "SELECT * FROM razors WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 4",
            [$userId]
        );

        $recentBlades = Database::fetchAll(
            "SELECT * FROM blades WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 4",
            [$userId]
        );

        return view('share/index', [
            'user' => $user,
            'stats' => $stats,
            'recentRazors' => $recentRazors,
            'recentBlades' => $recentBlades,
            'token' => $token,
        ]);
    }

    /**
     * Show shared razors list
     */
    public function razors(string $token): string
    {
        $user = Database::fetch(
            "SELECT id, username FROM users WHERE share_token = ? AND deleted_at IS NULL",
            [$token]
        );

        if (!$user) {
            http_response_code(404);
            return view('errors/404');
        }

        $razors = Database::fetchAll(
            "SELECT * FROM razors WHERE user_id = ? AND deleted_at IS NULL ORDER BY name",
            [$user['id']]
        );

        return view('share/razors', [
            'user' => $user,
            'razors' => $razors,
            'token' => $token,
        ]);
    }

    /**
     * Show shared razor detail
     */
    public function razor(string $token, int $id): string
    {
        $user = Database::fetch(
            "SELECT id, username FROM users WHERE share_token = ? AND deleted_at IS NULL",
            [$token]
        );

        if (!$user) {
            http_response_code(404);
            return view('errors/404');
        }

        $razor = Database::fetch(
            "SELECT * FROM razors WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $user['id']]
        );

        if (!$razor) {
            http_response_code(404);
            return view('errors/404');
        }

        // Get additional images
        $images = Database::fetchAll(
            "SELECT * FROM razor_images WHERE razor_id = ? ORDER BY created_at DESC",
            [$id]
        );

        // Get blade usage
        $bladeUsage = Database::fetchAll(
            "SELECT bu.count, b.name as blade_name
             FROM blade_usage bu
             JOIN blades b ON bu.blade_id = b.id
             WHERE bu.razor_id = ?
             ORDER BY bu.count DESC",
            [$id]
        );

        return view('share/razor', [
            'user' => $user,
            'razor' => $razor,
            'images' => $images,
            'bladeUsage' => $bladeUsage,
            'token' => $token,
        ]);
    }

    /**
     * Show shared blades list
     */
    public function blades(string $token): string
    {
        $user = Database::fetch(
            "SELECT id, username FROM users WHERE share_token = ? AND deleted_at IS NULL",
            [$token]
        );

        if (!$user) {
            http_response_code(404);
            return view('errors/404');
        }

        $blades = Database::fetchAll(
            "SELECT blades.*,
                    (SELECT COALESCE(SUM(bu.count), 0) FROM blade_usage bu WHERE bu.blade_id = blades.id) as total_usage
             FROM blades
             WHERE user_id = ? AND deleted_at IS NULL
             ORDER BY name",
            [$user['id']]
        );

        return view('share/blades', [
            'user' => $user,
            'blades' => $blades,
            'token' => $token,
        ]);
    }

    /**
     * Show shared blade detail
     */
    public function blade(string $token, int $id): string
    {
        $user = Database::fetch(
            "SELECT id, username FROM users WHERE share_token = ? AND deleted_at IS NULL",
            [$token]
        );

        if (!$user) {
            http_response_code(404);
            return view('errors/404');
        }

        $blade = Database::fetch(
            "SELECT * FROM blades WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $user['id']]
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

        // Get usage by razor
        $usage = Database::fetchAll(
            "SELECT bu.*, r.name as razor_name
             FROM blade_usage bu
             JOIN razors r ON bu.razor_id = r.id
             WHERE bu.blade_id = ?
             ORDER BY r.name",
            [$id]
        );

        $totalUsage = array_sum(array_column($usage, 'count'));

        return view('share/blade', [
            'user' => $user,
            'blade' => $blade,
            'images' => $images,
            'usage' => $usage,
            'totalUsage' => $totalUsage,
            'token' => $token,
        ]);
    }

    /**
     * Show shared brushes list
     */
    public function brushes(string $token): string
    {
        $user = Database::fetch(
            "SELECT id, username FROM users WHERE share_token = ? AND deleted_at IS NULL",
            [$token]
        );

        if (!$user) {
            http_response_code(404);
            return view('errors/404');
        }

        $brushes = Database::fetchAll(
            "SELECT * FROM brushes WHERE user_id = ? AND deleted_at IS NULL ORDER BY name",
            [$user['id']]
        );

        return view('share/brushes', [
            'user' => $user,
            'brushes' => $brushes,
            'token' => $token,
        ]);
    }

    /**
     * Show shared brush detail
     */
    public function brush(string $token, int $id): string
    {
        $user = Database::fetch(
            "SELECT id, username FROM users WHERE share_token = ? AND deleted_at IS NULL",
            [$token]
        );

        if (!$user) {
            http_response_code(404);
            return view('errors/404');
        }

        $brush = Database::fetch(
            "SELECT * FROM brushes WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $user['id']]
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

        return view('share/brush', [
            'user' => $user,
            'brush' => $brush,
            'images' => $images,
            'token' => $token,
        ]);
    }

    /**
     * Show shared other items list
     */
    public function other(string $token): string
    {
        $user = Database::fetch(
            "SELECT id, username FROM users WHERE share_token = ? AND deleted_at IS NULL",
            [$token]
        );

        if (!$user) {
            http_response_code(404);
            return view('errors/404');
        }

        $category = $_GET['category'] ?? 'all';

        $params = [$user['id']];
        $categoryFilter = '';
        $categories = [
            'bowl' => 'Bowls',
            'soap' => 'Soaps',
            'balm' => 'Balms',
            'splash' => 'Splashes',
            'fragrance' => 'Fragrances',
        ];

        if ($category !== 'all' && isset($categories[$category])) {
            $categoryFilter = 'AND category = ?';
            $params[] = $category;
        }

        $items = Database::fetchAll(
            "SELECT * FROM other_items
             WHERE user_id = ? AND deleted_at IS NULL {$categoryFilter}
             ORDER BY name",
            $params
        );

        return view('share/other', [
            'user' => $user,
            'items' => $items,
            'categories' => $categories,
            'currentCategory' => $category,
            'token' => $token,
        ]);
    }

    /**
     * Show shared other item detail
     */
    public function otherItem(string $token, int $id): string
    {
        $user = Database::fetch(
            "SELECT id, username FROM users WHERE share_token = ? AND deleted_at IS NULL",
            [$token]
        );

        if (!$user) {
            http_response_code(404);
            return view('errors/404');
        }

        $item = Database::fetch(
            "SELECT * FROM other_items WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$id, $user['id']]
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

        $categories = [
            'bowl' => 'Bowls',
            'soap' => 'Soaps',
            'balm' => 'Balms',
            'splash' => 'Splashes',
            'fragrance' => 'Fragrances',
        ];

        return view('share/other-item', [
            'user' => $user,
            'item' => $item,
            'attributes' => $attributes,
            'images' => $images,
            'categories' => $categories,
            'token' => $token,
        ]);
    }
}
