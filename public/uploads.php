<?php
/**
 * Serve uploaded files from data directory
 */

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load configuration
$config = require BASE_PATH . '/config.php';
if (file_exists(BASE_PATH . '/config.local.php')) {
    $localConfig = require BASE_PATH . '/config.local.php';
    $config = array_merge($config, $localConfig);
}

$path = $_GET['path'] ?? '';

// Security: prevent directory traversal
$path = str_replace(['..', "\0"], '', $path);
$path = ltrim($path, '/');

if (empty($path)) {
    http_response_code(404);
    exit;
}

$uploadPath = $config['UPLOAD_PATH'];
$fullPath = $uploadPath . '/' . $path;

if (!file_exists($fullPath) || !is_file($fullPath)) {
    http_response_code(404);
    exit;
}

// Verify file is within upload directory
$realUploadPath = realpath($uploadPath);
$realFilePath = realpath($fullPath);

if ($realFilePath === false || strpos($realFilePath, $realUploadPath) !== 0) {
    http_response_code(403);
    exit;
}

// Get MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $fullPath);
finfo_close($finfo);

// Only allow image types
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(403);
    exit;
}

// Set headers
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: public, max-age=31536000');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');

// Output file
readfile($fullPath);
exit;
