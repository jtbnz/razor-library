<?php
$title = 'Subscription Expired - Razor Library';
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
        <div class="auth-box" style="max-width: 500px;">
            <div class="auth-box-header">
                <h1>Subscription <?= $status['status'] === 'trial' ? 'Trial' : '' ?> Expired</h1>
            </div>
            <div class="auth-box-body" style="text-align: center;">
                <p class="mb-4"><?= e($message) ?></p>

                <?php if ($status['status'] === 'trial'): ?>
                <p class="text-muted mb-4">Your 7-day free trial has ended. Subscribe to continue accessing your razor collection.</p>
                <?php endif; ?>

                <div class="d-flex flex-column gap-3">
                    <a href="https://buymeacoffee.com/" target="_blank" rel="noopener" class="btn btn-primary btn-lg">
                        Subscribe Now
                    </a>

                    <a href="<?= function_exists('url') ? url('/profile/export') : '/profile/export' ?>" class="btn btn-outline">
                        Download My Data
                    </a>

                    <a href="<?= function_exists('url') ? url('/logout') : '/logout' ?>" class="btn btn-outline">
                        Logout
                    </a>
                </div>

                <hr class="my-4">

                <p class="text-muted text-small">
                    If you've already subscribed, please allow a few minutes for your subscription to be activated.
                    If issues persist, contact support.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
