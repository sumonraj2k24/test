<div class="page-head"><div><p class="kicker">Admin</p><h1>The studio floor</h1></div></div>
<?php if ($updates): ?><div class="notice"><?= count($updates) ?> update waiting. <a href="/admin/updates">Review <?= e($updates[0]['version']) ?></a></div><?php endif; ?>
<div class="stats">
    <div class="stat"><b><?= money((int) $stats['revenue']) ?></b><span>Paid revenue</span></div>
    <div class="stat"><b><?= (int) $stats['enrollments'] ?></b><span>Enrollments</span></div>
    <div class="stat"><b><?= (int) $stats['pending_courses'] ?></b><span>Courses in review</span></div>
    <div class="stat"><b><?= (int) $stats['pending_applications'] ?></b><span>Instructor applications</span></div>
</div>
<div class="grid-3">
    <div>
        <h2>Waiting courses</h2>
        <?php foreach ($pendingCourses as $course): ?><p><a href="/admin/courses?status=pending"><?= e($course['title']) ?></a> · <?= e($course['instructor_name']) ?></p><?php endforeach; ?>
        <h2>Applications</h2>
        <?php foreach ($applications as $app): ?><p><a href="/admin/applications"><?= e($app['name']) ?></a> · <?= e($app['expertise']) ?></p><?php endforeach; ?>
    </div>
    <div>
        <h2>Recent audit</h2>
        <?php foreach ($audit as $row): ?><p class="quiet"><?= e($row['action']) ?> · <?= e($row['name'] ?? 'system') ?> · <?= e(pretty_date($row['created_at'])) ?></p><?php endforeach; ?>
    </div>
</div>
