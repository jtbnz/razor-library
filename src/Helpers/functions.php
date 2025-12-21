<?php
/**
 * Helper functions for Razor Library
 */

/**
 * Render a view with data
 */
function view(string $name, array $data = []): string
{
    extract($data);

    $viewFile = BASE_PATH . '/src/Views/' . $name . '.php';
    if (!file_exists($viewFile)) {
        return "View not found: {$name}";
    }

    ob_start();
    require $viewFile;
    return ob_get_clean();
}

/**
 * Get the base path for subdirectory installs
 */
function base_path(): string
{
    return config('APP_BASE_PATH', '');
}

/**
 * Generate a URL with base path prefix
 */
function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

/**
 * Redirect to a URL
 */
function redirect(string $url): void
{
    // Prepend base path if URL starts with /
    if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
        $url = base_path() . $url;
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Get CSRF token
 */
function csrf_token(): string
{
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Generate CSRF input field
 */
function csrf_field(): string
{
    return '<input type="hidden" name="' . config('CSRF_TOKEN_NAME') . '" value="' . csrf_token() . '">';
}

/**
 * Verify CSRF token
 */
function verify_csrf(): bool
{
    $tokenName = config('CSRF_TOKEN_NAME');
    $token = $_POST[$tokenName] ?? $_GET[$tokenName] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Escape HTML
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Get current user
 */
function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;
    if ($user === null) {
        $user = Database::fetch(
            "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL",
            [$_SESSION['user_id']]
        );
    }
    return $user ?: null;
}

/**
 * Check if user is authenticated
 */
function is_authenticated(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function is_admin(): bool
{
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

/**
 * Flash message helpers
 */
function flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key, bool $escape = true): ?string
{
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    if ($message !== null && $escape) {
        return htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    }
    return $message;
}

function has_flash(string $key): bool
{
    return isset($_SESSION['flash'][$key]);
}

/**
 * Old input helper (for form repopulation)
 */
function old(string $key, string $default = ''): string
{
    return $_SESSION['old'][$key] ?? $default;
}

function set_old(array $data): void
{
    $_SESSION['old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

/**
 * Generate a UUID v4
 */
function generate_uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Generate a secure random token
 */
function generate_token(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

/**
 * Format date for display
 */
function format_date(string $date, string $format = 'M j, Y'): string
{
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function format_datetime(string $datetime, string $format = 'M j, Y g:i A'): string
{
    return date($format, strtotime($datetime));
}

/**
 * Slugify a string
 */
function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'item';
}

/**
 * Get file extension from MIME type
 */
function mime_to_extension(string $mime): string
{
    $map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    return $map[$mime] ?? 'jpg';
}

/**
 * Validate email format
 */
function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if request is AJAX
 */
function is_ajax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Return JSON response
 */
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get the base URL
 */
function base_url(string $path = ''): string
{
    $url = config('APP_URL', '');
    return rtrim($url, '/') . '/' . ltrim($path, '/');
}

/**
 * Asset URL helper with cache busting
 */
function asset(string $path): string
{
    $assetPath = base_path() . '/assets/' . ltrim($path, '/');

    // Add cache-busting version for CSS and JS files
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (in_array($ext, ['css', 'js'])) {
        $filePath = BASE_PATH . '/public/assets/' . ltrim($path, '/');
        if (file_exists($filePath)) {
            $assetPath .= '?v=' . filemtime($filePath);
        }
    }

    return $assetPath;
}

/**
 * Uploaded image URL helper
 */
function upload_url(string $path): string
{
    return base_path() . '/uploads/' . ltrim($path, '/');
}
