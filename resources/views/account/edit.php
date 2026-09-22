<div class="page-head"><div><p class="kicker">Account</p><h1>Profile</h1></div></div>
<form method="post" action="/account" enctype="multipart/form-data" style="max-width:680px">
    <?= csrf_field() ?>
    <label class="field"><span>Name</span><input class="input" name="name" value="<?= e($currentUser['name']) ?>" required></label>
    <label class="field"><span>Headline</span><input class="input" name="headline" value="<?= e($currentUser['headline']) ?>"></label>
    <label class="field"><span>Bio</span><textarea class="textarea" name="bio"><?= e($currentUser['bio']) ?></textarea></label>
    <div class="grid-2">
        <label class="field"><span>Payout method</span><input class="input" name="payout_method" value="<?= e($currentUser['payout_method']) ?>" placeholder="paypal"></label>
        <label class="field"><span>Payout details</span><input class="input" name="payout_details" value="<?= e($currentUser['payout_details']) ?>"></label>
    </div>
    <label class="field"><span>Avatar</span><input class="input" type="file" name="avatar" accept="image/*"></label>
    <label class="field"><span>New password, optional</span><input class="input" type="password" name="password" minlength="8"></label>
    <button class="btn" type="submit">Save</button>
</form>
