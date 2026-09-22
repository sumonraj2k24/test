<div class="page-head"><div><p class="kicker">Commerce</p><h1>Orders</h1></div><a href="/admin/orders/export">Export CSV</a></div>
<form method="get" class="stack"><select class="select" name="status"><option value="">Any</option><?php foreach (['pending','paid','refunded'] as $state): ?><option <?= selected($status, $state) ?>><?= $state ?></option><?php endforeach; ?></select><button class="btn btn-small">Filter</button></form>
<div class="table-wrap"><table>
    <thead><tr><th>Number</th><th>Student</th><th>Gateway</th><th>Total</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($orders as $order): ?>
        <tr>
            <td><?= e($order['number']) ?></td>
            <td><?= e($order['name']) ?><div class="quiet"><?= e($order['email']) ?></div></td>
            <td><?= e($order['gateway']) ?></td>
            <td><?= money((int) $order['total_cents']) ?></td>
            <td><?= status_badge($order['status']) ?></td>
            <td><?php if ($order['status'] === 'paid'): ?><form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/refund" data-confirm="Refund and withdraw access?"><?= csrf_field() ?><button class="btn btn-small btn-danger">Refund</button></form><?php endif; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
