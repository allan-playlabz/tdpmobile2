<div class="text-center box-wrapper">
<?php echo $this->Form->create(null,['class'=>'form']) ?>
  <h1 class="h3 mb-3 fw-normal"><?= __('Set Password') ?></h1>
  <p><?= __('Please set your password to complete setting up your account') ?>.</p>
  <?php echo $this->Form->control('password',['class'=>'form-control','placeholder'=>__('Password'),'label'=>['class'=>'visually-hidden']]) ?>
  <?php echo $this->Form->control('confirm_password',['class'=>'form-control','type'=>'Password','placeholder'=>__('Confirm Password'),'label'=>['class'=>'visually-hidden']]) ?>
  <button class="btn btn-lg btn-primary w-100" type="submit"><?= __('Save') ?></button>
<?php echo $this->Form->end() ?>
</div>
