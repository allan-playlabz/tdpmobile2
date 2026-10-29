<?php
/**
 * @var \App\View\AppView $this
 * @var bool $required Whether 2FA applies to this user
 * @var bool $usesApp Whether the method is the authenticator app
 * @var bool $isSetUp Whether an authenticator is linked
 * @var \Cake\I18n\DateTime|null $setUpOn
 * @var int $codesLeft
 */
$this->Html->css(['two-factor'], ['block' => true]);
$codeField = fn() => $isSetUp
    ? $this->Form->text('code', ['class' => 'form-control form-control-sm tf-code', 'inputmode' => 'numeric', 'autocomplete' => 'one-time-code', 'maxlength' => 6, 'placeholder' => __('App code'), 'required' => true])
    : '';
?>
<div class="row">
    <div class="col">
        <h3><?= __('Two-factor sign-in') ?></h3>
    </div>
</div>

<div class="row">
    <div class="col-lg-7 mb-3">
        <div class="card">
            <div class="card-body d-flex align-items-start">
                <div class="tf-status-icon mr-3 <?= $required ? 'is-on' : '' ?>"><i class="fa fa-<?= $usesApp ? 'mobile' : ($required ? 'envelope' : 'unlock') ?>"></i></div>
                <div class="flex-grow-1">
                    <?php if (!$required) : ?>
                        <div class="font-weight-bold"><?= __('Two-factor sign-in is off') ?></div>
                        <div class="small text-muted"><?= __('You sign in with your password only. Contact us to turn it on.') ?></div>
                    <?php elseif (!$usesApp) : ?>
                        <div class="font-weight-bold"><?= __('Email codes') ?></div>
                        <div class="small text-muted"><?= __('When you sign in from a new IP address, we email you a one-time code.') ?></div>
                    <?php elseif ($isSetUp) : ?>
                        <div class="font-weight-bold"><?= __('Authenticator app is set up') ?></div>
                        <div class="small text-muted">
                            <?= $setUpOn ? __('Since {0}', h($setUpOn->i18nFormat('d MMM y'))) . ' &middot; ' : '' ?>
                            <?= __n('{0} backup code left', '{0} backup codes left', $codesLeft, $codesLeft) ?>
                        </div>
                    <?php else : ?>
                        <div class="font-weight-bold"><?= __('No authenticator app yet') ?></div>
                        <div class="small text-muted"><?= __('You will be asked to set one up at your next login, or set it up now.') ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php if ($usesApp) : ?>
    <div class="col-lg-5">
        <?php if ($isSetUp) : ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="font-weight-bold mb-1"><?= __('New backup codes') ?></div>
                    <p class="small text-muted"><?= __('Replaces all your backup codes, including unused ones.') ?></p>
                    <?= $this->Form->create(null, ['autocomplete' => 'off', 'class' => 'tf-row']) ?>
                    <?= $codeField() ?>
                    <?= $this->Form->button(__('Create new codes'), ['name' => 'do', 'value' => 'regenerate', 'class' => 'btn btn-outline-primary btn-sm text-nowrap']) ?>
                    <?= $this->Form->end() ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <div class="font-weight-bold mb-1"><?= $isSetUp ? __('Set up a new phone') : __('Set up an authenticator app') ?></div>
                <p class="small text-muted"><?= $isSetUp ? __('Moves sign-in to a new device; the old one stops working.') : __('Use Google Authenticator or Microsoft Authenticator.') ?></p>
                <?= $this->Form->create(null, ['autocomplete' => 'off', 'class' => 'tf-row']) ?>
                <?= $codeField() ?>
                <?= $this->Form->button($isSetUp ? __('Continue') : __('Set up'), ['name' => 'do', 'value' => 'setup', 'class' => 'btn btn-primary btn-sm text-nowrap']) ?>
                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
