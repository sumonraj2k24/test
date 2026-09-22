<div class="player">
    <header class="player-top"><a href="/courses/<?= e($course['slug']) ?>">← <?= e($course['title']) ?></a><span>Locked</span></header>
    <div class="player-body">
        <aside class="curriculum-side">
            <?php foreach ($sections as $section): ?>
                <div class="sec"><?= e($section['title']) ?></div>
                <?php foreach ($section['lessons'] as $item): ?>
                    <a href="/learn/<?= e($course['slug']) ?>/lesson/<?= (int) $item['id'] ?>"><span>·</span><span><?= e($item['title']) ?></span><span></span></a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </aside>
        <article class="lesson-main">
            <p class="kicker">Not yet</p>
            <h1><?= e($lesson['title']) ?></h1>
            <p class="lede"><?= e($access['reason']) ?></p>
            <?php if (!$enrollment): ?><a class="btn" href="/courses/<?= e($course['slug']) ?>">View enrollment</a><?php endif; ?>
        </article>
    </div>
</div>
