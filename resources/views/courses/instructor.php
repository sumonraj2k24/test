<p class="kicker">Instructor</p>
<h1><?= e($instructor['name']) ?></h1>
<p class="lede"><?= e($instructor['headline']) ?></p>
<div class="prose"><?= md($instructor['bio']) ?></div>
<div class="course-grid" style="margin-top:22px">
    <?php foreach ($courses as $course): partial('partials/course-card', ['course' => $course]); endforeach; ?>
</div>
<?php if (!$courses): ?><div class="empty">No published courses yet.</div><?php endif; ?>
