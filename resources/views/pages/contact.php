<p class="kicker">Contact</p>
<h1>Write to the studio</h1>
<form method="post" action="/contact" style="max-width:640px;margin-top:18px">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label class="field"><span>Name</span><input class="input" name="name" value="<?= e(old('name')) ?>" required></label>
        <label class="field"><span>Email</span><input class="input" type="email" name="email" value="<?= e(old('email')) ?>" required></label>
    </div>
    <label class="field"><span>Subject</span><input class="input" name="subject" value="<?= e(old('subject')) ?>" required></label>
    <label class="field"><span>Message</span><textarea class="textarea" name="body" required><?= e(old('body')) ?></textarea></label>
    <button class="btn" type="submit">Send</button>
</form>
