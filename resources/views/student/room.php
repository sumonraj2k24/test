<p class="kicker"><?= e($class['course_title']) ?></p>
<h1><?= e($class['title']) ?></h1>
<p><?= e($class['instructor_name']) ?> · <?= e(pretty_datetime($class['starts_at'])) ?> · <?= (int) $class['duration_minutes'] ?> minutes</p>
<div class="prose"><?= md($class['description']) ?></div>
<?php if (!$attending): ?>
    <form method="post" action="/live/room/<?= (int) $class['id'] ?>/attend"><?= csrf_field() ?><button class="btn" type="submit">Check in</button></form>
<?php else: ?>
    <div class="notice">Checked in <?= e(pretty_datetime($attending['joined_at'])) ?>. If this lesson is linked, it is marked complete.</div>
<?php endif; ?>
<div class="stack" style="margin-top:12px">
    <?php if ($class['zoom_join_url']): ?><a class="btn" href="<?= e($class['zoom_join_url']) ?>" target="_blank" rel="noopener">Open Zoom</a><?php else: ?><span class="quiet">No Zoom URL yet. The in-app room is the class until one is pasted or created via the API.</span><?php endif; ?>
    <?php if ($class['zoom_start_url'] && ((int) $class['instructor_id'] === (int) ($currentUser['id'] ?? 0) || \App\Core\Auth::can('live.manage'))): ?>
        <a class="btn btn-line" href="<?= e($class['zoom_start_url']) ?>" target="_blank" rel="noopener">Start as host</a>
    <?php endif; ?>
    <a href="/courses/<?= e($class['course_slug']) ?>/discussions">Ask on the forum</a>
</div>
