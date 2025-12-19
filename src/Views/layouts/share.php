<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($user['username']) ?>'s Collection - Razor Library</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="header-left">
                <a href="<?= url('/share/' . e($token)) ?>" class="header-logo">
                    <span class="logo-text"><?= e($user['username']) ?>'s Collection</span>
                </a>
            </div>
            <nav class="header-nav">
                <a href="<?= url('/share/' . e($token) . '/razors') ?>" class="nav-link">Razors</a>
                <a href="<?= url('/share/' . e($token) . '/blades') ?>" class="nav-link">Blades</a>
                <a href="<?= url('/share/' . e($token) . '/brushes') ?>" class="nav-link">Brushes</a>
                <a href="<?= url('/share/' . e($token) . '/other') ?>" class="nav-link">Other</a>
            </nav>
            <button class="menu-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <!-- Mobile Navigation -->
    <nav class="mobile-nav">
        <a href="<?= url('/share/' . e($token) . '/razors') ?>" class="mobile-nav-link">Razors</a>
        <a href="<?= url('/share/' . e($token) . '/blades') ?>" class="mobile-nav-link">Blades</a>
        <a href="<?= url('/share/' . e($token) . '/brushes') ?>" class="mobile-nav-link">Brushes</a>
        <a href="<?= url('/share/' . e($token) . '/other') ?>" class="mobile-nav-link">Other</a>
    </nav>

    <!-- Main Content -->
    <main class="share-main">
        <div class="container">
            <?= $content ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="share-footer">
        <p>Shared with <a href="<?= url('/') ?>">Razor Library</a></p>
    </footer>

    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
