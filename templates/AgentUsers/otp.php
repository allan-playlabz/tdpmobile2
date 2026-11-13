<?php
/**
 * Second sign-in step: authenticator code, or an emailed OTP (email method, first
 * login before an app is linked, or "Try another way" for an authenticator user).
 *
 * @var \App\View\AppView $this
 * @var string $method TwoFactor::METHOD_AUTHENTICATOR or METHOD_EMAIL
 * @var bool $fallback Authenticator user who chose an emailed code instead
 * @var bool $canTryAnotherWay Show the "Try another way" link (Turnstile configured)
 */
$fallback = $fallback ?? false;
?>
<div class="card mx-auto my-3">
  <div class="card-body">
  <?= $this->Flash->render('flash', ['element'=>'flash/default']) ?>
  <?= $this->Form->create() ?>
    <!-- <h2 class="form-signin-heading"><img src="/img/winner_logo_header.png"/></h2> -->
    <?php if (($method ?? null) === 'authenticator'): ?>
    <h3 class="subtitle text-center"><?= __('Authenticator code') ?></h3>
    <p class="form-description" ><?= __('Enter the 6-digit code from your authenticator app.') ?></p>
    <div class="mb-2">
      <?= $this->Form->control('otp', ['label'=>'', 'class'=>'form-control', 'placeholder'=>__('6-digit code'), 'inputmode'=>'numeric', 'autocomplete'=>'one-time-code', 'autofocus'=>true]) ?>
    </div>
    <p class="small text-muted mb-4"><?= __('Lost your phone? Enter one of your backup codes (XXXX-XXXX) instead.') ?></p>
    <?php else: ?>
    <h3 class="subtitle text-center"><?= __('OTP') ?></h3>
    <p class="form-description" ><?= __('Please enter your One Time Password (OTP) sent to your email.') ?></p>
    <div class="mb-4">
      <?= $this->Form->control('otp', ['label'=>'', 'class'=>'form-control', 'placeholder'=>__('One Time Password'), 'inputmode'=>'numeric', 'autocomplete'=>'one-time-code', 'autofocus'=>$fallback]) ?>
    </div>
    <?php endif ?>
    <?= $this->Form->button(__('LOG IN'),['class'=>'btn btn-primary btn-raised']); ?>
  <?= $this->Form->end() ?>
  <?php if (($method ?? null) !== 'authenticator'): ?>
  <br>
  <a href="#" class="btn btn-primary" data-post-url="<?= $this->Url->build(['action' => 'resendOtp']) ?>"><?= __('Resend OTP') ?></a>
  <?php endif ?>
  <?php if (!empty($canTryAnotherWay)): ?>
  <p class="small mt-3 mb-0">
    <?= __("Don't have your phone?") ?>
    <?= $this->Html->link(__('Try another way'), ['action' => 'otpAnotherWay']) ?>
  </p>
  <?php elseif ($fallback): ?>
  <p class="small mt-3 mb-0">
    <a href="#" data-post-url="<?= $this->Url->build(['action' => 'otpAnotherWay']) ?>" data-post-data='{"do":"cancel"}'><?= __('Use my authenticator app instead') ?></a>
  </p>
  <?php endif ?>
  </div>
</div>
<?php $now = \Cake\I18n\DateTime::now(); ?>
<div class="auth-timestamp">
  <i class="fa fa-clock-o"></i>
  <time datetime="<?= h($now->toIso8601String()) ?>"><?= h($now->i18nFormat("EEEE, d MMMM y '·' h:mm a (O)")) ?></time>
</div>
