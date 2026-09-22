<h1>Contact inbox</h1>
<?php foreach ($messages as $message): ?>
    <article class="board-card" style="margin-bottom:10px">
        <strong><?= e($message['subject']) ?></strong> <?= $message['read_at'] ? '' : '· new' ?>
        <p><?= e($message['name']) ?> · <?= e($message['email']) ?> · <?= e(pretty_datetime($message['created_at'])) ?></p>
        <p><?= nl2br(e($message['body'])) ?></p>
        <?php if (!$message['read_at']): ?><form method="post" action="/admin/inbox/read"><?= csrf_field() ?><input type="hidden" name="read" value="<?= (int) $message['id'] ?>"><button class="btn btn-small btn-ghost">Mark read</button></form><?php endif; ?>
    </article>
<?php endforeach; ?>
