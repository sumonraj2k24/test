<p class="kicker">Order</p>
<h1><?= e($order['number']) ?></h1>
<p><?= status_badge($order['status']) ?> <?= e($order['gateway']) ?> · <?= money((int) $order['total_cents']) ?></p>
<ul><?php foreach ($items as $item): ?><li><?= e($item['title']) ?> · <?= money((int) $item['price_cents']) ?> · instructor keeps <?= money((int) $item['instructor_earning_cents']) ?></li><?php endforeach; ?></ul>
<h2>Transactions</h2>
<ul><?php foreach ($transactions as $tx): ?><li><?= e($tx['status']) ?> <?= money((int) $tx['amount_cents']) ?> <?= $tx['last4'] ? '· card ·' . e($tx['last4']) : '' ?></li><?php endforeach; ?></ul>
