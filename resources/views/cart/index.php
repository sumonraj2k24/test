<div class="page-head"><div><p class="kicker">Cart</p><h1>What you are taking</h1></div></div>
<?php if (!$courses): ?>
    <div class="empty">The cart is empty. <a href="/courses">Browse the catalog</a>.</div>
<?php else: ?>
    <?php $total = 0; foreach ($courses as $course): $total += (int) $course['price_cents']; ?>
        <div class="lesson-row">
            <div><a href="/courses/<?= e($course['slug']) ?>"><strong><?= e($course['title']) ?></strong></a><div class="quiet"><?= e($course['instructor_name']) ?></div></div>
            <div class="stack">
                <span><?= (int) $course['price_cents'] === 0 ? 'Free' : money((int) $course['price_cents']) ?></span>
                <form method="post" action="/cart/remove"><?= csrf_field() ?><input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>"><button class="btn btn-small btn-ghost" type="submit">Remove</button></form>
            </div>
        </div>
    <?php endforeach; ?>
    <p style="margin-top:18px">Subtotal <?= money($total) ?></p>
    <?php if ($currentUser): ?>
        <a class="btn" href="/checkout">Checkout</a>
    <?php else: ?>
        <a class="btn" href="/login?next=/checkout">Sign in to checkout</a>
    <?php endif; ?>
<?php endif; ?>
