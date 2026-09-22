<p class="kicker">Paid</p>
<h1>You are enrolled.</h1>
<p>Order <?= e($order['number']) ?> · <?= e($order['gateway']) ?> · <?= money((int) $order['total_cents']) ?></p>
<ul><?php foreach ($items as $item): ?><li><a href="/learn/<?= e($item['slug']) ?>"><?= e($item['title']) ?></a></li><?php endforeach; ?></ul>
<a class="btn" href="/learn">Go to my learning</a>
