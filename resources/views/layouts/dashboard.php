<?php
$primary = safe_hex((string) setting('primary_color', '#1f4f45'), '#1f4f45');
$accent = safe_hex((string) setting('accent_color', '#b5812d'), '#b5812d');
$active = $active ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Studio') ?> · <?= e($siteName) ?></title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560&family=IBM+Plex+Mono:wght@450&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>:root { --pine: <?= e($primary) ?>; --gold: <?= e($accent) ?>; }</style>
</head>
<body>
<div class="dash">
    <aside class="side">
        <a class="wordmark" href="/"><?= e($siteName) ?></a>
        <?php foreach (dashboard_nav() as $group): ?>
            <div class="side-group">
                <p><?= e($group['label']) ?></p>
                <?php foreach ($group['items'] as $item): ?>
                    <a class="nav-link <?= $active === $item[0] ? 'active' : '' ?>" href="<?= e($item[2]) ?>"><?= icon($item[3]) ?> <?= e($item[1]) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <div class="side-group">
            <a class="nav-link" href="/"><?= icon('arrow') ?> View site</a>
            <form method="post" action="/logout"><?= csrf_field() ?><button class="nav-link" style="width:100%;background:transparent;border:0;text-align:left;cursor:pointer"><?= icon('out') ?> Sign out</button></form>
        </div>
    </aside>
    <div class="main">
        <?php partial('partials/flash'); ?>
        <?= $content ?>
    </div>
</div>
<script src="/assets/js/app.js"></script>
</body>
</html>
