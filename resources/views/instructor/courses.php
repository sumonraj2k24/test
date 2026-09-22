<div class="page-head"><div><p class="kicker">Mode · <?= e($mode) ?></p><h1>Courses</h1></div><a class="btn" href="/studio/courses/create">New course</a></div>
<p class="quiet"><?= $mode === 'centralized' ? 'Publishing and published edits return to the admin queue.' : 'Collaborators can be invited. Publishing follows the approval setting.' ?></p>
<div class="table-wrap"><table>
    <thead><tr><th>Course</th><th>Status</th><th>Students</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($courses as $course): ?>
        <tr>
            <td><?= e($course['title']) ?><div class="quiet"><?= e($course['category_name'] ?? '') ?></div></td>
            <td><?= status_badge($course['status']) ?><?php if ($course['rejection_note']): ?><div class="quiet"><?= e($course['rejection_note']) ?></div><?php endif; ?></td>
            <td><?= (int) $course['students_count'] ?></td>
            <td class="stack"><a href="/studio/courses/<?= (int) $course['id'] ?>/edit">Details</a><a href="/studio/courses/<?= (int) $course['id'] ?>/curriculum">Curriculum</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
