<div class="page-head"><div><p class="kicker">Saved</p><h1>Wishlist</h1></div></div>
<div class="course-grid">
    <?php foreach ($courses as $course): partial('partials/course-card', ['course' => $course]); endforeach; ?>
</div>
<?php if (!$courses): ?><div class="empty">Nothing saved.</div><?php endif; ?>
