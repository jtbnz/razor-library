<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? 'Razor Library') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
    <div class="page-wrapper">
        <header class="header">
            <div class="header-inner">
                <a href="<?= url(is_authenticated() ? '/dashboard' : '/') ?>" class="logo">Razor Library</a>

                <?php if (is_authenticated()): ?>
                <button class="menu-toggle" aria-label="Toggle menu">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <nav class="nav-desktop">
                    <a href="<?= url('/razors') ?>" class="<?= strpos($_SERVER['REQUEST_URI'], '/razors') !== false ? 'active' : '' ?>">Razors</a>
                    <a href="<?= url('/blades') ?>" class="<?= strpos($_SERVER['REQUEST_URI'], '/blades') !== false ? 'active' : '' ?>">Blades</a>
                    <a href="<?= url('/brushes') ?>" class="<?= strpos($_SERVER['REQUEST_URI'], '/brushes') !== false ? 'active' : '' ?>">Brushes</a>
                    <a href="<?= url('/other') ?>" class="<?= strpos($_SERVER['REQUEST_URI'], '/other') !== false ? 'active' : '' ?>">Other</a>
                    <a href="<?= url('/profile') ?>" class="<?= strpos($_SERVER['REQUEST_URI'], '/profile') !== false ? 'active' : '' ?>">Profile</a>
                    <?php if (is_admin()): ?>
                    <a href="<?= url('/admin') ?>" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin') !== false ? 'active' : '' ?>">Admin</a>
                    <?php endif; ?>
                    <a href="<?= url('/logout') ?>">Logout</a>
                </nav>
                <?php endif; ?>
            </div>
        </header>

        <?php if (is_authenticated()): ?>
        <div class="layout-with-sidebar">
            <aside class="sidebar">
                <nav class="sidebar-nav">
                    <a href="<?= url('/razors') ?>" class="<?= strpos($_SERVER['REQUEST_URI'], '/razors') !== false ? 'active' : '' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <!-- Safety razor icon -->
                            <rect x="8" y="2" width="8" height="5" rx="1" stroke-width="2"/>
                            <line x1="12" y1="7" x2="12" y2="22" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Razors
                    </a>
                    <a href="<?= url('/blades') ?>" class="<?= strpos($_SERVER['REQUEST_URI'], '/blades') !== false ? 'active' : '' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <!-- DE blade icon -->
                            <rect x="2" y="8" width="20" height="8" rx="1" stroke-width="2"/>
                            <line x1="5" y1="12" x2="19" y2="12" stroke-width="1.5"/>
                            <circle cx="4.5" cy="12" r="1" fill="currentColor"/>
                            <circle cx="19.5" cy="12" r="1" fill="currentColor"/>
                        </svg>
                        Blades
                    </a>
                    <a href="<?= url('/brushes') ?>" class="<?= strpos($_SERVER['REQUEST_URI'], '/brushes') !== false ? 'active' : '' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <!-- Shaving brush icon -->
                            <ellipse cx="12" cy="6" rx="5" ry="4" stroke-width="2"/>
                            <path d="M9 10 L9 14 Q9 16 10 17 L10 21 Q10 22 12 22 Q14 22 14 21 L14 17 Q15 16 15 14 L15 10" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Brushes
                    </a>
                    <a href="<?= url('/other') ?>" class="<?= strpos($_SERVER['REQUEST_URI'], '/other') !== false ? 'active' : '' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        Other
                    </a>

                    <div class="divider"></div>

                    <a href="<?= url('/profile') ?>" class="<?= strpos($_SERVER['REQUEST_URI'], '/profile') !== false ? 'active' : '' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Profile
                    </a>

                    <?php if (is_admin()): ?>
                    <a href="<?= url('/admin') ?>" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin') !== false ? 'active' : '' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Admin
                    </a>
                    <?php endif; ?>

                    <div class="divider"></div>

                    <a href="<?= url('/logout') ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Logout
                    </a>
                </nav>
            </aside>
            <div class="sidebar-overlay"></div>

            <main class="main-content">
                <div class="container">
                    <?php if (has_flash('success')): ?>
                    <div class="alert alert-success"><?= get_flash('success') ?></div>
                    <?php endif; ?>

                    <?php if (has_flash('error')): ?>
                    <div class="alert alert-error"><?= get_flash('error') ?></div>
                    <?php endif; ?>

                    <?php if (has_flash('warning')): ?>
                    <div class="alert alert-warning"><?= get_flash('warning') ?></div>
                    <?php endif; ?>

                    <?= $content ?? '' ?>
                </div>
            </main>
        </div>
        <?php else: ?>
        <main class="main-content">
            <?= $content ?? '' ?>
        </main>
        <?php endif; ?>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> Razor Library. <a href="<?= url('/terms') ?>">Terms and Conditions</a></p>
            </div>
        </footer>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
