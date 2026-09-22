<div class="page-head"><div><p class="kicker">Studio</p><h1>Good to see the work.</h1></div><a class="btn" href="/studio/courses/create">New course</a></div>
<div class="stats">
    <div class="stat"><b><?= (int) $stats['courses'] ?></b><span>Courses</span></div>
    <div class="stat"><b><?= (int) $stats['students'] ?></b><span>Students</span></div>
    <div class="stat"><b><?= money((int) $stats['available']) ?></b><span>Available to pay out</span></div>
    <div class="stat"><b><?= (int) $stats['completion'] ?>%</b><span>Completion</span></div>
</div>
<div class="grid-3">
    <div>
        <h2>Courses</h2>
        <?php foreach ($courses as $course): ?>
            <a class="lesson-row" href="/studio/courses/<?= (int) $course['id'] ?>/curriculum"><span><?= e($course['title']) ?></span><?= status_badge($course['status']) ?></a>
        <?php endforeach; ?>
        <h2 style="margin-top:22px">Waiting to grade</h2>
        <?php foreach ($submissions as $row): ?>
            <a class="lesson-row" href="/studio/assignments/<?= (int) $row['assignment_id'] ?>"><span><?= e($row['student_name']) ?> · <?= e($row['title']) ?></span></a>
        <?php endforeach; ?>
        <?php if (!$submissions): ?><p class="quiet">No ungraded submissions.</p><?php endif; ?>
    </div>
    <div>
        <h2>Upcoming</h2>
        <?php foreach ($upcoming as $class): ?>
            <div class="board-card" style="margin-bottom:8px"><strong><?= e($class['title']) ?></strong><p class="quiet"><?= e(pretty_datetime($class['starts_at'])) ?></p></div>
        <?php endforeach; ?>
        <?php if (!$upcoming): ?><p class="quiet">Nothing scheduled. <a href="/studio/live">Add a live class</a>.</p><?php endif; ?>
    </div>
</div>
