<div class="page-head"><div><p class="kicker">People</p><h1>Students</h1></div></div>
<form method="get" class="stack">
    <select class="select" name="course" onchange="this.form.submit()">
        <?php foreach ($courses as $course): ?><option value="<?= (int) $course['id'] ?>" <?= selected((string) $courseId, (string) $course['id']) ?>><?= e($course['title']) ?></option><?php endforeach; ?>
    </select>
</form>
<div class="table-wrap"><table>
    <thead><tr><th>Student</th><th>Progress</th><th>Quizzes passed</th><th>Enrolled</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['name']) ?><div class="quiet"><?= e($row['email']) ?></div></td>
            <td><?= (int) $row['progress_percent'] ?>%</td>
            <td><?= (int) $row['quizzes_passed'] ?></td>
            <td><?= e(pretty_date($row['enrolled_at'])) ?></td>
            <td><a href="/messages/with/<?= (int) $row['user_id'] ?>">Message</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
