<?php
/**
 * Checkout progress: Cart → Details → Confirm & Pay → Done. Styles: theme.css (.sg-steps).
 *
 * @var \App\View\AppView $this
 * @var int $current 1 = cart, 2 = details (checkout), 3 = confirmation, 4 = thank you.
 */
$current = (int)($current ?? 1);
$steps = [
    1 => __('Cart'),
    2 => __('Details'),
    3 => __('Confirm & Pay'),
    4 => __('Done'),
];
?>
<ol class="sg-steps" aria-label="<?= __('Checkout progress') ?>">
  <?php foreach ($steps as $n => $label): ?>
    <?php $state = $n < $current ? 'is-done' : ($n === $current ? 'is-active' : ''); ?>
    <li class="<?= $state ?>"<?= $n === $current ? ' aria-current="step"' : '' ?>>
      <span class="sg-steps-dot" aria-hidden="true"><?= $n < $current ? '<i class="fa fa-check"></i>' : $n ?></span>
      <span class="sg-steps-label"><?= $label ?></span>
    </li>
  <?php endforeach; ?>
</ol>
