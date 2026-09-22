<div class="page-head"><div><p class="kicker">People</p><h1>Users</h1></div></div>
<form class="filters" method="get">
    <input class="input" name="q" value="<?= e($q) ?>" placeholder="Name or email">
    <select class="select" name="role"><option value="">Any role</option><?php foreach ($roles as $roleRow): ?><option value="<?= e($roleRow['slug']) ?>" <?= selected($role, $roleRow['slug']) ?>><?= e($roleRow['name']) ?></option><?php endforeach; ?></select>
    <button class="btn" type="submit">Filter</button>
</form>
<div class="table-wrap"><table>
    <thead><tr><th>User</th><th>Role</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= e($user['name']) ?><div class="quiet"><?= e($user['email']) ?></div></td>
            <td colspan="3">
                <form method="post" action="/admin/users/<?= (int) $user['id'] ?>" class="stack">
                    <?= csrf_field() ?>
                    <input class="input" name="name" value="<?= e($user['name']) ?>" style="max-width:180px">
                    <select class="select" name="role"><?php foreach ($roles as $roleRow): ?><option value="<?= e($roleRow['slug']) ?>" <?= selected($user['role'], $roleRow['slug']) ?>><?= e($roleRow['name']) ?></option><?php endforeach; ?></select>
                    <select class="select" name="status"><option value="active" <?= selected($user['status'], 'active') ?>>active</option><option value="suspended" <?= selected($user['status'], 'suspended') ?>>suspended</option></select>
                    <button class="btn btn-small" type="submit">Save</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
