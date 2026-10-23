<div class="row">
    <div class="col-sm-12">
        <div class="box-wrapper">
            <?php if (!empty($agent)): ?>
               <h3><?= __('Reset Password') ?></h3>
               <p><?= __('Hi {0}, please set a new password for your account.', $agent->name) ?></p>
                <?php
                echo $this->Form->create($agent);
                ?>
                <?php
                echo $this->Form->control('password',['class'=>'form-control']);
                echo $this->Form->control('confirm_password',['class'=>'form-control','type'=>'password']);
                echo $this->Form->submit(__('Set Password'), array('class' => 'btn btn-primary btn-raised'));
                echo $this->Form->end();
            ?>
            <?php endif ?>

        </div>
    </div>
</div>
