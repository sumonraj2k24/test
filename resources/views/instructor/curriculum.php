<div class="page-head">
    <div><p class="kicker"><?= status_badge($course['status']) ?></p><h1><?= e($course['title']) ?></h1></div>
    <a href="/studio/courses/<?= (int) $course['id'] ?>/edit">Details</a>
</div>
<?php foreach ($sections as $section): ?>
    <section class="board-card" style="margin-bottom:14px">
        <form method="post" action="/studio/sections/<?= (int) $section['id'] ?>" class="grid-2">
            <?= csrf_field() ?>
            <label class="field"><span>Section</span><input class="input" name="title" value="<?= e($section['title']) ?>"></label>
            <label class="field"><span>Drip days after enroll</span><input class="input" type="number" name="drip_days" value="<?= (int) $section['drip_days'] ?>" min="0"></label>
            <button class="btn btn-small" type="submit">Save section</button>
        </form>
        <form method="post" action="/studio/sections/<?= (int) $section['id'] ?>/delete" data-confirm="Delete this section and its lessons?"><?= csrf_field() ?><button class="btn btn-small btn-danger" type="submit">Delete section</button></form>
        <?php foreach ($section['lessons'] as $lesson): ?>
            <div class="lesson-row">
                <span><?= e(lesson_type_label($lesson['type'])) ?> · <?= e($lesson['title']) ?></span>
                <span class="stack">
                    <a href="?lesson=<?= (int) $lesson['id'] ?>">Edit</a>
                    <?php if ($lesson['type'] === 'quiz'):
                        $quizId = \App\Core\Database::value('SELECT id FROM quizzes WHERE lesson_id = ?', [(int) $lesson['id']]);
                    ?>
                        <a href="/studio/quizzes/<?= (int) $quizId ?>">Quiz</a>
                    <?php endif; ?>
                    <?php if ($lesson['type'] === 'assignment'):
                        $aid = \App\Core\Database::value('SELECT id FROM assignments WHERE lesson_id = ?', [(int) $lesson['id']]);
                    ?>
                        <a href="/studio/assignments/<?= (int) $aid ?>">Submissions</a>
                    <?php endif; ?>
                    <form class="inline-form" method="post" action="/studio/lessons/<?= (int) $lesson['id'] ?>/move"><?= csrf_field() ?><input type="hidden" name="direction" value="up"><button class="btn btn-small btn-ghost">Up</button></form>
                    <form class="inline-form" method="post" action="/studio/lessons/<?= (int) $lesson['id'] ?>/move"><?= csrf_field() ?><input type="hidden" name="direction" value="down"><button class="btn btn-small btn-ghost">Down</button></form>
                </span>
            </div>
            <?php if ((int) $editLesson === (int) $lesson['id']): ?>
                <form method="post" action="/studio/lessons/<?= (int) $lesson['id'] ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <label class="field"><span>Title</span><input class="input" name="title" value="<?= e($lesson['title']) ?>"></label>
                    <label class="field"><span>Type</span>
                        <select class="select" name="type"><?php foreach (['article','video','document','quiz','assignment','live'] as $type): ?><option value="<?= $type ?>" <?= selected($lesson['type'], $type) ?>><?= e(lesson_type_label($type)) ?></option><?php endforeach; ?></select>
                    </label>
                    <label class="field"><span>Summary</span><input class="input" name="summary" value="<?= e($lesson['summary']) ?>"></label>
                    <label class="field"><span>Content or instructions (markdown)</span><textarea class="textarea" name="content"><?= e($lesson['content']) ?></textarea></label>
                    <label class="field"><span>Video URL</span><input class="input" name="video_url" value="<?= e($lesson['video_url']) ?>"></label>
                    <label class="field"><span>Attachment</span><input class="input" type="file" name="attachment"></label>
                    <div class="grid-2">
                        <label class="field"><span>Minutes</span><input class="input" type="number" name="duration_minutes" value="<?= (int) $lesson['duration_minutes'] ?>"></label>
                        <label class="field"><span>Unlock at (UTC, optional)</span><input class="input" name="drip_at" value="<?= e($lesson['drip_at']) ?>" placeholder="2026-10-01 15:00:00"></label>
                    </div>
                    <label class="check-row"><input type="checkbox" name="is_preview" value="1" <?= checked((int) $lesson['is_preview']) ?>> Free preview</label>
                    <button class="btn btn-small" type="submit">Save lesson</button>
                </form>
                <form method="post" action="/studio/lessons/<?= (int) $lesson['id'] ?>/delete" data-confirm="Delete this lesson?"><?= csrf_field() ?><button class="btn btn-small btn-danger">Delete lesson</button></form>
            <?php endif; ?>
        <?php endforeach; ?>
        <details>
            <summary>Add a lesson</summary>
            <form method="post" action="/studio/sections/<?= (int) $section['id'] ?>/lessons" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <label class="field"><span>Title</span><input class="input" name="title" required></label>
                <label class="field"><span>Type</span>
                    <select class="select" name="type"><?php foreach (['article','video','document','quiz','assignment','live'] as $type): ?><option value="<?= $type ?>"><?= e(lesson_type_label($type)) ?></option><?php endforeach; ?></select>
                </label>
                <label class="field"><span>Content</span><textarea class="textarea" name="content"></textarea></label>
                <label class="field"><span>Video URL</span><input class="input" name="video_url"></label>
                <label class="field"><span>File</span><input class="input" type="file" name="attachment"></label>
                <label class="field"><span>Minutes</span><input class="input" type="number" name="duration_minutes" value="10"></label>
                <label class="check-row"><input type="checkbox" name="is_preview" value="1"> Preview</label>
                <button class="btn btn-small" type="submit">Add lesson</button>
            </form>
        </details>
    </section>
<?php endforeach; ?>
<form method="post" action="/studio/courses/<?= (int) $course['id'] ?>/sections" class="board-card">
    <?= csrf_field() ?>
    <h2>New section</h2>
    <label class="field"><span>Title</span><input class="input" name="title" required></label>
    <label class="field"><span>Drip days</span><input class="input" type="number" name="drip_days" value="0" min="0"></label>
    <button class="btn" type="submit">Add section</button>
</form>
