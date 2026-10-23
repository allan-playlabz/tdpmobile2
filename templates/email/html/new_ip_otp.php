<p>Dear <?php echo $data['user']->name ?>,</p>
<p>We don't recognized the IP address (<?php echo $data['user']->clientIp ?>) you are using, so this email is sent for your protection. <br>
Your one time password is <b><?php echo $data['user']->otp ?></b> valid only for 100 seconds.</p>
<p>Regards,</p>
<p>Your <?= \Cake\Core\Configure::read('Default.site_name') ?> Friends <br>
<a href="<?= $this->Url->build('/', ['fullBase' => true]) ?>"><?= $this->Url->build('/', ['fullBase' => true]) ?></a>
</p>