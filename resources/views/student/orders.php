<div class="page-head"><div><p class="kicker">Receipts</p><h1>Orders</h1></div></div>
<div class="table-wrap"><table>
    <thead><tr><th>Number</th><th>When</th><th>Gateway</th><th>Total</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $order): ?>
        <tr>
            <td><a href="/orders/<?= (int) $order['id'] ?>"><?= e($order['number']) ?></a></td>
            <td><?= e(pretty_date($order['created_at'])) ?></td>
            <td><?= e($order['gateway']) ?></td>
            <td><?= money((int) $order['total_cents']) ?></td>
            <td><?= status_badge($order['status']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
