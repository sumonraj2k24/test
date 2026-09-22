<div class="page-head"><div><p class="kicker">Checkout</p><h1>Pay the studio</h1></div></div>
<?php if (!$courses): ?><div class="empty">Nothing to pay. <a href="/cart">Return to cart</a>.</div><?php else: ?>
<form method="post" action="/checkout">
    <?= csrf_field() ?>
    <?php foreach ($courses as $course): ?>
        <div class="lesson-row"><span><?= e($course['title']) ?></span><span><?= money((int) $course['price_cents']) ?></span></div>
    <?php endforeach; ?>
    <label class="field" style="max-width:280px;margin-top:16px"><span>Coupon</span><input class="input" name="coupon" value="<?= e($coupon) ?>" placeholder="WELCOME10"></label>
    <?php if ($quote['coupon_error']): ?><p class="field-error"><?= e($quote['coupon_error']) ?></p><?php endif; ?>
    <p>Subtotal <?= money($quote['subtotal']) ?><?php if ($quote['discount']): ?> · discount <?= money($quote['discount']) ?><?php endif; ?></p>
    <p><strong>Due <?= money($quote['total']) ?></strong></p>
    <?php if ($quote['total'] > 0): ?>
        <div class="grid-2">
            <?php foreach ($gateways as $key => $gateway): ?>
                <label class="board-card">
                    <input type="radio" name="gateway" value="<?= e($key) ?>" <?= $key === 'stripe' ? 'checked' : '' ?>>
                    <strong><?= e($gateway['label']) ?></strong>
                    <p class="quiet"><?= e($gateway['blurb']) ?> <?= $gateway['configured'] ? 'Live keys are set.' : 'Sandbox until keys are saved.' ?></p>
                </label>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <button class="btn" type="submit" style="margin-top:12px"><?= $quote['total'] === 0 ? 'Enroll' : 'Continue to payment' ?></button>
</form>
<?php endif; ?>
