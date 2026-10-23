<p>Hi <?= $data['agent_user']->name ?>,	</p>
 <p><?= $data['user']['name'] ?> reset your password in <a href="<?= $this->Url->build('/',['fullBase'=>true]); ?>"><?= \Cake\Core\Configure::read('Default.site_name') ?></a>. Below is a temporary password you can use to login and set your new password. <br> <b><?= $data['temp_password'] ?></b></p>
<p>Regards,</p>
<p>Your Friends at  <?= \Cake\Core\Configure::read('Default.site_name') ?><br>
<a href="<?= $this->Url->build('/',['fullBase'=>true]); ?>"><?= $this->Url->build('/',['fullBase'=>true]); ?></a>
</p>