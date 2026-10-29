<?php
/**
 * @var \App\View\AppView $this
 * @var string $qr Inline SVG
 * @var string $secret Secret in groups of four
 * @var string $email
 * @var bool $loggedIn
 */
$this->Html->css(['two-factor'], ['block' => true]);
?>
<div class="tf-card text-left" style="margin-top:20px;">
    <?php if (!$loggedIn) : ?>
        <?= $this->Flash->render('flash', ['element' => 'Flash/default']) ?>
    <?php endif; ?>
    <h4 class="mb-1"><?= __('Set up your authenticator app') ?></h4>
    <p class="text-muted small mb-4"><?= $loggedIn ? __('Scan the new code with your phone. Your old app codes stop working once this is confirmed.') : __('Your account now asks for a code from an authenticator app when you sign in.') ?></p>

    <ol class="tf-steps">
        <li>
            <div class="tf-step-title"><?= __('Install an authenticator app') ?></div>
            <div class="small text-muted"><?= __('Google Authenticator or Microsoft Authenticator (free on iPhone and Android). No Google or Microsoft account is needed.') ?></div>
        </li>
        <li>
            <div class="tf-step-title"><?= __('Scan this QR code') ?></div>
            <div class="small text-muted mb-2"><?= __('In the app tap +, then "Scan a QR code".') ?></div>
            <div class="tf-qr"><?= $qr ?></div>
            <details class="small mt-2">
                <summary><?= __('Can\'t scan? Enter this key instead') ?></summary>
                <div class="mt-2"><?= __('Account: {0}', h($email)) ?></div>
                <code class="tf-secret"><?= h($secret) ?></code>
                <div class="text-muted"><?= __('Time-based, 6 digits.') ?></div>
            </details>
        </li>
        <li>
            <div class="tf-step-title"><?= __('Enter the 6-digit code from the app') ?></div>
            <?= $this->Form->create(null, ['autocomplete' => 'off', 'class' => 'mt-2']) ?>
            <div class="tf-row">
                <?= $this->Form->text('code', ['class' => 'form-control tf-code', 'inputmode' => 'numeric', 'autocomplete' => 'one-time-code', 'maxlength' => 6, 'placeholder' => '123456', 'required' => true, 'autofocus' => true]) ?>
                <?= $this->Form->button(__('Confirm'), ['class' => 'btn btn-primary']) ?>
            </div>
            <?= $this->Form->end() ?>
        </li>
    </ol>
    <?php if ($loggedIn) : ?>
        <?= $this->Html->link(__('Cancel'), ['action' => 'twoFactor'], ['class' => 'small']) ?>
    <?php endif; ?>
</div>
