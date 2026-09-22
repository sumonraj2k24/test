<p class="kicker">Teach</p>
<h1>Apply to instruct</h1>
<p class="lede">Tell the studio what you practice and how you would teach it. An administrator reads every application.</p>
<?php if ($application): ?>
    <div class="notice">Latest application: <?= e($application['status']) ?><?php if ($application['review_note']): ?> — <?= e($application['review_note']) ?><?php endif; ?></div>
<?php endif; ?>
<?php if ($currentUser && ($currentUser['role'] ?? '') !== 'instructor'): ?>
<form method="post" action="/teach" style="max-width:680px">
    <?= csrf_field() ?>
    <label class="field"><span>Expertise</span><input class="input" name="expertise" required></label>
    <label class="field"><span>Years teaching or practicing</span><input class="input" type="number" name="years_experience" min="0" required></label>
    <label class="field"><span>Sample URL</span><input class="input" name="sample_url" placeholder="https://"></label>
    <label class="field"><span>How you would teach</span><textarea class="textarea" name="statement" required minlength="40"></textarea></label>
    <button class="btn" type="submit">Submit application</button>
</form>
<?php elseif (!$currentUser): ?>
    <a class="btn" href="/login?next=/teach">Sign in to apply</a>
<?php else: ?>
    <a class="btn" href="/studio">Open the studio</a>
<?php endif; ?>
