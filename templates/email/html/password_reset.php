<?php
$reset_link = $this->Url->build(['prefix'=>false,'controller'=>'AgentUsers','action'=>'resetPassword',$data['password_reset']->email,$data['password_reset']->reset_code],['fullBase'=>true]); ?>
<p>Hi <?= $data['agent']->fullname ?? $data['agent']->name ?>,	</p>
 <p>We have received your request to reset your account password in <?= $this->Url->build('/',['fullBase'=>true]); ?>.</p>
<p>Please click or follow this link <a href="<?php echo $reset_link ?>"><?php echo $reset_link ?></a> to reset your password. <span style="color:red">This link is valid only for <strong>24 hours</strong>.</span></p>
<p>If you did not request to reset your password just ignore this email.</p>
<p>Regards,</p>
<p>Your Friends at <?= \Cake\Core\Configure::read('Default.site_name') ?><br>
<a href="<?= $this->Url->build('/',['fullBase'=>true]); ?>"><?= $this->Url->build('/',['fullBase'=>true]); ?></a>
</p>
