<?php
/**
 * Flash message (alert-warning). Dismissed with the close button (Bootstrap data API) — no inline handlers.
 *
 * @var \App\View\AppView $this
 * @var string $message
 * @var array $params
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="fa fa-exclamation-triangle alert-icon" aria-hidden="true"></i>
    <div class="alert-body"><?= $message ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= __('Close') ?>"></button>
</div>
