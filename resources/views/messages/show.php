<p class="kicker"><?= e($other['role']) ?></p>
<h1><?= e($other['name']) ?></h1>
<p class="quiet"><?= e($other['headline']) ?></p>
<div style="margin:16px 0">
    <?php foreach ($messages as $message): ?>
        <div class="message <?= (int) $message['sender_id'] === (int) $currentUser['id'] ? 'mine' : '' ?>">
            <?php if ($message['subject']): ?><strong><?= e($message['subject']) ?></strong><?php endif; ?>
            <p><?= nl2br(e($message['body'])) ?></p>
            <span class="quiet"><?= e(pretty_datetime($message['created_at'])) ?></span>
        </div>
    <?php endforeach; ?>
</div>
<form method="post" action="/messages">
    <?= csrf_field() ?>
    <input type="hidden" name="recipient_id" value="<?= (int) $other['id'] ?>">
    <?php if ($courseId): ?><input type="hidden" name="course_id" value="<?= (int) $courseId ?>"><?php endif; ?>
    <label class="field"><span>Reply</span><textarea class="textarea" name="body" required></textarea></label>
    <button class="btn" type="submit">Send</button>
</form>
