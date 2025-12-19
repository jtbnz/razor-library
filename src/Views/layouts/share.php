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
                    <!-- Safety razor icon -->
                    <rect x="8" y="2" width="8" height="5" rx="1" stroke-width="2"/>
                    <line x1="12" y1="7" x2="12" y2="22" stroke-width="2" stroke-linecap="round"/>
                    <line x1="9" y1="10" x2="15" y2="10" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Razors
            </a>
            <a href="<?= url('/share/' . e($token) . '/blades') ?>" class="<?= $isBlades ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <!-- DE blade icon -->
                    <rect x="2" y="8" width="20" height="8" rx="1" stroke-width="2"/>
                    <line x1="5" y1="12" x2="19" y2="12" stroke-width="1.5"/>
                    <circle cx="4.5" cy="12" r="1" fill="currentColor"/>
                    <circle cx="19.5" cy="12" r="1" fill="currentColor"/>
                </svg>
                Blades
            </a>
            <a href="<?= url('/share/' . e($token) . '/brushes') ?>" class="<?= $isBrushes ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <!-- Shaving brush icon -->
                    <ellipse cx="12" cy="6" rx="5" ry="4" stroke-width="2"/>
                    <path d="M9 10 L9 14 Q9 16 10 17 L10 21 Q10 22 12 22 Q14 22 14 21 L14 17 Q15 16 15 14 L15 10" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
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
