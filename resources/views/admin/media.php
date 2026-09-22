<div class="page-head"><div><p class="kicker">Disk · <?= e(setting('storage_disk', 'local')) ?></p><h1>Media</h1></div></div>
<form method="post" action="/admin/media" enctype="multipart/form-data" class="stack"><?= csrf_field() ?><input class="input" type="file" name="file" required><button class="btn">Upload</button></form>
<div class="course-grid" style="margin-top:16px">
    <?php foreach ($files as $file): ?>
        <article class="board-card">
            <?php if (str_starts_with((string) $file['mime'], 'image/') && $file['disk'] === 'local'): ?><img src="<?= e($file['path']) ?>" alt=""><?php endif; ?>
            <strong><?= e($file['filename']) ?></strong>
            <p class="quiet"><?= e($file['disk']) ?> · <?= number_format(((int) $file['size_bytes']) / 1024, 1) ?> KB</p>
            <input class="input" readonly value="<?= e($file['path']) ?>">
            <form method="post" action="/admin/media/<?= (int) $file['id'] ?>/delete"><?= csrf_field() ?><button class="btn btn-small btn-danger">Delete</button></form>
        </article>
    <?php endforeach; ?>
</div>
