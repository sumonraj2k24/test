<p class="kicker">Verification</p>
<h1>Certificate <?= e($code) ?></h1>
<?php if (!$certificate): ?>
    <div class="empty">No certificate uses that code.</div>
<?php else: ?>
    <div class="board-card">
        <strong><?= e($certificate['student_name']) ?></strong>
        <p>completed <?= e($certificate['course_title']) ?> on <?= e(pretty_date($certificate['completed_at'])) ?>.</p>
        <p class="quiet">Instructor <?= e($certificate['instructor_name']) ?></p>
        <a class="btn btn-small" href="/certificates/<?= e($certificate['certificate_code']) ?>">Open certificate</a>
    </div>
<?php endif; ?>
