<section class="hero">
    <div>
        <p class="kicker"><?= e(setting('hero_kicker', 'Studio')) ?></p>
        <h1><?= e(setting('hero_title', 'Learn from people who still do the work.')) ?></h1>
        <p class="lede"><?= e(setting('hero_lede', '')) ?></p>
        <form class="hero-search" action="/courses" method="get">
            <input name="q" placeholder="Search a course, an instructor, a problem" aria-label="Search courses">
            <button type="submit">Search</button>
        </form>
        <div class="hero-meta">
            <span><?= (int) $stats['courses'] ?> courses</span>
            <span><?= (int) $stats['instructors'] ?> instructors</span>
            <span><?= (int) $stats['learners'] ?> enrollments</span>
        </div>
    </div>
    <aside class="hero-board">
        <?php if ($upcoming): $next = $upcoming[0]; ?>
            <a class="board-card" href="/live" style="text-decoration:none">
                <span class="kicker">Next live room</span>
                <strong><?= e($next['title']) ?></strong>
                <span><?= e($next['instructor_name']) ?> · <?= e(pretty_datetime($next['starts_at'])) ?></span>
            </a>
        <?php endif; ?>
        <?php foreach (array_slice($courses, 0, 3) as $course): ?>
            <a class="mini-course" href="/courses/<?= e($course['slug']) ?>">
                <?php if (!empty($course['thumbnail'])): ?>
                    <img src="<?= e($course['thumbnail']) ?>" alt="">
                <?php else: ?>
                    <span class="swatch" style="background:<?= e($course['category_accent'] ?? '#1f4f45') ?>"></span>
                <?php endif; ?>
                <div>
                    <span class="quiet"><?= e($course['category_name'] ?? '') ?></span>
                    <strong><?= e($course['title']) ?></strong>
                </div>
            </a>
        <?php endforeach; ?>
    </aside>
</section>

<section class="section">
    <div class="category-row">
        <?php foreach ($categories as $category): ?>
            <a class="category-pill" href="/courses?category=<?= e($category['slug']) ?>">
                <i style="background:<?= e($category['accent'] ?: '#1f4f45') ?>"></i>
                <strong><?= e($category['name']) ?></strong>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <h2>On the table</h2>
        <a href="/courses">All courses</a>
    </div>
    <div class="course-grid">
        <?php foreach ($courses as $course): ?>
            <?php partial('partials/course-card', ['course' => $course]); ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="steps">
    <h2>How study works here</h2>
    <div><div class="step-no">01</div><h3>Enroll</h3><p>Free courses open immediately. Paid courses go through a cart with Stripe, PayPal, Mollie, or Paystack.</p></div>
    <div><div class="step-no">02</div><h3>Practice</h3><p>Readings, quizzes with attempt limits, assignments, and drip so a cohort stays together.</p></div>
    <div><div class="step-no">03</div><h3>Show the work</h3><p>Finish the lessons and the certificate carries a code anyone can verify.</p></div>
</section>

<?php if ($reviews): ?>
<section class="section">
    <div class="section-head"><h2>From the room</h2></div>
    <div class="quote-grid">
        <?php foreach ($reviews as $review): ?>
            <blockquote class="quote">
                <p>“<?= e($review['comment']) ?>”</p>
                <footer><?= e($review['student_name']) ?> · <a href="/courses/<?= e($review['course_slug']) ?>"><?= e($review['course_title']) ?></a></footer>
            </blockquote>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="section-head"><h2>Instructors</h2><a href="/teach">Apply to teach</a></div>
    <div class="instructor-row">
        <?php foreach ($instructors as $instructor): ?>
            <a class="person" href="/instructors/<?= (int) $instructor['id'] ?>">
                <strong><?= e($instructor['name']) ?></strong>
                <span><?= e($instructor['headline']) ?> · <?= (int) $instructor['course_count'] ?> course<?= (int) $instructor['course_count'] === 1 ? '' : 's' ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
