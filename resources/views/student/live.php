<div class="page-head"><div><p class="kicker">Live</p><h1>Your classes</h1></div></div>
<?php foreach ($classes as $class): ?>
    <article class="board-card" style="margin-bottom:10px">
        <strong><?= e($class['title']) ?></strong>
        <p><?= e($class['course_title']) ?> · <?= e(pretty_datetime($class['starts_at'])) ?></p>
        <a class="btn btn-small" href="/live/room/<?= (int) $class['id'] ?>">Room</a>
        <?php if ($class['joined_at']): ?><span class="quiet">Checked in <?= e(pretty_datetime($class['joined_at'])) ?></span><?php endif; ?>
    </article>
<?php endforeach; ?>
<?php if (!$classes): ?><div class="empty">No live classes on your courses. <a href="/live">See the public calendar</a>.</div><?php endif; ?>
