<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Agent $agent
 */
?>
<div class="row">
    <div class="col-md-6">
        <?= $this->Form->create($agent_user) ?>
        <fieldset>
            <legend><?= __('Edit Account Details') ?></legend>
            <?php
                echo $this->Form->control('name',['class'=>'form-control']);
                echo $this->Form->control('role',['options'=>['normal'=>__('Normal'),'viewer'=>__('Viewer')]]);
            ?>
        </fieldset>
        <?= $this->Form->button(__('Update'),['class'=>'btn btn-primary']) ?>
        <?= $this->Form->end() ?>
    </div>
</div>