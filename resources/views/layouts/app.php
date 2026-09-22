<?php
$primary = safe_hex((string) setting('primary_color', '#1f4f45'), '#1f4f45');
$accent = safe_hex((string) setting('accent_color', '#b5812d'), '#b5812d');
$nav = json_decode((string) setting('nav_links', '[]'), true) ?: [];
$seoDescription = $seoDescription ?? setting('seo_description', $tagline ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? $siteName) ?></title>
    <meta name="description" content="<?= e($seoDescription) ?>">
    <link rel="canonical" href="<?= e(url($_SERVER['REQUEST_URI'] ?? '/')) ?>">
    <meta property="og:title" content="<?= e($title ?? $siteName) ?>">
    <meta property="og:description" content="<?= e($seoDescription) ?>">
    <meta property="og:type" content="website">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,560;1,9..144,500&family=IBM+Plex+Mono:wght@450&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>:root { --pine: <?= e($primary) ?>; --gold: <?= e($accent) ?>; }</style>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<?php if ($announcement = setting('announcement')): ?>
    <div class="announcement"><?= e($announcement) ?></div>
<?php endif; ?>
<header class="mast">
    <a class="wordmark" href="/">
        <svg viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="16" r="12" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M16 4v24M8 16h16" stroke="currentColor" stroke-width="1.2"/><circle cx="16" cy="16" r="2.2" fill="currentColor"/></svg>
        <?= e($siteName) ?>
    </a>
    <button class="menu-toggle" type="button" aria-label="Menu" data-menu><?= icon('menu') ?></button>
    <nav class="mast-nav" data-nav>
        <?php foreach ($nav as $link): ?>
            <a href="<?= e($link['href']) ?>"><?= e($link['label']) ?></a>
        <?php endforeach; ?>
        <?php foreach ($navPages as $page): ?>
            <a href="/pages/<?= e($page['slug']) ?>"><?= e($page['title']) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="mast-actions">
        <a class="icon-btn" href="/courses" aria-label="Search courses"><?= icon('search') ?></a>
        <a class="icon-btn" href="/cart" aria-label="Cart"><?= icon('cart') ?><?php if ($cartCount): ?><span class="count"><?= (int) $cartCount ?></span><?php endif; ?></a>
        <?php if ($currentUser): ?>
            <a class="icon-btn" href="/notifications" aria-label="Notifications"><?= icon('bell') ?><?php if ($unreadCount): ?><span class="count"><?= (int) $unreadCount ?></span><?php endif; ?></a>
            <?php if (\App\Core\Auth::can('admin.access')): ?><a class="btn btn-small btn-ghost" href="/admin">Admin</a><?php endif; ?>
            <?php if (\App\Core\Auth::can('studio.access')): ?><a class="btn btn-small btn-ghost" href="/studio">Studio</a><?php endif; ?>
            <a class="user-chip" href="/account"><span class="avatar"><?= e(initials($currentUser['name'])) ?></span></a>
        <?php else: ?>
            <a class="btn btn-small btn-ghost" href="/login">Sign in</a>
            <a class="btn btn-small" href="/register">Join</a>
        <?php endif; ?>
    </div>
</header>
<main id="main">
    <div class="wrap">
        <?php partial('partials/flash'); ?>
        <?= $content ?>
    </div>
</main>
<footer class="site-footer">
    <div class="wrap footer-grid">
        <div>
            <div class="wordmark" style="margin-bottom:10px"><?= e($siteName) ?></div>
            <p class="quiet"><?= e(setting('footer_blurb', '')) ?></p>
        </div>
        <div>
            <p class="kicker">Studio</p>
            <p><a href="/courses">Courses</a></p>
            <p><a href="/live">Live classes</a></p>
            <p><a href="/teach">Teach</a></p>
            <p><a href="/contact">Contact</a></p>
            <?php foreach ($footerPages as $page): ?>
                <p><a href="/pages/<?= e($page['slug']) ?>"><?= e($page['title']) ?></a></p>
            <?php endforeach; ?>
        </div>
        <div>
            <p class="kicker">Notes</p>
            <form class="footer-form" method="post" action="/newsletter">
                <?= csrf_field() ?>
                <input type="email" name="email" required placeholder="Email for the studio letter">
                <button type="submit">Join</button>
            </form>
            <p class="quiet">No cadence promises. A letter when there is something to say.</p>
        </div>
    </div>
    <div class="wrap legal"><span>© <?= date('Y') ?> <?= e($siteName) ?></span><span>Certificates can be verified.</span></div>
</footer>
<script src="/assets/js/app.js"></script>
</body>
</html>
