<div class="page-head"><div><p class="kicker">Money</p><h1>Earnings</h1></div></div>
<div class="stats">
    <div class="stat"><b><?= money((int) $stats['revenue']) ?></b><span>Earned</span></div>
    <div class="stat"><b><?= money((int) $stats['reserved']) ?></b><span>Reserved in payouts</span></div>
    <div class="stat"><b><?= money((int) $stats['available']) ?></b><span>Available</span></div>
    <div class="stat"><b><?= (int) setting('platform_commission_percent', '20') ?>%</b><span>Platform commission</span></div>
</div>
<form method="post" action="/studio/earnings/payout" class="board-card">
    <?= csrf_field() ?>
    <label class="field"><span>Request amount (USD)</span><input class="input" name="amount" required></label>
    <label class="field"><span>Note</span><input class="input" name="note"></label>
    <button class="btn" type="submit">Request payout</button>
</form>
<h2>Payouts</h2>
<div class="table-wrap"><table>
    <thead><tr><th>When</th><th>Amount</th><th>Status</th><th>Note</th></tr></thead>
    <tbody><?php foreach ($payouts as $payout): ?><tr><td><?= e(pretty_date($payout['requested_at'])) ?></td><td><?= money((int) $payout['amount_cents']) ?></td><td><?= status_badge($payout['status']) ?></td><td><?= e($payout['note']) ?></td></tr><?php endforeach; ?></tbody>
</table></div>
<h2>Sales</h2>
<div class="table-wrap"><table>
    <thead><tr><th>Order</th><th>Course</th><th>Net</th><th>Your earning</th></tr></thead>
    <tbody><?php foreach ($sales as $sale): ?><tr><td><?= e($sale['number']) ?></td><td><?= e($sale['title']) ?></td><td><?= money((int) $sale['price_cents']) ?></td><td><?= money((int) $sale['instructor_earning_cents']) ?></td></tr><?php endforeach; ?></tbody>
</table></div>
