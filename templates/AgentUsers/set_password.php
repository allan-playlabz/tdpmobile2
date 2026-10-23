<div class="text-center box-wrapper">
<?php echo $this->Form->create(null,['class'=>'form']) ?>
  <h1 class="h3 mb-3 font-weight-normal"><?= __('Set Password') ?></h1>
  <p><?= __('Please set your password to complete setting up your account') ?>.</p>
  <?php echo $this->Form->control('password',['class'=>'form-control','placeholder'=>__('Password'),'label'=>['class'=>'sr-only']]) ?>
  <?php echo $this->Form->control('confirm_password',['class'=>'form-control','type'=>'Password','placeholder'=>__('Confirm Password'),'label'=>['class'=>'sr-only']]) ?>
  <button class="btn btn-lg btn-primary btn-block" type="submit"><?= __('Save') ?></button>
<?php echo $this->Form->end() ?>
</div>
