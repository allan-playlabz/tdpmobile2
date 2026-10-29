
<div style="margin:20px auto;" class="card">
  <div class="card-body">
  <?= $this->Flash->render('flash', ['element'=>'Flash/default']) ?>
  <?= $this->Form->create() ?>
    <!-- <h2 class="form-signin-heading"><img src="/img/winner_logo_header.png"/></h2> -->
    <?php if (($method ?? null) === 'authenticator'): ?>
    <h3 class="subtitle text-center"><?= __('Authenticator code') ?></h3>
    <p class="form-description" ><?= __('Enter the 6-digit code from your authenticator app.') ?></p>
    <div style="margin-bottom: 8px;">
      <?= $this->Form->control('otp', ['label'=>'', 'class'=>'form-control', 'placeholder'=>__('6-digit code'), 'inputmode'=>'numeric', 'autocomplete'=>'one-time-code', 'autofocus'=>true]) ?>
    </div>
    <p class="small text-muted" style="margin-bottom: 24px;"><?= __('Lost your phone? Enter one of your backup codes (XXXX-XXXX) instead.') ?></p>
    <?php else: ?>
    <h3 class="subtitle text-center"><?= __('OTP') ?></h3>
    <p class="form-description" ><?= __('Please enter your One Time Password (OTP) sent to your email.') ?></p>
    <div style="margin-bottom: 24px;">
      <?= $this->Form->control('otp', ['label'=>'', 'class'=>'form-control', 'placeholder'=>__('One Time Password'), 'inputmode'=>'numeric', 'autocomplete'=>'one-time-code']) ?>
    </div>
    <?php endif ?>
    <?= $this->Form->button(__('LOG IN'),['class'=>'btn btn-primary btn-raised']); ?>
  <?= $this->Form->end() ?>
  <?php if (($method ?? null) !== 'authenticator'): ?>
  <br>
  <?php echo $this->Form->postLink(__('Resend OTP'),['action'=>'resendOtp'],['class'=>'btn btn-primary']) ?>
  <?php endif ?>
  </div>
</div>
<?php $now = \Cake\I18n\DateTime::now(); ?>
<div class="auth-timestamp">
  <i class="fa fa-clock-o"></i>
  <time datetime="<?= h($now->toIso8601String()) ?>"><?= h($now->i18nFormat("EEEE, d MMMM y '·' h:mm a (O)")) ?></time>
</div>
<style>
.form-description {text-align: center;}
</style>