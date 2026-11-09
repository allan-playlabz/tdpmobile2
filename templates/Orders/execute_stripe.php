<?php
/**
 * Redirects to Stripe Checkout (layout "empty": this template is the whole page).
 * No inline script (CSP): webroot/js/pages/orders-execute-stripe.js reads the publishable key and
 * session id from the data-* attributes below. The policy must allow https://js.stripe.com.
 *
 * @var \App\View\AppView $this
 * @var string $session_id
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow" />
    <title><?= __('Redirecting to payment') ?></title>
    <?= $this->Html->css(['bootstrap.min.css', 'theme.css?v=20261109c']) ?>
</head>
<body class="page-orders-execute-stripe">
    <div id="stripe-redirect"
         class="container py-5 text-center"
         data-key="<?= h((string)\Cake\Core\Configure::read('Payment.Stripe.api_key')) ?>"
         data-session-id="<?= h((string)$session_id) ?>">
        <div class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></div>
        <p class="lead"><?= __('Taking you to the payment page…') ?></p>
        <div id="stripe-error" class="alert alert-danger d-none" role="alert"></div>
    </div>
    <?= $this->Html->script(['https://js.stripe.com/v3/', 'pages/orders-execute-stripe.js?v=20261106']) ?>
</body>
</html>
