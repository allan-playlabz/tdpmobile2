<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Agent $agent
 */
?>
<div class="card m-auto" style="width: 18rem">
  <img src="/img/user.jpg" class="card-img-top" alt="...">
  <div class="card-body">
    <div class="text-center">
    <h5 class="card-title"><?= $agent->first_name ?> <?= $agent->last_name ?></h5>
    <div><?= h($agent->email) ?></div>
    <div>Current Credit: <?= $this->Number->currency($agent->credits) ?></div>
    <br>
    <a href="<?= $this->Url->build(['action'=>'edit',$agent->id]) ?>" class="btn btn-primary">Edit</a>
    </div>
  </div>
</div>
