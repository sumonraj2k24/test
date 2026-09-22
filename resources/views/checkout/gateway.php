<div class="pay-sheet">
    <p class="kicker">Sandbox · <?= e($gateway['label']) ?></p>
    <h1><?= money((int) $order['total_cents']) ?></h1>
    <p>Order <?= e($order['number']) ?>. This sheet is Meridian, not the provider’s hosted page. Card numbers are not stored — only a last four, if you type one.</p>
    <ul><?php foreach ($items as $item): ?><li><?= e($item['title']) ?></li><?php endforeach; ?></ul>
    <form method="post" action="/checkout/pay/<?= e($order['number']) ?>">
        <?= csrf_field() ?>
        <?php if (($gateway['kind'] ?? '') === 'card'): ?>
            <div class="card-preview">•••• •••• •••• ••••</div>
            <label class="field"><span>Card number</span><input class="input" name="card_number" inputmode="numeric" placeholder="4242 4242 4242 4242" required></label>
            <div class="grid-2">
                <label class="field"><span>Expiry</span><input class="input" name="card_expiry" placeholder="12 / 28" required></label>
                <label class="field"><span>CVC</span><input class="input" name="card_cvc" placeholder="123" required></label>
            </div>
        <?php else: ?>
            <p>Approve this <?= e($gateway['label']) ?> sandbox payment. No provider password is collected or stored.</p>
        <?php endif; ?>
        <button class="btn" type="submit">Confirm <?= e($gateway['label']) ?> sandbox</button>
        <a class="btn btn-ghost" href="/checkout">Back</a>
    </form>
</div>
