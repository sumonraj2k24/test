<div class="page-head"><div><p class="kicker">Proof</p><h1>Certificates</h1></div></div>
<?php foreach ($certificates as $row): ?>
    <article class="board-card" style="margin-bottom:10px">
        <strong><?= e($row['title']) ?></strong>
        <p class="quiet"><?= e($row['certificate_code']) ?> · <?= e(pretty_date($row['completed_at'])) ?></p>
        <a class="btn btn-small" href="/certificates/<?= e($row['certificate_code']) ?>">Open</a>
        <a href="/certificate/verify/<?= e($row['certificate_code']) ?>">Verify</a>
    </article>
<?php endforeach; ?>
<?php if (!$certificates): ?><div class="empty">Finish a course to earn one. Chris Pell already has SQL That Tells the Truth, if you want to see the format.</div><?php endif; ?>
