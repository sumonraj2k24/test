<div class="page-head"><div><p class="kicker">Performance</p><h1>Quizzes and assignments</h1></div></div>
<h2>Recent attempts</h2>
<div class="table-wrap"><table>
    <thead><tr><th>Student</th><th>Quiz</th><th>Course</th><th>Score</th><th>When</th></tr></thead>
    <tbody>
    <?php foreach ($attempts as $row): ?>
        <tr><td><?= e($row['student_name']) ?></td><td><?= e($row['quiz_title']) ?></td><td><?= e($row['course_title']) ?></td><td><?= (int) $row['score_percent'] ?>% <?= (int) $row['passed'] ? 'pass' : 'fail' ?></td><td><?= e(pretty_datetime($row['submitted_at'])) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<h2>Assignments</h2>
<?php foreach ($assignments as $row): ?>
    <a class="lesson-row" href="/studio/assignments/<?= (int) $row['id'] ?>"><span><?= e($row['title']) ?> · <?= e($row['course_title']) ?></span><span><?= (int) $row['waiting'] ?> waiting</span></a>
<?php endforeach; ?>
