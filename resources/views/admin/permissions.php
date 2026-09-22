<div class="page-head"><div><p class="kicker">Access</p><h1>Permissions</h1></div></div>
<form method="post" action="/admin/roles" class="stack"><?= csrf_field() ?><input class="input" name="name" placeholder="New role name" style="max-width:240px"><button class="btn btn-line">Create role</button></form>
<form method="post" action="/admin/permissions">
    <?= csrf_field() ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Permission</th><?php foreach ($roles as $role): ?><th><?= e($role['name']) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php
        $granted = [];
        foreach ($grants as $grant) { $granted[$grant['role'] . ':' . $grant['permission_id']] = true; }
        $group = null;
        foreach ($permissions as $permission):
        ?>
            <tr>
                <td><?= e($permission['group_name']) ?> · <?= e($permission['name']) ?></td>
                <?php foreach ($roles as $role): ?>
                    <td><input type="checkbox" name="grant[<?= e($role['slug']) ?>][]" value="<?= (int) $permission['id'] ?>" <?= isset($granted[$role['slug'] . ':' . $permission['id']]) ? 'checked' : '' ?>></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <button class="btn" type="submit" style="margin-top:12px">Save matrix</button>
</form>
<p class="quiet">Administrator always keeps admin access, settings, and role management, so the studio cannot lock itself out.</p>
