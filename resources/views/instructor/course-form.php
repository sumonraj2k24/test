<div class="page-head"><div><p class="kicker">Course</p><h1><?= $course ? 'Edit details' : 'New course' ?></h1></div>
    <?php if ($course): ?><a href="/studio/courses/<?= (int) $course['id'] ?>/curriculum">Curriculum</a><?php endif; ?>
</div>
<form method="post" action="<?= $course ? '/studio/courses/' . (int) $course['id'] : '/studio/courses' ?>" enctype="multipart/form-data" style="max-width:760px">
    <?= csrf_field() ?>
    <?php $c = $course ?? []; ?>
    <label class="field"><span>Title</span><input class="input" name="title" value="<?= e($c['title'] ?? old('title')) ?>" required></label>
    <label class="field"><span>Subtitle</span><input class="input" name="subtitle" value="<?= e($c['subtitle'] ?? old('subtitle')) ?>" required></label>
    <div class="grid-2">
        <label class="field"><span>Category</span>
            <select class="select" name="category_id"><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= selected($c['category_id'] ?? '', (string) $category['id']) ?>><?= e($category['name']) ?></option><?php endforeach; ?></select>
        </label>
        <label class="field"><span>Level</span>
            <select class="select" name="level"><?php foreach (['beginner','intermediate','advanced','all'] as $level): ?><option value="<?= $level ?>" <?= selected($c['level'] ?? 'all', $level) ?>><?= e(course_level_label($level)) ?></option><?php endforeach; ?></select>
        </label>
    </div>
    <div class="grid-2">
        <label class="field"><span>Price (USD)</span><input class="input" name="price" value="<?= e(isset($c['price_cents']) ? number_format($c['price_cents'] / 100, 2, '.', '') : '0') ?>" required></label>
        <label class="field"><span>Compare-at price</span><input class="input" name="compare" value="<?= e(!empty($c['compare_cents']) ? number_format($c['compare_cents'] / 100, 2, '.', '') : '') ?>"></label>
    </div>
    <label class="field"><span>Language</span><input class="input" name="language" value="<?= e($c['language'] ?? 'English') ?>" required></label>
    <label class="field"><span>Description</span><textarea class="textarea" name="description" required><?= e($c['description'] ?? '') ?></textarea></label>
    <label class="field"><span>Outcomes, one per line</span><textarea class="textarea" name="outcomes"><?= e($c['outcomes'] ?? '') ?></textarea></label>
    <label class="field"><span>Requirements, one per line</span><textarea class="textarea" name="requirements"><?= e($c['requirements'] ?? '') ?></textarea></label>
    <label class="field"><span>Thumbnail</span><input class="input" type="file" name="thumbnail" accept="image/*"></label>
    <label class="field"><span>Preview video URL</span><input class="input" name="preview_video" value="<?= e($c['preview_video'] ?? '') ?>"></label>
    <label class="check-row"><input type="checkbox" name="enforce_sequence" value="1" <?= checked((int) ($c['enforce_sequence'] ?? 0)) ?>> Teach in sequence</label>
    <h2 style="margin:18px 0 8px">SEO</h2>
    <label class="field"><span>SEO title</span><input class="input" name="seo_title" value="<?= e($c['seo_title'] ?? '') ?>"></label>
    <label class="field"><span>SEO description</span><textarea class="textarea" name="seo_description"><?= e($c['seo_description'] ?? '') ?></textarea></label>
    <button class="btn" type="submit">Save</button>
</form>
<?php if ($course): ?>
    <form method="post" action="/studio/courses/<?= (int) $course['id'] ?>/submit" style="margin-top:12px"><?= csrf_field() ?><button class="btn btn-gold" type="submit">Submit / publish</button></form>
    <?php if (setting('course_management_mode') === 'collaborative'): ?>
        <form method="post" action="/studio/courses/<?= (int) $course['id'] ?>/collaborators" class="stack" style="margin-top:18px">
            <?= csrf_field() ?>
            <input class="input" name="email" placeholder="Collaborator email" style="max-width:280px">
            <button class="btn btn-line" type="submit">Invite</button>
        </form>
        <?php foreach ($collaborators ?? [] as $person): ?><p class="quiet"><?= e($person['name']) ?> · <?= e($person['email']) ?></p><?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>
