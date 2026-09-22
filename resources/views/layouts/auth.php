<?php
$primary = safe_hex((string) setting('primary_color', '#1f4f45'), '#1f4f45');
$accent = safe_hex((string) setting('accent_color', '#b5812d'), '#b5812d');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Sign in') ?></title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,560;1,9..144,500&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>:root { --pine: <?= e($primary) ?>; --gold: <?= e($accent) ?>; }</style>
</head>
<body>
<div class="auth-shell">
    <aside class="auth-aside">
        <a class="wordmark" href="/" style="color:#f6f1e7"><?= e($siteName) ?></a>
        <div>
            <p class="kicker">The studio keys</p>
            <h1>Come in. The work is already on the table.</h1>
        </div>
        <p class="quiet">Demo password for every seeded account: meridian</p>
    </aside>
    <div class="auth-panel">
        <?php partial('partials/flash'); ?>
        <?= $content ?>
    </div>
</div>
<script src="/assets/js/app.js"></script>
</body>
</html>
