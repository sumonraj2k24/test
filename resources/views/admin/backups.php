<div class="page-head"><div><p class="kicker">Operations</p><h1>Backups</h1></div></div>
<p>Last backup <?= e(setting('last_backup_at', 'never')) ?>. Cron runs when due: <code><?= e(url('/cron/run?token=' . setting('cron_token', ''))) ?></code></p>
<form method="post" action="/admin/backups"><?= csrf_field() ?><button class="btn">Back up now</button></form>
<div class="table-wrap" style="margin-top:12px"><table>
    <thead><tr><th>File</th><th>Size</th><th>When</th><th>By</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($backups as $backup): ?>
        <tr>
            <td><?= e($backup['filename']) ?></td>
            <td><?= number_format(((int) $backup['size_bytes']) / 1024, 1) ?> KB</td>
            <td><?= e(pretty_datetime($backup['created_at'])) ?></td>
            <td><?= e($backup['name'] ?? 'cron') ?></td>
            <td><a href="/admin/backups/<?= (int) $backup['id'] ?>/download">Download</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
