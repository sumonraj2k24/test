<p class="quiet"><a href="/courses/<?= e($course['slug']) ?>/discussions">Discussion</a></p>
<h1><?= e($thread['title']) ?></h1>
<p><?= nl2br(e($thread['body'])) ?></p>
<p class="quiet"><?= e($thread['author_name']) ?> · <?= e(pretty_datetime($thread['created_at'])) ?></p>
<?php if (is_course_staff($course)): ?>
    <form method="post" action="/discussions/<?= (int) $thread['id'] ?>/pin"><?= csrf_field() ?><button class="btn btn-small btn-ghost" type="submit"><?= (int) $thread['pinned'] ? 'Unpin' : 'Pin' ?></button></form>
<?php endif; ?>
<?php foreach ($posts as $post): ?>
    <div class="thread"><strong><?= e($post['author_name']) ?></strong> <span class="quiet"><?= e($post['role']) ?> · <?= e(pretty_datetime($post['created_at'])) ?></span><p><?= nl2br(e($post['body'])) ?></p></div>
<?php endforeach; ?>
<form method="post" action="/discussions/<?= (int) $thread['id'] ?>/reply">
    <?= csrf_field() ?>
    <label class="field"><span>Reply</span><textarea class="textarea" name="body" required></textarea></label>
    <button class="btn" type="submit">Reply</button>
</form>
