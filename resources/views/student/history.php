<div class="page-head"><div><p class="kicker">History</p><h1>Recently opened</h1></div></div>
<div class="table-wrap">
<table>
    <thead><tr><th>When</th><th>Lesson</th><th>Course</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($history as $row): ?>
        <tr>
            <td><?= e(pretty_datetime($row['last_watched_at'])) ?></td>
            <td><?= e($row['lesson_title']) ?> <?= (int) $row['completed'] ? '· done' : '' ?></td>
            <td><?= e($row['course_title']) ?></td>
            <td><a href="/learn/<?= e($row['course_slug']) ?>/lesson/<?= (int) $row['lesson_id'] ?>">Open</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php if (!$history): ?><div class="empty">No watch history yet.</div><?php endif; ?>
