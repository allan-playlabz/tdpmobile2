<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Agent $agent
 */
$public_host = \Cake\Core\Configure::read('AWSPublic.public_host');
?>
<div class="card m-auto" style="width: 18rem">
  <?php 
    if(empty($agent->agent->image)){
      echo $this->Html->image('/img/user.jpg',['class'=>'card-img-top','alt'=>$agent->agent->first_name.' picture']);
    }
    else {
      echo $this->Html->image($public_host.$agent->agent->image,['class'=>'card-img-top','alt'=>$agent->agent->first_name.' picture']);
    }
  ?>
  <div class="card-body">
    <div class="text-center">
    <h5 class="card-title"><?= $agent->agent->company_name ?></h5>
    <div><?= h($agent->email) ?></div>
    <div><?= __('Current Credit') ?>: <?= $this->Number->currency($auth_user['credits']) ?></div>
    <br>
    <?php if($auth_user['role'] !== 'master' && $auth_user['role'] !== 'viewer') { ?>
      <a href="<?= $this->Url->build(['action'=>'edit']) ?>" class="btn btn-primary"><?= __('Edit') ?></a>
    <?php } ?>
    <?php if ($auth_user['role'] != 'viewer'): ?>
    <a href="<?= $this->Url->build(['action'=>'changePassword']) ?>" class="btn btn-primary"><?= __('Change Password') ?></a>
    <?php endif ?>
    </div>
  </div>
</div>
