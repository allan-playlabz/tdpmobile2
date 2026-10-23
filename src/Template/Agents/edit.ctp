<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Agent $agent
 */
?>
<div class=" content">
    <?= $this->Form->create($agent) ?>
    <fieldset>
        <legend><?= __('Edit Agent') ?></legend>
        <?php
            echo $this->Form->control('first_name',['class'=>'form-control']);
            echo $this->Form->control('last_name',['class'=>'form-control']);
            echo $this->Form->control('email',['class'=>'form-control']);
            echo $this->Form->control('company_name',['class'=>'form-control']);
            echo $this->Form->control('country',['class'=>'form-control']);
            echo $this->Form->control('contact_no',['class'=>'form-control']);
            echo $this->Form->control('gender',['class'=>'form-control']);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit'),['class'=>'btn btn-primary']) ?>
    <?= $this->Form->end() ?>
</div>
