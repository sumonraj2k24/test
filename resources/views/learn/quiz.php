<?php
$used = count($attempts);
$left = (int) $quiz['attempts_allowed'] === 0 ? null : max(0, (int) $quiz['attempts_allowed'] - $used);
$passed = false;
foreach ($attempts as $attempt) { if ((int) $attempt['passed'] === 1) $passed = true; }
$showAnswers = $passed || ($left === 0);
$latest = $attempts[0] ?? null;
$ends = ((int) $quiz['time_limit_minutes'] > 0) ? (strtotime($startedAt . ' UTC') + ((int) $quiz['time_limit_minutes'] * 60)) : 0;
?>
<div class="player">
    <header class="player-top">
        <a href="/learn/<?= e($course['slug']) ?>/lesson/<?= (int) $lesson['id'] ?>">← <?= e($lesson['title']) ?></a>
        <?php if ($ends): ?><span data-timer data-ends="<?= (int) $ends ?>">--:--</span><?php endif; ?>
    </header>
    <article class="lesson-main" style="max-width:760px">
        <?php partial('partials/flash'); ?>
        <p class="kicker">Quiz · pass <?= (int) $quiz['pass_percent'] ?>% · <?= $left === null ? 'unlimited attempts' : $left . ' attempts left' ?></p>
        <h1><?= e($quiz['title']) ?></h1>
        <?php if ($quiz['description']): ?><p><?= e($quiz['description']) ?></p><?php endif; ?>
        <?php if ($latest): ?>
            <div class="notice">Last score <?= (int) $latest['score_percent'] ?>% · <?= (int) $latest['passed'] ? 'passed' : 'not passed' ?> · <?= e(pretty_datetime($latest['submitted_at'])) ?></div>
        <?php endif; ?>
        <?php if ($left === 0 && !$passed): ?>
            <p>No attempts remain.</p>
        <?php elseif (!$passed): ?>
            <form method="post" action="/learn/<?= e($course['slug']) ?>/quiz/<?= (int) $lesson['id'] ?>">
                <?= csrf_field() ?>
                <?php foreach ($questions as $index => $question):
                    $options = json_decode((string) $question['options_json'], true) ?: [];
                ?>
                    <fieldset class="board-card" style="margin:12px 0">
                        <legend><strong><?= $index + 1 ?>. <?= e($question['question']) ?></strong></legend>
                        <?php if ($question['type'] === 'text'): ?>
                            <input class="input" name="answers[<?= (int) $question['id'] ?>]" required>
                        <?php else: ?>
                            <?php foreach ($options as $option): ?>
                                <label class="check-row">
                                    <input type="<?= $question['type'] === 'multi' ? 'checkbox' : 'radio' ?>" name="answers[<?= (int) $question['id'] ?>]<?= $question['type'] === 'multi' ? '[]' : '' ?>" value="<?= e($option['id']) ?>" <?= $question['type'] === 'single' ? 'required' : '' ?>>
                                    <?= e($option['text']) ?>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </fieldset>
                <?php endforeach; ?>
                <button class="btn" type="submit">Submit quiz</button>
            </form>
        <?php endif; ?>
        <?php if ($showAnswers && $latest): ?>
            <h2>Answers</h2>
            <?php $detail = json_decode((string) $latest['answers_json'], true)['detail'] ?? []; ?>
            <?php foreach ($questions as $question):
                $row = null;
                foreach ($detail as $item) { if ((int) $item['id'] === (int) $question['id']) $row = $item; }
            ?>
                <p><?= e($question['question']) ?> — <?= ($row['correct'] ?? false) ? 'correct' : 'review this one' ?></p>
            <?php endforeach; ?>
        <?php endif; ?>
    </article>
</div>
