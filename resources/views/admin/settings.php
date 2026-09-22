<div class="page-head"><div><p class="kicker">System</p><h1>Settings</h1></div></div>
<form method="post" action="/admin/settings">
    <?= csrf_field() ?>
    <h2>Studio rules</h2>
    <div class="grid-2">
        <label class="field"><span>Currency</span><input class="input" name="currency" value="<?= e(setting('currency', 'USD')) ?>"></label>
        <label class="field"><span>Commission percent</span><input class="input" name="platform_commission_percent" value="<?= e(setting('platform_commission_percent', '20')) ?>"></label>
        <label class="field"><span>Course management</span>
            <select class="select" name="course_management_mode">
                <option value="collaborative" <?= selected(setting('course_management_mode'), 'collaborative') ?>>Collaborative</option>
                <option value="centralized" <?= selected(setting('course_management_mode'), 'centralized') ?>>Centralized</option>
            </select>
        </label>
        <label class="field"><span>Public URL override</span><input class="input" name="app_url" value="<?= e(setting('app_url', '')) ?>" placeholder="https://studio.example"></label>
    </div>
    <label class="check-row"><input type="checkbox" name="require_course_approval" value="1" <?= checked(setting('require_course_approval', '1') === '1') ?>> Require admin approval before publish</label>
    <label class="field"><span>Support email</span><input class="input" name="support_email" value="<?= e(setting('support_email')) ?>"></label>
    <h2>Payment gateways</h2>
    <?php foreach (['stripe' => 'Stripe secret', 'paypal' => 'PayPal', 'mollie' => 'Mollie', 'paystack' => 'Paystack'] as $key => $label): ?>
        <label class="check-row"><input type="checkbox" name="pay_<?= $key ?>_enabled" value="1" <?= checked(setting('pay_' . $key . '_enabled', '1') === '1') ?>> Enable <?= e($label) ?></label>
    <?php endforeach; ?>
    <label class="field"><span>Stripe secret</span><input class="input" name="stripe_secret" value="<?= e(setting('stripe_secret', '')) ?>"></label>
    <label class="field"><span>Stripe webhook secret</span><input class="input" name="stripe_webhook_secret" value="<?= e(setting('stripe_webhook_secret', '')) ?>"></label>
    <div class="grid-2">
        <label class="field"><span>PayPal client id</span><input class="input" name="paypal_client_id" value="<?= e(setting('paypal_client_id', '')) ?>"></label>
        <label class="field"><span>PayPal secret</span><input class="input" name="paypal_secret" value="<?= e(setting('paypal_secret', '')) ?>"></label>
    </div>
    <label class="field"><span>PayPal mode</span><select class="select" name="paypal_mode"><option value="sandbox" <?= selected(setting('paypal_mode', 'sandbox'), 'sandbox') ?>>sandbox</option><option value="live" <?= selected(setting('paypal_mode'), 'live') ?>>live</option></select></label>
    <label class="field"><span>Mollie API key</span><input class="input" name="mollie_api_key" value="<?= e(setting('mollie_api_key', '')) ?>"></label>
    <label class="field"><span>Paystack secret</span><input class="input" name="paystack_secret" value="<?= e(setting('paystack_secret', '')) ?>"></label>
    <h2 id="mail">SMTP</h2>
    <div class="grid-2">
        <label class="field"><span>Host</span><input class="input" name="smtp_host" value="<?= e(setting('smtp_host', '')) ?>"></label>
        <label class="field"><span>Port</span><input class="input" name="smtp_port" value="<?= e(setting('smtp_port', '587')) ?>"></label>
        <label class="field"><span>Encryption</span><select class="select" name="smtp_encryption"><option value="tls">tls</option><option value="ssl" <?= selected(setting('smtp_encryption'), 'ssl') ?>>ssl</option><option value="none" <?= selected(setting('smtp_encryption'), 'none') ?>>none</option></select></label>
        <label class="field"><span>Username</span><input class="input" name="smtp_username" value="<?= e(setting('smtp_username', '')) ?>"></label>
        <label class="field"><span>Password</span><input class="input" name="smtp_password" value="<?= e(setting('smtp_password', '')) ?>"></label>
        <label class="field"><span>From</span><input class="input" name="smtp_from" value="<?= e(setting('smtp_from', '')) ?>"></label>
    </div>
    <label class="field"><span>From name</span><input class="input" name="smtp_from_name" value="<?= e(setting('smtp_from_name', '')) ?>"></label>
    <h2>Google OAuth</h2>
    <label class="field"><span>Client id</span><input class="input" name="google_client_id" value="<?= e(setting('google_client_id', '')) ?>"></label>
    <label class="field"><span>Client secret</span><input class="input" name="google_client_secret" value="<?= e(setting('google_client_secret', '')) ?>"></label>
    <p class="quiet">Redirect URI: <?= e(url('/auth/google/callback')) ?></p>
    <h2>Zoom server-to-server</h2>
    <label class="field"><span>Account id</span><input class="input" name="zoom_account_id" value="<?= e(setting('zoom_account_id', '')) ?>"></label>
    <label class="field"><span>Client id</span><input class="input" name="zoom_client_id" value="<?= e(setting('zoom_client_id', '')) ?>"></label>
    <label class="field"><span>Client secret</span><input class="input" name="zoom_client_secret" value="<?= e(setting('zoom_client_secret', '')) ?>"></label>
    <h2>Storage</h2>
    <label class="field"><span>Disk</span><select class="select" name="storage_disk"><option value="local" <?= selected(setting('storage_disk'), 'local') ?>>local</option><option value="s3" <?= selected(setting('storage_disk'), 's3') ?>>s3</option></select></label>
    <label class="field"><span>Max upload MB</span><input class="input" name="max_upload_mb" value="<?= e(setting('max_upload_mb', '20')) ?>"></label>
    <div class="grid-2">
        <label class="field"><span>S3 key</span><input class="input" name="s3_key" value="<?= e(setting('s3_key', '')) ?>"></label>
        <label class="field"><span>S3 secret</span><input class="input" name="s3_secret" value="<?= e(setting('s3_secret', '')) ?>"></label>
        <label class="field"><span>Region</span><input class="input" name="s3_region" value="<?= e(setting('s3_region', 'us-east-1')) ?>"></label>
        <label class="field"><span>Bucket</span><input class="input" name="s3_bucket" value="<?= e(setting('s3_bucket', '')) ?>"></label>
    </div>
    <label class="field"><span>S3 endpoint (optional, path-style)</span><input class="input" name="s3_endpoint" value="<?= e(setting('s3_endpoint', '')) ?>"></label>
    <h2>Backups</h2>
    <div class="grid-2">
        <label class="field"><span>Interval hours</span><input class="input" name="backup_interval_hours" value="<?= e(setting('backup_interval_hours', '24')) ?>"></label>
        <label class="field"><span>Retention</span><input class="input" name="backup_retention" value="<?= e(setting('backup_retention', '8')) ?>"></label>
    </div>
    <label class="field"><span>Cron token</span><input class="input" name="cron_token" value="<?= e(setting('cron_token', '')) ?>"></label>
    <p class="quiet">Cron URL: <?= e(url('/cron/run?token=' . setting('cron_token', ''))) ?></p>
    <button class="btn" type="submit">Save settings</button>
</form>
<form method="post" action="/admin/settings/test-mail" class="stack" style="margin-top:12px"><?= csrf_field() ?><input class="input" type="email" name="email" placeholder="Test recipient" style="max-width:280px"><button class="btn btn-line">Send test mail</button></form>
