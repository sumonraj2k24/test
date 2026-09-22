<div class="page-head"><div><p class="kicker">Teaching</p><h1>Applications</h1></div></div>
<?php foreach ($applications as $app): ?>
    <article class="board-card" style="margin-bottom:12px">
        <strong><?= e($app['name']) ?></strong> <?= status_badge($app['status']) ?>
        <p><?= e($app['email']) ?> · <?= e($app['expertise']) ?> · <?= (int) $app['years_experience'] ?> years</p>
        <p><?= nl2br(e($app['statement'])) ?></p>
        <?php if ($app['sample_url']): ?><p><a href="<?= e($app['sample_url']) ?>" rel="noopener"><?= e($app['sample_url']) ?></a></p><?php endif; ?>
        <?php if ($app['status'] === 'pending'): ?>
            <form method="post" action="/admin/applications/<?= (int) $app['id'] ?>">
                <?= csrf_field() ?>
                <label class="field"><span>Note</span><input class="input" name="review_note"></label>
                <button class="btn btn-small" name="decision" value="approved">Approve</button>
                <button class="btn btn-small btn-danger" name="decision" value="rejected">Reject</button>
            </form>
        <?php elseif ($app['review_note']): ?>
            <p class="quiet"><?= e($app['review_note']) ?></p>
        <?php endif; ?>
    </article>
<?php endforeach; ?>
