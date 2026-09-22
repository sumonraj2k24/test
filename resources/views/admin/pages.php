<div class="page-head"><div><p class="kicker">Content</p><h1>Pages</h1></div><a class="btn" href="/admin/pages/new">New page</a></div>
<div class="table-wrap"><table>
    <thead><tr><th>Title</th><th>Slug</th><th>Nav</th><th>Footer</th><th></th></tr></thead>
    <tbody><?php foreach ($pages as $page): ?><tr><td><?= e($page['title']) ?></td><td><?= e($page['slug']) ?></td><td><?= (int) $page['show_in_nav'] ? 'yes' : '' ?></td><td><?= (int) $page['show_in_footer'] ? 'yes' : '' ?></td><td><a href="/admin/pages/<?= (int) $page['id'] ?>">Edit</a></td></tr><?php endforeach; ?></tbody>
</table></div>
