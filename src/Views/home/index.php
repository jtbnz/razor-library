<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Razor Library - Track Your Wet Shaving Collection</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
    <div class="landing-hero" style="background-image: url('<?= asset('images/hero1.jpeg') ?>');">
        <div class="landing-hero-overlay"></div>
        <div class="landing-hero-content">
            <h1 class="landing-logo">Razor Library</h1>
            <p class="landing-tagline">Catalog and track your wet shaving collection. Razors, blades, brushes, and more.</p>
            <div class="landing-actions">
                <a href="/login" class="btn btn-primary btn-lg">Sign In</a>
            </div>
        </div>
    </div>

    <section class="landing-features">
        <h2>Everything You Need</h2>
        <div class="features-grid">
            <div class="feature-card">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <h3>Photo Gallery</h3>
                <p>Upload multiple images for each item with automatic resizing and thumbnails.</p>
            </div>
            <div class="feature-card">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <h3>Usage Tracking</h3>
                <p>Track blade usage per razor and brush usage to understand your rotation.</p>
            </div>
            <div class="feature-card">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
                <h3>Share Your Collection</h3>
                <p>Generate a private link to share your collection with friends.</p>
            </div>
            <div class="feature-card">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <h3>Export Everything</h3>
                <p>Download your entire collection as a ZIP with images and markdown files.</p>
            </div>
        </div>
    </section>

    <footer class="share-footer">
        <p>Razor Library - A personal collection tracker for wet shaving enthusiasts</p>
    </footer>
</body>
</html>
