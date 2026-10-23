<div class="row">
    <div class="col">
        <div class="box-wrapper">
            <h3><?= __('Forgot Password') ?></h3>
            <?php
            echo $this->Form->create($password_reset);
            ?>
            <p><?= __('Please enter your email to send the reset link.') ?></p>
            <?php
            echo $this->Form->control('email',['class'=>'form-control']);
            echo $this->Form->submit(__('Send me the link'), array('class' => 'btn btn-primary btn-raised'));
            echo $this->Form->end();
            ?>
        </div>
    </div>
</div>
