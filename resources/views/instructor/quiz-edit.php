<p class="quiet"><a href="/studio/courses/<?= (int) $quiz['course_id'] ?>/curriculum">Curriculum</a> · <?= e($quiz['course_title']) ?></p>
<h1><?= e($quiz['title']) ?></h1>
<form method="post" action="/studio/quizzes/<?= (int) $quiz['id'] ?>" class="grid-2">
    <?= csrf_field() ?>
    <label class="field"><span>Title</span><input class="input" name="title" value="<?= e($quiz['title']) ?>"></label>
    <label class="field"><span>Pass percent</span><input class="input" type="number" name="pass_percent" value="<?= (int) $quiz['pass_percent'] ?>"></label>
    <label class="field"><span>Time limit minutes (0 = none)</span><input class="input" type="number" name="time_limit_minutes" value="<?= (int) $quiz['time_limit_minutes'] ?>"></label>
    <label class="field"><span>Attempts (0 = unlimited)</span><input class="input" type="number" name="attempts_allowed" value="<?= (int) $quiz['attempts_allowed'] ?>"></label>
    <label class="field"><span>Description</span><textarea class="textarea" name="description"><?= e($quiz['description']) ?></textarea></label>
    <button class="btn" type="submit">Save quiz</button>
</form>
<h2>Questions</h2>
<?php foreach ($questions as $question): ?>
    <div class="lesson-row"><span><?= e($question['type']) ?> · <?= e($question['question']) ?></span>
        <form method="post" action="/studio/questions/<?= (int) $question['id'] ?>/delete"><?= csrf_field() ?><button class="btn btn-small btn-danger">Delete</button></form>
    </div>
<?php endforeach; ?>
<form method="post" action="/studio/quizzes/<?= (int) $quiz['id'] ?>/questions" class="board-card">
    <?= csrf_field() ?>
    <h3>Add a question</h3>
    <label class="field"><span>Prompt</span><input class="input" name="question" required></label>
    <label class="field"><span>Type</span><select class="select" name="type"><option value="single">Single</option><option value="multi">Multi</option><option value="text">Short text</option></select></label>
    <p class="quiet">For choice questions, fill options and tick the correct ones. For text, comma-separated accepted answers.</p>
    <?php for ($i = 0; $i < 4; $i++): ?>
        <label class="check-row"><input type="checkbox" name="correct[]" value="<?= $i ?>"><input class="input" name="options[]" placeholder="Option <?= $i + 1 ?>"></label>
    <?php endfor; ?>
    <label class="field"><span>Accepted text answers</span><input class="input" name="accepted" placeholder="decision, Decision"></label>
    <button class="btn" type="submit">Add question</button>
</form>
