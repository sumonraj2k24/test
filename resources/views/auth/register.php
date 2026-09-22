<p class="kicker">Join</p>
<h1 style="font-size:42px;margin-bottom:16px">Make a shelf.</h1>
<form method="post" action="/register">
    <?= csrf_field() ?>
    <label class="field"><span>Name</span><input class="input" name="name" value="<?= e(old('name')) ?>" required><?php if ($msg = error('name')): ?><em class="field-error"><?= e($msg) ?></em><?php endif; ?></label>
    <label class="field"><span>Email</span><input class="input" type="email" name="email" value="<?= e(old('email')) ?>" required><?php if ($msg = error('email')): ?><em class="field-error"><?= e($msg) ?></em><?php endif; ?></label>
    <label class="field"><span>Password</span><input class="input" type="password" name="password" required minlength="8"><?php if ($msg = error('password')): ?><em class="field-error"><?= e($msg) ?></em><?php endif; ?></label>
    <label class="field"><span>Confirm password</span><input class="input" type="password" name="password_confirmation" required></label>
    <button class="btn" type="submit">Create account</button>
</form>
<p style="margin-top:16px">Already enrolled somewhere? <a href="/login">Sign in</a></p>
