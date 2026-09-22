<div class="page-head"><div><p class="kicker">Commerce</p><h1>Payouts</h1></div></div>
<?php foreach ($payouts as $payout): ?>
    <form method="post" action="/admin/payouts/<?= (int) $payout['id'] ?>" class="board-card" style="margin-bottom:10px">
        <?= csrf_field() ?>
        <strong><?= e($payout['name']) ?></strong> · <?= money((int) $payout['amount_cents']) ?> · <?= status_badge($payout['status']) ?>
        <p class="quiet"><?= e($payout['method']) ?> · <?= e($payout['details']) ?></p>
        <label class="field"><span>Note</span><input class="input" name="note" value="<?= e($payout['note']) ?>"></label>
        <button class="btn btn-small" name="status" value="approved">Approve</button>
        <button class="btn btn-small" name="status" value="paid">Mark paid</button>
        <button class="btn btn-small btn-danger" name="status" value="rejected">Reject</button>
    </form>
<?php endforeach; ?>
