<div class="page-head">
    <div>
        <p class="kicker">Live</p>
        <h1>Rooms on the calendar</h1>
    </div>
</div>
<p class="lede">Zoom links appear when an instructor connects the API or pastes a join URL. The in-app room works either way.</p>
<div class="split-list">
    <?php foreach ($classes as $class): ?>
        <article class="board-card">
            <span class="kicker"><?= strtotime($class['starts_at'] . ' UTC') > time() ? 'Upcoming' : 'Held' ?> · <?= e($class['status']) ?></span>
            <strong><?= e($class['title']) ?></strong>
            <p><?= e($class['course_title']) ?> · <?= e($class['instructor_name']) ?></p>
            <p class="quiet"><?= e(pretty_datetime($class['starts_at'])) ?> · <?= (int) $class['duration_minutes'] ?> min</p>
            <p><?= e($class['description']) ?></p>
            <?php if ($currentUser): ?><a class="btn btn-small" href="/live/room/<?= (int) $class['id'] ?>">Open room</a><?php else: ?><a class="btn btn-small" href="/login?next=/live/room/<?= (int) $class['id'] ?>">Sign in to join</a><?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
<?php if (!$classes): ?><div class="empty">No live classes on the calendar.</div><?php endif; ?>
