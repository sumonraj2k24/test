<div class="page-head"><div><p class="kicker">Shelf</p><h1>My learning</h1></div></div>
<?php if (!$enrollments): ?><div class="empty">Nothing enrolled yet. <a href="/courses">Browse courses</a>.</div><?php endif; ?>
<div class="split-list">
    <?php foreach ($enrollments as $row): ?>
        <article class="board-card">
            <span class="kicker"><?= e($row['category_name'] ?? 'Course') ?> · <?= (int) $row['progress_percent'] ?>%</span>
            <strong><?= e($row['title']) ?></strong>
            <p class="quiet"><?= e($row['instructor_name']) ?></p>
            <div class="progress-track" style="background:#e7e0d2;width:100%;margin:8px 0"><i style="width:<?= (int) $row['progress_percent'] ?>%"></i></div>
            <a class="btn btn-small" href="/learn/<?= e($row['slug']) ?>"><?= $row['completed_at'] ? 'Review' : 'Continue' ?></a>
            <a href="/courses/<?= e($row['slug']) ?>/discussions">Discussion</a>
        </article>
    <?php endforeach; ?>
</div>
