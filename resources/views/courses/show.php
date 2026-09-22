<article class="course-hero">
    <div>
        <p class="kicker"><?= e($course['category_name'] ?? 'Course') ?> · <?= e(course_level_label($course['level'])) ?></p>
        <h1><?= e($course['title']) ?></h1>
        <p class="lede"><?= e($course['subtitle']) ?></p>
        <p class="quiet"><?= stars((float) $course['rating_avg']) ?> <?= e(number_format((float) $course['rating_avg'], 1)) ?> · <?= (int) $course['rating_count'] ?> reviews · <?= (int) $course['students_count'] ?> students · <?= (int) $course['duration_minutes'] ?> min</p>
        <?php if ($course['status'] !== 'published'): ?><p><?= status_badge($course['status']) ?> Not public.</p><?php endif; ?>
    </div>
    <aside class="buybox">
        <?php if (!empty($course['thumbnail'])): ?><img src="<?= e($course['thumbnail']) ?>" alt="" style="margin-bottom:12px"><?php endif; ?>
        <div class="price">
            <?php if ($course['compare_cents']): ?><s><?= money((int) $course['compare_cents']) ?></s><?php endif; ?>
            <?= (int) $course['price_cents'] === 0 ? 'Free' : money((int) $course['price_cents']) ?>
        </div>
        <div class="stack" style="margin-top:12px">
            <?php if ($enrollment): ?>
                <a class="btn" href="/learn/<?= e($course['slug']) ?>">Continue · <?= (int) $enrollment['progress_percent'] ?>%</a>
            <?php elseif ((int) $course['price_cents'] === 0 && $currentUser): ?>
                <form method="post" action="/courses/<?= e($course['slug']) ?>/enroll"><?= csrf_field() ?><button class="btn" type="submit">Enroll free</button></form>
            <?php elseif ((int) $course['price_cents'] === 0): ?>
                <a class="btn" href="/login?next=/courses/<?= e($course['slug']) ?>">Sign in to enroll</a>
            <?php else: ?>
                <form method="post" action="/cart"><?= csrf_field() ?><input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>"><input type="hidden" name="next" value="/checkout"><button class="btn" type="submit"><?= $inCart ? 'In cart · checkout' : 'Buy now' ?></button></form>
                <?php if (!$inCart): ?>
                    <form method="post" action="/cart"><?= csrf_field() ?><input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>"><input type="hidden" name="next" value="/cart"><button class="btn btn-line" type="submit">Add to cart</button></form>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($currentUser && !$enrollment): ?>
                <form method="post" action="/wishlist"><?= csrf_field() ?><input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>"><button class="btn btn-ghost" type="submit"><?= $wished ? 'Wishlisted' : 'Wishlist' ?></button></form>
            <?php endif; ?>
        </div>
        <p class="quiet" style="margin-top:12px">Taught by <a href="/instructors/<?= (int) $course['instructor_id'] ?>"><?= e($course['instructor_name']) ?></a></p>
        <?php if ($currentUser): ?>
            <p><a href="/messages/with/<?= (int) $course['instructor_id'] ?>?course=<?= (int) $course['id'] ?>">Message the instructor</a></p>
        <?php endif; ?>
        <p><a href="/courses/<?= e($course['slug']) ?>/discussions">Discussion</a></p>
    </aside>
</article>

<div class="grid-3">
    <div class="prose">
        <?= md($course['description']) ?>
        <?php if ($outcomes): ?>
            <h2>You will be able to</h2>
            <ul><?php foreach ($outcomes as $line): ?><li><?= e($line) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <?php if ($requirements): ?>
            <h2>Bring this</h2>
            <ul><?php foreach ($requirements as $line): ?><li><?= e($line) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <h2>Curriculum</h2>
        <div class="curriculum">
            <?php foreach ($sections as $section): ?>
                <details open>
                    <summary><?= e($section['title']) ?><?php if ((int) $section['drip_days'] > 0): ?> · opens <?= (int) $section['drip_days'] ?> days after enroll<?php endif; ?></summary>
                    <?php foreach ($section['lessons'] as $lesson): ?>
                        <?php if ((int) $lesson['is_preview'] === 1 || $enrollment || is_course_staff($course)): ?>
                            <a class="lesson-row" href="/learn/<?= e($course['slug']) ?>/lesson/<?= (int) $lesson['id'] ?>"><span><?= e(lesson_type_label($lesson['type'])) ?> · <?= e($lesson['title']) ?></span><span><?= (int) $lesson['duration_minutes'] ?>m</span></a>
                        <?php else: ?>
                            <div class="lesson-row"><span><?= e(lesson_type_label($lesson['type'])) ?> · <?= e($lesson['title']) ?></span><span><?= (int) $lesson['duration_minutes'] ?>m</span></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </details>
            <?php endforeach; ?>
        </div>
        <h2 id="reviews">Reviews</h2>
        <?php if (!$reviews): ?><p class="quiet">No reviews yet.</p><?php endif; ?>
        <?php foreach ($reviews as $review): ?>
            <div class="thread">
                <strong><?= e($review['student_name']) ?></strong> <?= stars((float) $review['rating']) ?>
                <p><?= e($review['comment']) ?></p>
            </div>
        <?php endforeach; ?>
        <?php if ($enrollment): ?>
            <form method="post" action="/courses/<?= e($course['slug']) ?>/reviews">
                <?= csrf_field() ?>
                <label class="field"><span>Your rating</span>
                    <select class="select" name="rating"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?></select>
                </label>
                <label class="field"><span>What should the next student know?</span><textarea class="textarea" name="comment" required minlength="8"></textarea></label>
                <button class="btn" type="submit">Save review</button>
            </form>
        <?php endif; ?>
    </div>
    <aside>
        <div class="board-card">
            <span class="kicker">Instructor</span>
            <strong><?= e($course['instructor_name']) ?></strong>
            <p><?= e($course['instructor_headline']) ?></p>
            <p class="quiet"><?= e(excerpt($course['instructor_bio'] ?? '', 220)) ?></p>
        </div>
        <?php if ($collaborators): ?>
            <div class="board-card" style="margin-top:12px">
                <span class="kicker">Also teaching</span>
                <?php foreach ($collaborators as $person): ?><p><?= e($person['name']) ?> · <?= e($person['headline']) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>
    </aside>
</div>
<?php if ($related): ?>
<section class="section">
    <div class="section-head"><h2>Nearby</h2></div>
    <div class="course-grid">
        <?php foreach ($related as $relatedCourse): partial('partials/course-card', ['course' => $relatedCourse]); endforeach; ?>
    </div>
</section>
<?php endif; ?>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Course',
    'name' => $course['title'],
    'description' => $course['subtitle'],
    'provider' => ['@type' => 'Organization', 'name' => $siteName],
], json_flags()) ?>
</script>
