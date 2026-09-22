<a class="course-card" href="/courses/<?= e($course['slug']) ?>">
    <div class="cover">
        <?php if (!empty($course['thumbnail'])): ?>
            <img src="<?= e($course['thumbnail']) ?>" alt="">
        <?php else: ?>
            <div class="swatch" style="background: <?= e($course['category_accent'] ?? '#1f4f45') ?>"></div>
        <?php endif; ?>
    </div>
    <div class="course-body">
        <div class="cat"><?= e($course['category_name'] ?? 'Studio') ?> · <?= e(course_level_label($course['level'])) ?></div>
        <h3><?= e($course['title']) ?></h3>
        <div class="sub"><?= e($course['subtitle']) ?></div>
        <div class="course-foot">
            <span><?= stars((float) $course['rating_avg']) ?> <span class="quiet"><?= (int) $course['rating_count'] ?></span></span>
            <span class="price"><?= (int) $course['price_cents'] === 0 ? 'Free' : money((int) $course['price_cents']) ?></span>
        </div>
        <div class="quiet"><?= e($course['instructor_name'] ?? '') ?></div>
    </div>
</a>
