<h1><?= $page ? 'Edit page' : 'New page' ?></h1>
<form method="post" action="/admin/pages">
    <?= csrf_field() ?>
    <?php if ($page): ?><input type="hidden" name="id" value="<?= (int) $page['id'] ?>"><?php endif; ?>
    <label class="field"><span>Title</span><input class="input" name="title" value="<?= e($page['title'] ?? '') ?>" required></label>
    <label class="field"><span>Slug</span><input class="input" name="slug" value="<?= e($page['slug'] ?? '') ?>"></label>
    <label class="field"><span>Content (markdown)</span><textarea class="textarea" name="content" required><?= e($page['content'] ?? '') ?></textarea></label>
    <label class="field"><span>SEO title</span><input class="input" name="seo_title" value="<?= e($page['seo_title'] ?? '') ?>"></label>
    <label class="field"><span>SEO description</span><input class="input" name="seo_description" value="<?= e($page['seo_description'] ?? '') ?>"></label>
    <label class="field"><span>Status</span><select class="select" name="status"><option value="published">published</option><option value="draft" <?= selected($page['status'] ?? '', 'draft') ?>>draft</option></select></label>
    <label class="check-row"><input type="checkbox" name="show_in_nav" value="1" <?= checked((int) ($page['show_in_nav'] ?? 0)) ?>> Show in navbar</label>
    <label class="check-row"><input type="checkbox" name="show_in_footer" value="1" <?= checked((int) ($page['show_in_footer'] ?? 1)) ?>> Show in footer</label>
    <label class="field"><span>Position</span><input class="input" type="number" name="position" value="<?= (int) ($page['position'] ?? 0) ?>"></label>
    <button class="btn" type="submit">Save page</button>
</form>
