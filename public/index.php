<?php
/**
 * Razor Library - Main Entry Point
 *
 * All requests are routed through this file.
 */

// Error reporting (will be controlled by config in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Handle /uploads/* requests for PHP built-in server (htaccess doesn't work with php -S)
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/uploads/(.+)$#', $requestUri, $matches)) {
    $_GET['path'] = $matches[1];
    require __DIR__ . '/uploads.php';
    exit;
}

// Load configuration
$config = require BASE_PATH . '/config.php';
if (file_exists(BASE_PATH . '/config.local.php')) {
    $localConfig = require BASE_PATH . '/config.local.php';
    $config = array_merge($config, $localConfig);
}

// Set debug mode
if ($config['APP_DEBUG']) {
    ini_set('display_errors', 1);
}

// Make config globally available
$GLOBALS['config'] = $config;

// Helper function to get config value
function config(string $key, $default = null)
{
    return $GLOBALS['config'][$key] ?? $default;
}

// Autoload classes
spl_autoload_register(function ($class) {
    $paths = [
        BASE_PATH . '/src/Controllers/',
        BASE_PATH . '/src/Models/',
        BASE_PATH . '/src/Helpers/',
        BASE_PATH . '/src/Middleware/',
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load helper functions
require_once BASE_PATH . '/src/Helpers/functions.php';

// Initialize database
require_once BASE_PATH . '/src/Helpers/Database.php';
Database::init();

// Start session
session_name(config('SESSION_NAME'));
session_start();

// Initialize CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get request URI and method
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove trailing slash except for root
if ($requestUri !== '/' && substr($requestUri, -1) === '/') {
    $requestUri = rtrim($requestUri, '/');
}

// Load router
require_once BASE_PATH . '/src/Helpers/Router.php';
$router = new Router();

// Define routes
// Public routes
$router->get('/', 'HomeController@index');
$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');
$router->get('/forgot-password', 'AuthController@forgotPasswordForm');
$router->post('/forgot-password', 'AuthController@forgotPassword');
$router->get('/reset-password/{token}', 'AuthController@resetPasswordForm');
$router->post('/reset-password/{token}', 'AuthController@resetPassword');
$router->get('/setup', 'AuthController@setupForm');
$router->post('/setup', 'AuthController@setup');

// Share routes (public with token)
$router->get('/share/{token}', 'ShareController@index');
$router->get('/share/{token}/razors', 'ShareController@razors');
$router->get('/share/{token}/razors/{id}', 'ShareController@razor');
$router->get('/share/{token}/blades', 'ShareController@blades');
$router->get('/share/{token}/blades/{id}', 'ShareController@blade');
$router->get('/share/{token}/brushes', 'ShareController@brushes');
$router->get('/share/{token}/brushes/{id}', 'ShareController@brush');
$router->get('/share/{token}/other', 'ShareController@other');
$router->get('/share/{token}/other/{id}', 'ShareController@otherItem');

// Protected routes (require authentication)
$router->get('/dashboard', 'DashboardController@index', ['auth']);

// Razors
$router->get('/razors', 'RazorController@index', ['auth']);
$router->get('/razors/new', 'RazorController@create', ['auth']);
$router->post('/razors', 'RazorController@store', ['auth']);
$router->get('/razors/{id}', 'RazorController@show', ['auth']);
$router->get('/razors/{id}/edit', 'RazorController@edit', ['auth']);
$router->post('/razors/{id}', 'RazorController@update', ['auth']);
$router->post('/razors/{id}/delete', 'RazorController@delete', ['auth']);
$router->post('/razors/{id}/images', 'RazorController@uploadImage', ['auth']);
$router->post('/razors/{id}/images/{imageId}/hero', 'RazorController@setHeroImage', ['auth']);
$router->post('/razors/{id}/images/{imageId}/delete', 'RazorController@deleteImage', ['auth']);
$router->post('/razors/{id}/urls', 'RazorController@addUrl', ['auth']);
$router->post('/razors/{id}/urls/{urlId}/delete', 'RazorController@deleteUrl', ['auth']);
$router->post('/razors/{id}/last-used', 'RazorController@updateLastUsed', ['auth']);
$router->post('/razors/{id}/blades', 'RazorController@addBlade', ['auth']);
$router->post('/razors/{id}/blades/{bladeId}/remove', 'RazorController@removeBlade', ['auth']);

// Blades
$router->get('/blades', 'BladeController@index', ['auth']);
$router->get('/blades/new', 'BladeController@create', ['auth']);
$router->post('/blades', 'BladeController@store', ['auth']);
$router->get('/blades/{id}', 'BladeController@show', ['auth']);
$router->get('/blades/{id}/edit', 'BladeController@edit', ['auth']);
$router->post('/blades/{id}', 'BladeController@update', ['auth']);
$router->post('/blades/{id}/delete', 'BladeController@delete', ['auth']);
$router->post('/blades/{id}/images', 'BladeController@uploadImage', ['auth']);
$router->post('/blades/{id}/images/{imageId}/hero', 'BladeController@setHeroImage', ['auth']);
$router->post('/blades/{id}/images/{imageId}/delete', 'BladeController@deleteImage', ['auth']);
$router->post('/blades/{id}/urls', 'BladeController@addUrl', ['auth']);
$router->post('/blades/{id}/urls/{urlId}/delete', 'BladeController@deleteUrl', ['auth']);
$router->post('/blades/{id}/last-used', 'BladeController@updateLastUsed', ['auth']);

// Brushes
$router->get('/brushes', 'BrushController@index', ['auth']);
$router->get('/brushes/new', 'BrushController@create', ['auth']);
$router->post('/brushes', 'BrushController@store', ['auth']);
$router->get('/brushes/{id}', 'BrushController@show', ['auth']);
$router->get('/brushes/{id}/edit', 'BrushController@edit', ['auth']);
$router->post('/brushes/{id}', 'BrushController@update', ['auth']);
$router->post('/brushes/{id}/delete', 'BrushController@delete', ['auth']);
$router->post('/brushes/{id}/images', 'BrushController@uploadImage', ['auth']);
$router->post('/brushes/{id}/images/{imageId}/hero', 'BrushController@setHeroImage', ['auth']);
$router->post('/brushes/{id}/images/{imageId}/delete', 'BrushController@deleteImage', ['auth']);
$router->post('/brushes/{id}/urls', 'BrushController@addUrl', ['auth']);
$router->post('/brushes/{id}/urls/{urlId}/delete', 'BrushController@deleteUrl', ['auth']);
$router->post('/brushes/{id}/last-used', 'BrushController@updateLastUsed', ['auth']);

// Other items
$router->get('/other', 'OtherController@index', ['auth']);
$router->get('/other/new', 'OtherController@create', ['auth']);
$router->post('/other', 'OtherController@store', ['auth']);
$router->get('/other/{id}', 'OtherController@show', ['auth']);
$router->get('/other/{id}/edit', 'OtherController@edit', ['auth']);
$router->post('/other/{id}', 'OtherController@update', ['auth']);
$router->post('/other/{id}/delete', 'OtherController@delete', ['auth']);
$router->post('/other/{id}/images', 'OtherController@uploadImage', ['auth']);
$router->post('/other/{id}/images/{imageId}/hero', 'OtherController@setHeroImage', ['auth']);
$router->post('/other/{id}/images/{imageId}/delete', 'OtherController@deleteImage', ['auth']);
$router->post('/other/{id}/urls', 'OtherController@addUrl', ['auth']);
$router->post('/other/{id}/urls/{urlId}/delete', 'OtherController@deleteUrl', ['auth']);
$router->post('/other/{id}/last-used', 'OtherController@updateLastUsed', ['auth']);

// Profile
$router->get('/profile', 'ProfileController@index', ['auth']);
$router->post('/profile', 'ProfileController@update', ['auth']);
$router->post('/profile/regenerate-share-token', 'ProfileController@regenerateShareToken', ['auth']);
$router->get('/profile/export', 'ProfileController@export', ['auth']);
$router->post('/profile/import-csv', 'ProfileController@importCsv', ['auth']);
$router->get('/profile/csv-template', 'ProfileController@downloadTemplate', ['auth']);

// Admin
$router->get('/admin', 'AdminController@index', ['auth', 'admin']);
$router->get('/admin/users/new', 'AdminController@create', ['auth', 'admin']);
$router->post('/admin/users', 'AdminController@store', ['auth', 'admin']);
$router->get('/admin/users/{id}/edit', 'AdminController@edit', ['auth', 'admin']);
$router->post('/admin/users/{id}', 'AdminController@update', ['auth', 'admin']);
$router->post('/admin/users/{id}/delete', 'AdminController@delete', ['auth', 'admin']);

// Admin backup/restore
$router->post('/admin/backup', 'AdminController@backup', ['auth', 'admin']);
$router->get('/admin/backup/{filename}/download', 'AdminController@downloadBackup', ['auth', 'admin']);
$router->post('/admin/backup/{filename}/delete', 'AdminController@deleteBackup', ['auth', 'admin']);
$router->post('/admin/restore', 'AdminController@restore', ['auth', 'admin']);
$router->post('/admin/reset-database', 'AdminController@resetDatabase', ['auth', 'admin']);

// Dispatch the request
$router->dispatch($requestMethod, $requestUri);
