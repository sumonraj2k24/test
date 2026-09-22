<p class="quiet"><?= e($assignment['course_title']) ?></p>
<h1><?= e($assignment['title']) ?></h1>
<?php foreach ($submissions as $submission): ?>
    <article class="board-card" style="margin-bottom:12px">
        <strong><?= e($submission['name']) ?></strong> <?= status_badge($submission['status']) ?>
        <p><?= nl2br(e($submission['content'])) ?></p>
        <?php if ($submission['file_path']): ?><p><a href="<?= e($submission['file_path']) ?>">Attachment</a></p><?php endif; ?>
        <form method="post" action="/studio/submissions/<?= (int) $submission['id'] ?>/grade">
            <?= csrf_field() ?>
            <label class="field"><span>Score</span><input class="input" type="number" name="score" min="0" max="100" value="<?= e((string) ($submission['score'] ?? '')) ?>"></label>
            <label class="field"><span>Feedback</span><textarea class="textarea" name="feedback"><?= e($submission['feedback']) ?></textarea></label>
            <button class="btn btn-small" type="submit">Save grade</button>
        </form>
    </article>
<?php endforeach; ?>
<?php if (!$submissions): ?><div class="empty">No submissions yet.</div><?php endif; ?>
