<div class="page-head"><div><p class="kicker"><?= (int) $count ?> active</p><h1>Newsletter</h1></div></div>
<form method="post" action="/admin/newsletter" class="board-card">
    <?= csrf_field() ?>
    <label class="field"><span>Subject</span><input class="input" name="subject" required></label>
    <label class="field"><span>Body (markdown)</span><textarea class="textarea" name="body" required></textarea></label>
    <button class="btn" type="submit">Send to subscribers</button>
</form>
<h2>Campaigns</h2>
<?php foreach ($campaigns as $campaign): ?><p><?= e($campaign['subject']) ?> · <?= (int) $campaign['recipients_count'] ?> · <?= e(pretty_datetime($campaign['sent_at'])) ?></p><?php endforeach; ?>
<h2>Mail log</h2>
<ul><?php foreach ($mailLog as $file): ?><li><?= e($file['name']) ?></li><?php endforeach; ?></ul>
<h2>Subscribers</h2>
<ul><?php foreach ($subscribers as $subscriber): ?><li><?= e($subscriber['email']) ?> · <?= e($subscriber['status']) ?></li><?php endforeach; ?></ul>
