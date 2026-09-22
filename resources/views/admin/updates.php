<div class="page-head"><div><p class="kicker">Code <?= e($code) ?> · database <?= e($database) ?></p><h1>System updates</h1></div></div>
<?php if ($pending): ?>
    <?php foreach ($pending as $update): ?>
        <article class="board-card">
            <strong><?= e($update['version']) ?></strong>
            <p><?= e($update['notes']) ?></p>
        </article>
    <?php endforeach; ?>
    <form method="post" action="/admin/updates/apply"><?= csrf_field() ?><button class="btn">Apply next update</button></form>
    <p class="quiet">A backup is taken before the SQL runs.</p>
<?php else: ?>
    <div class="empty">Database matches the newest shipped update.</div>
<?php endif; ?>
<h2>History</h2>
<ul><?php foreach ($history as $row): ?><li><?= e($row['version']) ?> · <?= e(pretty_datetime($row['applied_at'])) ?> · <?= e($row['name'] ?? 'install') ?><br><span class="quiet"><?= e($row['notes']) ?></span></li><?php endforeach; ?></ul>
