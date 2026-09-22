<div class="page-head">
    <div>
        <p class="kicker">Catalog</p>
        <h1>Courses</h1>
    </div>
    <span class="quiet"><?= count($courses) ?> shown</span>
</div>
<form class="filters" method="get" action="/courses">
    <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Title, instructor, topic">
    <select class="select" name="category">
        <option value="">All categories</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?= e($category['slug']) ?>" <?= selected($filters['category'], $category['slug']) ?>><?= e($category['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="select" name="level">
        <option value="">Any level</option>
        <?php foreach (['beginner', 'intermediate', 'advanced', 'all'] as $level): ?>
            <option value="<?= $level ?>" <?= selected($filters['level'], $level) ?>><?= e(course_level_label($level)) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="select" name="instructor">
        <option value="">Any instructor</option>
        <?php foreach ($instructors as $instructor): ?>
            <option value="<?= (int) $instructor['id'] ?>" <?= selected($filters['instructor'], (string) $instructor['id']) ?>><?= e($instructor['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="select" name="rating">
        <option value="">Any rating</option>
        <option value="4" <?= selected($filters['rating'], '4') ?>>4 and up</option>
        <option value="5" <?= selected($filters['rating'], '5') ?>>5 only</option>
    </select>
    <select class="select" name="price">
        <option value="">Free or paid</option>
        <option value="free" <?= selected($filters['price'], 'free') ?>>Free</option>
        <option value="paid" <?= selected($filters['price'], 'paid') ?>>Paid</option>
    </select>
    <select class="select" name="sort">
        <?php foreach (['featured' => 'Featured', 'newest' => 'Newest', 'popular' => 'Popular', 'rating' => 'Rating', 'price_asc' => 'Price ↑', 'price_desc' => 'Price ↓'] as $value => $label): ?>
            <option value="<?= $value ?>" <?= selected($filters['sort'], $value) ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Filter</button>
</form>
<?php if (!$courses): ?>
    <div class="empty">Nothing matches. Widen the filter, or search a person instead of a slogan.</div>
<?php else: ?>
    <div class="course-grid">
        <?php foreach ($courses as $course): partial('partials/course-card', ['course' => $course]); endforeach; ?>
    </div>
<?php endif; ?>
