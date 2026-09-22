<div class="page-head"><div><p class="kicker"><?= $zoomReady ? 'Zoom connected' : 'Zoom not configured' ?></p><h1>Live classes</h1></div></div>
<form method="post" action="/studio/live" class="board-card" style="margin-bottom:16px">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label class="field"><span>Course</span><select class="select" name="course_id"><?php foreach ($courses as $course): ?><option value="<?= (int) $course['id'] ?>"><?= e($course['title']) ?></option><?php endforeach; ?></select></label>
        <label class="field"><span>Title</span><input class="input" name="title" required></label>
        <label class="field"><span>Starts (UTC)</span><input class="input" type="datetime-local" name="starts_at" required></label>
        <label class="field"><span>Minutes</span><input class="input" type="number" name="duration_minutes" value="60"></label>
    </div>
    <label class="field"><span>Description</span><textarea class="textarea" name="description"></textarea></label>
    <label class="field"><span>Manual Zoom join URL</span><input class="input" name="zoom_join_url" placeholder="https://zoom.us/j/..."></label>
    <label class="check-row"><input type="checkbox" name="create_zoom" value="1"> Create the meeting with the Zoom API</label>
    <button class="btn" type="submit">Schedule</button>
</form>
<?php foreach ($classes as $class): ?>
    <div class="lesson-row">
        <span><strong><?= e($class['title']) ?></strong> · <?= e($class['course_title']) ?><br><span class="quiet"><?= e(pretty_datetime($class['starts_at'])) ?> · <?= (int) $class['attendance'] ?> checked in · <?= e($class['status']) ?></span></span>
        <span class="stack">
            <a href="/live/room/<?= (int) $class['id'] ?>">Room</a>
            <?php if ($class['status'] !== 'cancelled'): ?>
                <form method="post" action="/studio/live/<?= (int) $class['id'] ?>/cancel"><?= csrf_field() ?><button class="btn btn-small btn-danger">Cancel</button></form>
            <?php endif; ?>
        </span>
    </div>
<?php endforeach; ?>
