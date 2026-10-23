<p>Hi <?= $data['agent_user']->name ?>,	</p>
 <p>A master agent from <?= \Cake\Core\Configure::read('Default.site_name') ?> has created you an account with a temporary password <b><?= $data['temp_password'] ?></b>.</p>
<p>Please use this temporary password to <?= $this->Html->link('login',['controller'=>'AgentUsers','action'=>'login'],['fullBase'=>true]) ?> and set your own password.</p>
<p>Regards,</p>
<p>Your Friends at  <?= \Cake\Core\Configure::read('Default.site_name') ?><br>
<a href="<?= $this->Url->build('/',['fullBase'=>true]); ?>"><?= $this->Url->build('/',['fullBase'=>true]); ?></a>
</p>
