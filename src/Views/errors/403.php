<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden - Razor Library</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-box-header">
                <h1>403</h1>
                <p class="mb-0">Access Forbidden</p>
            </div>
            <div class="auth-box-body text-center">
                <p class="text-muted mb-3">You don't have permission to access this page.</p>
                <a href="/dashboard" class="btn btn-primary">Go to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
