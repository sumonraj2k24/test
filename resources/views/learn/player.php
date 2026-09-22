<?php
$doneCount = 0; $total = count($flat);
foreach ($progressMap as $row) { if ((int) $row['completed'] === 1) $doneCount++; }
$percent = $enrollment['progress_percent'] ?? ($total ? (int) floor($doneCount / $total * 100) : 0);
?>
<div class="player">
    <header class="player-top">
        <a href="/courses/<?= e($course['slug']) ?>">← <?= e($course['title']) ?></a>
        <div style="display:flex;align-items:center;gap:10px">
            <span><?= (int) $percent ?>%</span>
            <div class="progress-track"><i style="width:<?= (int) $percent ?>%"></i></div>
        </div>
    </header>
    <div class="player-body">
        <aside class="curriculum-side">
            <?php $last = null; foreach ($sections as $section): ?>
                <div class="sec"><?= e($section['title']) ?><?php if ((int) $section['drip_days']): ?> · drip <?= (int) $section['drip_days'] ?>d<?php endif; ?></div>
                <?php foreach ($section['lessons'] as $item):
                    $state = $progressMap[(int) $item['id']] ?? null;
                    $current = (int) $item['id'] === (int) $lesson['id'];
                ?>
                    <a class="<?= $current ? 'current' : '' ?>" href="/learn/<?= e($course['slug']) ?>/lesson/<?= (int) $item['id'] ?>">
                        <span><?= ($state && (int) $state['completed']) ? '✓' : '·' ?></span>
                        <span><?= e($item['title']) ?></span>
                        <span class="quiet"><?= e(lesson_type_label($item['type'])) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </aside>
        <article class="lesson-main">
            <?php partial('partials/flash'); ?>
            <p class="kicker"><?= e($lesson['section_title'] ?? '') ?> · <?= e(lesson_type_label($lesson['type'])) ?></p>
            <h1><?= e($lesson['title']) ?></h1>
            <?php if ($lesson['summary']): ?><p class="lede"><?= e($lesson['summary']) ?></p><?php endif; ?>
            <?php if ($lesson['type'] === 'video'): ?>
                <?= video_embed($lesson['video_url']) ?>
                <?php if (!$lesson['video_url']): ?><div class="notice">No recording URL yet. Paste a YouTube, Vimeo, or MP4 link in the curriculum builder.</div><?php endif; ?>
            <?php endif; ?>
            <?php if ($lesson['type'] === 'live' && !empty($live)): ?>
                <div class="board-card">
                    <strong><?= e($live['title']) ?></strong>
                    <p><?= e(pretty_datetime($live['starts_at'])) ?> · <?= (int) $live['duration_minutes'] ?> min</p>
                    <p><a class="btn btn-small" href="/live/room/<?= (int) $live['id'] ?>">Enter the room</a>
                    <?php if ($live['zoom_join_url']): ?><a class="btn btn-small btn-line" href="<?= e($live['zoom_join_url']) ?>" target="_blank" rel="noopener">Open Zoom</a><?php endif; ?></p>
                </div>
            <?php endif; ?>
            <div class="prose"><?= md($lesson['content']) ?></div>
            <?php if ($lesson['attachment_path']): ?><p><a class="btn btn-small btn-line" href="<?= e($lesson['attachment_path']) ?>">Download attachment</a></p><?php endif; ?>
            <?php if ($lesson['type'] === 'quiz'): ?><p><a class="btn" href="/learn/<?= e($course['slug']) ?>/quiz/<?= (int) $lesson['id'] ?>">Open quiz</a></p><?php endif; ?>
            <?php if ($lesson['type'] === 'assignment'): ?><p><a class="btn" href="/learn/<?= e($course['slug']) ?>/assignment/<?= (int) $lesson['id'] ?>">Open assignment</a></p><?php endif; ?>
            <?php if ($notesReady): ?>
                <form class="note-box" method="post" action="/learn/<?= e($course['slug']) ?>/lesson/<?= (int) $lesson['id'] ?>/note">
                    <?= csrf_field() ?>
                    <label class="field"><span>Private note</span><textarea class="textarea" name="body"><?= e($note['body'] ?? '') ?></textarea></label>
                    <button class="btn btn-small" type="submit">Save note</button>
                </form>
            <?php else: ?>
                <div class="notice">Private lesson notes ship in update 1.1.0. An administrator can apply it under System updates.</div>
            <?php endif; ?>
            <div class="player-nav">
                <?php if ($neighbors['prev']): ?><a class="btn btn-line" href="/learn/<?= e($course['slug']) ?>/lesson/<?= (int) $neighbors['prev']['id'] ?>">Previous</a><?php else: ?><span></span><?php endif; ?>
                <?php if ($enrollment && !in_array($lesson['type'], ['quiz', 'assignment'], true)): ?>
                    <form method="post" action="/learn/<?= e($course['slug']) ?>/lesson/<?= (int) $lesson['id'] ?>/complete"><?= csrf_field() ?><button class="btn" type="submit">Mark complete</button></form>
                <?php endif; ?>
                <?php if ($neighbors['next']): ?><a class="btn btn-ghost" href="/learn/<?= e($course['slug']) ?>/lesson/<?= (int) $neighbors['next']['id'] ?>">Next</a><?php endif; ?>
            </div>
        </article>
    </div>
</div>
