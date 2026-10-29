<?php
/**
 * @var \App\View\AppView $this
 * @var array<string> $codes
 * @var bool $loggedIn
 */
$this->Html->css(['two-factor'], ['block' => true]);
?>
<div class="tf-card text-left" style="margin-top:20px;">
    <h4 class="mb-1"><?= __('Save your backup codes') ?></h4>
    <p class="text-muted small"><?= __('If you lose your phone, sign in with one of these codes instead of an app code. Each code works once.') ?></p>
    <div class="alert alert-warning small py-2"><i class="fa fa-exclamation-triangle"></i> <?= __('These codes are shown only once. Print them or store them in a password manager.') ?></div>

    <ol class="tf-codes" id="tf-codes">
        <?php foreach ($codes as $code) : ?>
            <li><code><?= h($code) ?></code></li>
        <?php endforeach; ?>
    </ol>

    <div class="tf-row mb-4 tf-no-print">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print"></i> <?= __('Print') ?></button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="tf-copy"><i class="fa fa-clipboard"></i> <span><?= __('Copy') ?></span></button>
    </div>

    <?= $this->Form->create(null, ['class' => 'tf-no-print']) ?>
    <?= $this->Form->button($loggedIn ? __('I\'ve saved these codes') : __('I\'ve saved these codes, continue'), ['class' => 'btn btn-primary btn-block']) ?>
    <?= $this->Form->end() ?>
</div>
<script>
    document.getElementById('tf-copy').addEventListener('click', function () {
        var text = Array.prototype.map.call(document.querySelectorAll('#tf-codes code'), function (c) { return c.textContent; }).join('\n');
        var label = this.querySelector('span');
        navigator.clipboard.writeText(text).then(function () { label.textContent = <?= json_encode(__('Copied')) ?>; });
    });
</script>
