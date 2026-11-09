<?php
/**
 * Thank you (step 4): order reference, summary and next actions; polls the status while the
 * order is still processing. CSS: theme.css (.page-orders-thank-you). JS: pages/orders-thank-you.js.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Order|null $order
 */
use Cake\Core\Configure;
use Cake\Utility\Inflector;

$status = $order->status ?? null;
$ref = $order->order_ref_id ?? null;
$viewUrl = $ref ? $this->Url->build(['controller' => 'Orders', 'action' => 'view', $ref]) : null;

if (!empty($order) && !in_array($status, ['done', 'failed'], true)) {
    $this->set('pageData', [
        'orderRef' => $ref,
        'urls' => [
            'status' => $this->Url->build(['controller' => 'Orders', 'action' => 'getStatus']),
            'thankYou' => $this->Url->build(['controller' => 'Orders', 'action' => 'thankYou']),
            'home' => $this->Url->build('/'),
        ],
        'i18n' => [
            'completing' => __('<p>We are completing your order now.</p>
		<p>This may take typically 5-15 minutes. During peak period, it may take up to 30 minutes for the attraction to return with a confirmation.</p>
		<p>You will receive an email with the tickets once completed.</p>
		<p>For assistance on your ticket or if you do not receive the final ticket after 30 minutes, please get in touch with our Customer Service Agent with the following Order Reference: '),
            'completingEnd' => __('.</p>'),
        ],
    ]);
    $this->Html->script('pages/orders-thank-you.js?v=20261106', ['block' => 'script']);
}
?>
<?= $this->element('checkout_steps', ['current' => $status === 'done' ? 5 : 4]) ?>

<div class="thank-you mx-auto">
  <div class="text-center mb-4">
    <?php if ($status === 'failed'): ?>
      <div class="thank-you-icon is-failed" aria-hidden="true"><i class="fa fa-times"></i></div>
    <?php elseif ($status === 'done'): ?>
      <div class="thank-you-icon" aria-hidden="true"><i class="fa fa-check"></i></div>
    <?php else: ?>
      <div class="thank-you-icon is-pending" aria-hidden="true"><i class="fa fa-hourglass-half"></i></div>
    <?php endif ?>
    <h1 class="thank-you-title"><?= __('Thank you for your purchase!') ?></h1>
    <?php if ($ref): ?>
      <div class="order-ref" data-order-ref="<?= h($ref) ?>">
        <span class="price-label"><?= __('Order Reference') ?></span>
        <span class="order-ref-value" id="order-ref"><?= h($ref) ?></span>
        <button type="button" class="btn btn-sm btn-outline-primary" data-copy="<?= h($ref) ?>" data-copied="<?= __('Order reference copied') ?>" aria-label="<?= __('Copy order reference') ?>"><i class="fa fa-clone" aria-hidden="true"></i></button>
      </div>
    <?php endif ?>
  </div>

  <?php if ($status === 'done'): ?>
    <div class="sg-panel p-4 mb-4">
      <?php if (!empty($order->order_items)): ?>
        <h2 class="h5 mb-3"><?= __('Order summary') ?></h2>
        <ul class="list-unstyled thank-you-items mb-3">
          <?php foreach ($order->order_items as $item): ?>
            <li>
              <div class="min-w-0">
                <div class="fw-semibold"><?= h($item->ticket->attraction->title ?? '', false) ?></div>
                <div class="small text-muted">
                  <?= h($item->ticket->name ?? '', false) ?>
                  <?php if (!empty($item->date_of_visit)): ?> · <?= $item->date_of_visit->format('d M Y') ?><?= !empty($item->time_of_visit) && ($item->ticket->attraction->pricing_type ?? '') === 'fdatetime' ? ' ' . $item->time_of_visit->format('H:i') : '' ?><?php endif ?>
                </div>
              </div>
              <div class="text-end text-nowrap">
                <span class="small text-muted">&times; <?= (int)$item->qty ?></span>
                <div class="fw-semibold"><?= $this->Number->currency($item->price * $item->qty) ?></div>
              </div>
            </li>
          <?php endforeach ?>
        </ul>
      <?php endif ?>
      <dl class="sg-dl sg-dl-inline mb-0">
        <?php if ($order->total !== null): ?>
          <div><dt><?= __('Total') ?></dt><dd class="price-net"><?= $this->Number->currency($order->total) ?></dd></div>
        <?php endif ?>
        <?php if (!empty($order->payment_method)): ?>
          <div><dt><?= __('Payment Method') ?></dt><dd><?= h(__(Inflector::humanize((string)$order->payment_method))) ?></dd></div>
        <?php endif ?>
        <?php if (!empty($order->created)): ?>
          <div><dt><?= __('Purchase Date') ?></dt><dd><?= $order->created->format('d M Y H:i') ?></dd></div>
        <?php endif ?>
      </dl>
    </div>

    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
      <a class="btn btn-cta btn-lg" href="<?= $viewUrl ?>"><i class="fa fa-download me-2" aria-hidden="true"></i><?= __('View order & download vouchers') ?></a>
      <?php if (Configure::read('Features.invoice')): ?>
        <?= $this->Html->link('<i class="fa fa-file-pdf-o me-2" aria-hidden="true"></i>' . __('Download invoice'), ['controller' => 'Orders', 'action' => 'downloadInvoice', $ref], ['class' => 'btn btn-outline-primary btn-lg', 'escape' => false]) ?>
      <?php endif ?>
      <?= $this->Html->link('<i class="fa fa-plus me-2" aria-hidden="true"></i>' . __('Book more attractions'), '/', ['class' => 'btn btn-link btn-lg', 'escape' => false]) ?>
    </div>
    <p class="text-center small text-muted mt-3"><?= __('The vouchers are also sent by email.') ?></p>

  <?php elseif ($status === 'failed'): ?>
    <div class="alert alert-danger" role="alert">
      <i class="fa fa-exclamation-circle alert-icon" aria-hidden="true"></i>
      <div class="alert-body">
        <?= __('There is an error processing this order. Please try again or contact admin.') ?>
        <?= $this->Html->link(__('Contact Us'), ['controller' => 'AgentUsers', 'action' => 'contact'], ['class' => 'alert-link ms-1']) ?>
      </div>
    </div>
    <div class="text-center">
      <?= $this->Html->link(__('Back to cart'), ['controller' => 'Orders', 'action' => 'cart'], ['class' => 'btn btn-outline-primary']) ?>
    </div>

  <?php elseif (!empty($order)): ?>
    <div class="alert alert-info processing-message" role="status">
      <span class="spinner-border spinner-border-sm alert-icon" aria-hidden="true"></span>
      <div class="alert-body processing-text"><?= __('We are processing your order. Please do not navigate away from this page. We will show you the details here once done.') ?></div>
    </div>
  <?php endif ?>
</div>
