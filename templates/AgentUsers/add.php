<div class="row">
    <div class="col-md-8">
        <?= $this->Form->create($agent_user) ?>
        <fieldset>
        <?php
            echo $this->Form->control('name',['class'=>'form-control']);
            echo $this->Form->control('email',['class'=>'form-control', 'label' => __('Email')]);
            // echo $this->Form->control('password',['class'=>'form-control','value'=>'']);
            // echo $this->Form->control('confirm_password',['type'=>'password','class'=>'form-control','value'=>'','required'=>false]);
            echo $this->Form->control('role',['options'=>['normal'=>__('Normal'),'viewer'=>__('Viewer')]]);
            echo $this->Form->control('status',['options'=>['active'=>__('Active'),'inactive'=>__('Inactive')]]);
        ?>
        </fieldset>
        <p><?= __('Note: Account temporary password will be sent to users email.') ?></p>
        <?= $this->Form->button(__('Save'),['class'=>'btn btn-primary']) ?>
        <?= $this->Form->end() ?>
    </div>
</div>
