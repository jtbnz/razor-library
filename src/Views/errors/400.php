<?php
$title = '400 Bad Request - Razor Library';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= function_exists('asset') ? asset('css/style.css') : '/assets/css/style.css' ?>">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-box-header">
                <h1>400</h1>
                <p class="mb-0">Bad Request</p>
            </div>
            <div class="auth-box-body" style="text-align: center;">
                <p>The server could not understand your request. Please check the data you submitted and try again.</p>
                <a href="<?= function_exists('url') ? url('/') : '/' ?>" class="btn btn-primary">Return Home</a>
            </div>
        </div>
    </div>
</body>
</html>
