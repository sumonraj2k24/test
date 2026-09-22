<p class="kicker">Sign in</p>
<h1 style="font-size:42px;margin-bottom:16px">Welcome back.</h1>
<form method="post" action="/login" data-login>
    <?= csrf_field() ?>
    <?php if (!empty($next)): ?><input type="hidden" name="next" value="<?= e($next) ?>"><?php endif; ?>
    <label class="field"><span>Email</span><input class="input" type="email" name="email" value="<?= e(old('email')) ?>" required></label>
    <label class="field"><span>Password</span><input class="input" type="password" name="password" required></label>
    <button class="btn" type="submit">Sign in</button>
    <a class="btn btn-line" href="/auth/google">Google</a>
</form>
<p style="margin-top:16px">New here? <a href="/register">Create an account</a></p>
<div class="demo-keys">
    <p class="quiet">Seeded studio keys — password <code>meridian</code></p>
    <?php foreach ([
        ['Mira · admin', 'mira@meridian.test'],
        ['Amara · instructor', 'amara@meridian.test'],
        ['Jonah · instructor', 'jonah@meridian.test'],
        ['Nora · student', 'nora@meridian.test'],
        ['Chris · student', 'chris@meridian.test'],
    ] as [$label, $email]): ?>
        <div><button type="button" data-fill data-email="<?= e($email) ?>"><?= e($label) ?></button></div>
    <?php endforeach; ?>
</div>
