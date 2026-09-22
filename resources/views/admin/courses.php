<div class="page-head"><div><p class="kicker">Catalog</p><h1>Courses</h1></div></div>
<form class="filters" method="get">
    <input class="input" name="q" value="<?= e($q) ?>" placeholder="Search">
    <select class="select" name="status">
        <option value="">Any status</option>
        <?php foreach (['draft','pending','published','rejected'] as $state): ?><option value="<?= $state ?>" <?= selected($status, $state) ?>><?= $state ?></option><?php endforeach; ?>
    </select>
    <button class="btn">Filter</button>
</form>
<div class="table-wrap"><table>
    <thead><tr><th>Course</th><th>Instructor</th><th>Status</th><th>Price</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($courses as $course): ?>
        <tr>
            <td><a href="/courses/<?= e($course['slug']) ?>"><?= e($course['title']) ?></a><?php if ((int) $course['is_featured']): ?> · featured<?php endif; ?></td>
            <td><?= e($course['instructor_name']) ?></td>
            <td><?= status_badge($course['status']) ?></td>
            <td><?= money((int) $course['price_cents']) ?></td>
            <td class="stack">
                <form method="post" action="/admin/courses/<?= (int) $course['id'] ?>/moderate"><?= csrf_field() ?><button class="btn btn-small" name="action" value="approve">Publish</button></form>
                <form method="post" action="/admin/courses/<?= (int) $course['id'] ?>/moderate"><?= csrf_field() ?><input type="hidden" name="note" value="Needs a clearer outcome list."><button class="btn btn-small btn-ghost" name="action" value="reject">Return</button></form>
                <form method="post" action="/admin/courses/<?= (int) $course['id'] ?>/moderate"><?= csrf_field() ?><button class="btn btn-small btn-ghost" name="action" value="feature">Feature</button></form>
                <form method="post" action="/admin/courses/<?= (int) $course['id'] ?>/moderate"><?= csrf_field() ?><button class="btn btn-small btn-danger" name="action" value="unpublish">Unpublish</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
