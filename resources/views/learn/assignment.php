<div class="player">
    <header class="player-top"><a href="/learn/<?= e($course['slug']) ?>/lesson/<?= (int) $lesson['id'] ?>">← <?= e($lesson['title']) ?></a></header>
    <article class="lesson-main">
        <?php partial('partials/flash'); ?>
        <p class="kicker">Assignment<?php if ($dueAt): ?> · due <?= e(pretty_datetime($dueAt)) ?><?php endif; ?></p>
        <h1><?= e($assignment['title']) ?></h1>
        <div class="prose"><?= md($assignment['instructions']) ?></div>
        <?php if ($submission): ?>
            <div class="notice">Submitted <?= e(pretty_datetime($submission['submitted_at'])) ?> · <?= e($submission['status']) ?>
                <?php if ($submission['score'] !== null): ?> · <?= (int) $submission['score'] ?>/<?= (int) $assignment['max_score'] ?><?php endif; ?>
            </div>
            <?php if ($submission['feedback']): ?><blockquote class="quote"><p><?= e($submission['feedback']) ?></p></blockquote><?php endif; ?>
        <?php endif; ?>
        <form method="post" action="/learn/<?= e($course['slug']) ?>/assignment/<?= (int) $lesson['id'] ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <label class="field"><span>Your work</span><textarea class="textarea" name="content" required minlength="20"><?= e($submission['content'] ?? '') ?></textarea></label>
            <label class="field"><span>Attachment, optional</span><input class="input" type="file" name="attachment"></label>
            <button class="btn" type="submit"><?= $submission ? 'Update submission' : 'Submit' ?></button>
        </form>
    </article>
</div>
