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
        <div class="header-inner">
            <a href="<?= url('/share/' . e($token)) ?>" class="logo"><?= e($user['username']) ?>'s Collection</a>

            <button class="menu-toggle" aria-label="Toggle menu">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <nav class="nav-desktop">
                <?php
                $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                $isRazors = strpos($currentPath, '/razors') !== false;
                $isBlades = strpos($currentPath, '/blades') !== false;
                $isBrushes = strpos($currentPath, '/brushes') !== false;
                $isOther = strpos($currentPath, '/other') !== false;
                ?>
                <a href="<?= url('/share/' . e($token) . '/razors') ?>" class="<?= $isRazors ? 'active' : '' ?>">Razors</a>
                <a href="<?= url('/share/' . e($token) . '/blades') ?>" class="<?= $isBlades ? 'active' : '' ?>">Blades</a>
                <a href="<?= url('/share/' . e($token) . '/brushes') ?>" class="<?= $isBrushes ? 'active' : '' ?>">Brushes</a>
                <a href="<?= url('/share/' . e($token) . '/other') ?>" class="<?= $isOther ? 'active' : '' ?>">Other</a>
            </nav>
        </div>
    </header>

    <!-- Mobile Sidebar (same as app layout) -->
    <aside class="sidebar share-sidebar">
        <nav class="sidebar-nav">
            <a href="<?= url('/share/' . e($token) . '/razors') ?>" class="<?= $isRazors ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Razors
            </a>
            <a href="<?= url('/share/' . e($token) . '/blades') ?>" class="<?= $isBlades ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5v6m-6-6h6"/>
                </svg>
                Blades
            </a>
            <a href="<?= url('/share/' . e($token) . '/brushes') ?>" class="<?= $isBrushes ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                Brushes
            </a>
            <a href="<?= url('/share/' . e($token) . '/other') ?>" class="<?= $isOther ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Other
            </a>
        </nav>
    </aside>
    <div class="sidebar-overlay"></div>

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
