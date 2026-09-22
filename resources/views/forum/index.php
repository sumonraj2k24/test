<div class="page-head"><div><p class="kicker"><?= e($course['title']) ?></p><h1>Discussion</h1></div><a href="/learn/<?= e($course['slug']) ?>">Back to course</a></div>
<form method="post" action="/courses/<?= e($course['slug']) ?>/discussions" style="margin-bottom:18px">
    <?= csrf_field() ?>
    <label class="field"><span>Title</span><input class="input" name="title" required></label>
    <label class="field"><span>Opening note</span><textarea class="textarea" name="body" required></textarea></label>
    <button class="btn" type="submit">Open thread</button>
</form>
<?php foreach ($threads as $thread): ?>
    <a class="lesson-row" href="/courses/<?= e($course['slug']) ?>/discussions/<?= (int) $thread['id'] ?>">
        <span><?= (int) $thread['pinned'] ? 'Pinned · ' : '' ?><strong><?= e($thread['title']) ?></strong><br><span class="quiet"><?= e($thread['author_name']) ?> · <?= (int) $thread['replies'] ?> replies</span></span>
        <span class="quiet"><?= e(pretty_date($thread['updated_at'])) ?></span>
    </a>
<?php endforeach; ?>
