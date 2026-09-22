<div class="page-head"><div><p class="kicker">Catalog</p><h1>Categories</h1></div></div>
<form method="post" action="/admin/categories" class="board-card">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label class="field"><span>Name</span><input class="input" name="name" required></label>
        <label class="field"><span>Accent</span><input class="input" name="accent" value="#1f4f45"></label>
    </div>
    <label class="field"><span>Description</span><input class="input" name="description"></label>
    <label class="field"><span>Position</span><input class="input" type="number" name="position" value="0"></label>
    <button class="btn" type="submit">Add category</button>
</form>
<?php foreach ($categories as $category): ?>
    <form method="post" action="/admin/categories" class="lesson-row">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
        <input class="input" name="name" value="<?= e($category['name']) ?>">
        <input class="input" name="accent" value="<?= e($category['accent']) ?>">
        <input class="input" name="description" value="<?= e($category['description']) ?>">
        <input class="input" type="number" name="position" value="<?= (int) $category['position'] ?>" style="max-width:80px">
        <button class="btn btn-small">Save</button>
        <span class="quiet"><?= (int) $category['course_count'] ?></span>
    </form>
    <form method="post" action="/admin/categories/<?= (int) $category['id'] ?>/delete" data-confirm="Delete category?"><?= csrf_field() ?><button class="btn btn-small btn-danger">Delete</button></form>
<?php endforeach; ?>
