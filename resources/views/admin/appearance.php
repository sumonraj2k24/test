<h1>Appearance</h1>
<form method="post" action="/admin/appearance">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label class="field"><span>Site name</span><input class="input" name="site_name" value="<?= e(setting('site_name')) ?>"></label>
        <label class="field"><span>Tagline</span><input class="input" name="tagline" value="<?= e(setting('tagline')) ?>"></label>
        <label class="field"><span>Primary</span><input class="input" name="primary_color" value="<?= e(setting('primary_color')) ?>"></label>
        <label class="field"><span>Accent</span><input class="input" name="accent_color" value="<?= e(setting('accent_color')) ?>"></label>
    </div>
    <label class="field"><span>Announcement bar</span><input class="input" name="announcement" value="<?= e(setting('announcement')) ?>"></label>
    <label class="field"><span>Hero kicker</span><input class="input" name="hero_kicker" value="<?= e(setting('hero_kicker')) ?>"></label>
    <label class="field"><span>Hero title</span><input class="input" name="hero_title" value="<?= e(setting('hero_title')) ?>"></label>
    <label class="field"><span>Hero lede</span><textarea class="textarea" name="hero_lede"><?= e(setting('hero_lede')) ?></textarea></label>
    <label class="field"><span>Footer blurb</span><textarea class="textarea" name="footer_blurb"><?= e(setting('footer_blurb')) ?></textarea></label>
    <label class="field"><span>SEO title</span><input class="input" name="seo_title" value="<?= e(setting('seo_title')) ?>"></label>
    <label class="field"><span>SEO description</span><textarea class="textarea" name="seo_description"><?= e(setting('seo_description')) ?></textarea></label>
    <h2>Navbar</h2>
    <div data-rows>
        <?php foreach ($navLinks as $link): ?>
            <div class="grid-2" data-row>
                <input class="input" name="nav_label[]" value="<?= e($link['label']) ?>">
                <input class="input" name="nav_href[]" value="<?= e($link['href']) ?>">
            </div>
        <?php endforeach; ?>
        <div class="grid-2" data-row>
            <input class="input" name="nav_label[]" placeholder="Label">
            <input class="input" name="nav_href[]" placeholder="/path">
        </div>
        <button class="btn btn-small btn-ghost" type="button" data-add-row>Add link</button>
    </div>
    <button class="btn" type="submit" style="margin-top:12px">Save appearance</button>
</form>
