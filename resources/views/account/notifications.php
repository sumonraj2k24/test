<div class="page-head">
    <div><p class="kicker">Inbox</p><h1>Notifications</h1></div>
    <form method="post" action="/notifications/read"><?= csrf_field() ?><button class="btn btn-small btn-ghost" type="submit">Mark all read</button></form>
</div>
<?php foreach ($notes as $note): ?>
    <form method="post" action="/notifications/read" class="lesson-row">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $note['id'] ?>">
        <button type="submit" style="background:none;border:0;text-align:left;cursor:pointer">
            <strong><?= e($note['title']) ?></strong><?= $note['read_at'] ? '' : ' · new' ?>
            <div class="quiet"><?= e($note['body']) ?></div>
        </button>
        <span class="quiet"><?= e(pretty_date($note['created_at'])) ?></span>
    </form>
<?php endforeach; ?>
<?php if (!$notes): ?><div class="empty">No notifications.</div><?php endif; ?>
