<p>Dear <?= h($data['user']->name) ?>,</p>
<?php if (($data['reason'] ?? null) === 'two_factor_fallback'): ?>
<p>Someone (hopefully you) chose to sign in without the authenticator app from the IP address <?= h($data['user']->clientIp) ?>. If this wasn't you, don't share this code and change your password. <br>
<?php else: ?>
<p>We don't recognize the IP address (<?= h($data['user']->clientIp) ?>) you are using, so this email is sent for your protection. <br>
<?php endif ?>
Your one time password is <b><?= h($data['user']->otp) ?></b> valid only for 5 minutes.</p>
<p>Regards,</p>
<p>Your <?= \Cake\Core\Configure::read('Default.site_name') ?> Friends <br>
<a href="<?= $this->Url->build('/', ['fullBase' => true]) ?>"><?= $this->Url->build('/', ['fullBase' => true]) ?></a>
</p>
