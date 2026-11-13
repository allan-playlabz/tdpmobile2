<?php
/**
 * "Try another way": Cloudflare Turnstile check, then an emailed sign-in code.
 * Script: webroot/js/pages/agent-users-otp-another-way.js (enables the button).
 *
 * @var \App\View\AppView $this
 * @var string $maskedEmail
 * @var string $siteKey Turnstile site key
 */
$this->Html->script('https://challenges.cloudflare.com/turnstile/v0/api.js', ['block' => 'script', 'async' => true, 'defer' => true]);
$this->Html->script('pages/agent-users-otp-another-way.js', ['block' => 'script']);
?>
<div class="card mx-auto my-3">
  <div class="card-body">
    <?= $this->Flash->render('flash', ['element' => 'flash/default']) ?>
    <h3 class="subtitle text-center"><?= __("Verify it's you") ?></h3>
    <p class="form-description"><?= __('We will email a one-time code to {0}. Complete the check below to send it.', '<strong>' . h($maskedEmail) . '</strong>') ?></p>
    <?= $this->Form->create(null, ['id' => 'another-way-form']) ?>
      <div class="cf-turnstile mb-3" data-sitekey="<?= h($siteKey) ?>" data-action="email-otp" data-callback="sgTurnstileOk" data-expired-callback="sgTurnstileExpired" data-error-callback="sgTurnstileExpired"></div>
      <?= $this->Form->button(__('Send code to my email'), ['class' => 'btn btn-primary btn-raised', 'id' => 'another-way-submit', 'disabled' => true]) ?>
    <?= $this->Form->end() ?>
    <p class="small mt-3 mb-0"><?= $this->Html->link(__('Back to authenticator code'), ['action' => 'otp']) ?></p>
  </div>
</div>
