<div class="page-head"><div><p class="kicker">Inbox</p><h1>Messages</h1></div></div>
<?php foreach ($threads as $thread): ?>
    <a class="lesson-row" href="/messages/with/<?= (int) $thread['id'] ?>">
        <span><strong><?= e($thread['name']) ?></strong> <?= (int) $thread['unread'] ? '· ' . (int) $thread['unread'] . ' new' : '' ?><br><span class="quiet"><?= e(excerpt($thread['last_body'], 90)) ?></span></span>
        <span class="quiet"><?= e(pretty_date($thread['last_at'])) ?></span>
    </a>
<?php endforeach; ?>
<?php if (!$threads): ?><div class="empty">No messages. Open a course and write the instructor.</div><?php endif; ?>
