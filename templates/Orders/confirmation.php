<?php
/**
 * Confirmation (step 3): read-only review + payment method + Pay Now.
 * CSS: theme.css (.page-orders-confirmation). JS: pages/orders-confirmation.js (fees, Pay Now).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Order $order
 * @var array $auth_user
 * @var bool $is_credits_enabled
 * @var array $other_payment_methods
 * @var array $markups
 * @var array $payment_providers
 * @var array|null $tickets_with_question_group
 */
use Cake\Core\Configure;
use Cake\Utility\Inflector;

$subtotal = 0;
$other_fees = 0;
if ($this->request->getSession()->check('language') && !empty($other_payment_methods)) {
    foreach ($other_payment_methods as $key => $name) {
        $other_payment_methods[$key] = __($name);
    }
}
$other_payment_methods = (array)$other_payment_methods;
$creditsShort = $is_credits_enabled && $auth_user['credits'] < $order->total;
$providerIcons = ['Stripe' => 'fa-cc-stripe', 'Paypal' => 'fa-paypal', '2C2P' => 'fa-credit-card'];
foreach ((array)$order->order_items as $order_item) {
    $subtotal += $order_item->price * $order_item->qty;
}
$checkout_info = null;
if (!empty($order->checkout_info) && !is_array($order->checkout_info)) {
    $checkout_info = json_decode($order->checkout_info);
}

$this->set('pageData', [
    'subtotal' => (float)$subtotal,
    'otherFees' => (float)$other_fees,
    'otherPaymentMethods' => array_keys($other_payment_methods),
    'markups' => $markups ?: new stdClass(),
    'providers' => $payment_providers ?: new stdClass(),
    'i18n' => [
        'convenienceFee' => __('Convenience Fee'),
        'chooseMethod' => __('Please choose a payment method!'),
        'processing' => __('We are processing your order. Please do not navigate away from this page'),
        'redirecting' => __('Taking you to the payment page…'),
        'fee' => __('+ {0} fee', '{fee}'),
        'noFee' => __('No fee'),
    ],
]);
$this->Html->script('pages/orders-confirmation.js', ['block' => 'script']);
?>
<?= $this->element('checkout_steps', ['current' => 3]) ?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <h1 class="mb-0"><?= __('Confirmation') ?></h1>
  <?= $this->Html->link('<i class="fa fa-chevron-left" aria-hidden="true"></i> ' . __('Back'), ['action' => 'checkout'], ['escape' => false, 'class' => 'btn btn-link px-0']); ?>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <?php if (!empty($checkout_info)): ?>
      <div class="card sg-card mb-4 customer-summary">
        <div class="card-header">
          <i class="fa fa-user-o text-navy" aria-hidden="true"></i><?= __('Customer Information') ?>
          <?= $this->Html->link('<i class="fa fa-pencil me-1" aria-hidden="true"></i>' . __('Edit'), ['action' => 'checkout'], ['escape' => false, 'class' => 'btn btn-sm btn-outline-primary ms-auto']) ?>
        </div>
        <div class="card-body">
          <dl class="sg-dl">
            <div><dt><?= __('First Name') ?></dt><dd data-field="first_name"><?= h($checkout_info->first_name ?? '') ?></dd></div>
            <div><dt><?= __('Last Name') ?></dt><dd data-field="last_name"><?= h($checkout_info->last_name ?? '') ?></dd></div>
            <div><dt><?= __('Email') ?></dt><dd data-field="email"><?= h($checkout_info->email ?? '') ?></dd></div>
            <div><dt><?= __('Mobile') ?></dt><dd data-field="mobile"><?= h($checkout_info->mobile ?? '') ?></dd></div>
            <div><dt><?= __('Order Tracking Number') ?></dt><dd data-field="tracking_no"><?= ($checkout_info->tracking_no ?? '') !== '' ? h($checkout_info->tracking_no) : '<span class="text-muted">—</span>' ?></dd></div>
          </dl>
        </div>
      </div>
    <?php endif ?>

    <?php if (!empty($order->order_items)): ?>
      <?php
      $required_info = [];
      $transfer_details = [];
      $return_transfer_details = [];
      foreach ($order->order_items as $order_item) {
          $order_item_data = json_decode($order_item->order_data);
          if (!empty($order_item_data)) {
              if (!empty($order_item_data->required_info)) {
                  $required_info = array_merge($required_info, (array)$order_item_data->required_info);
              }
              if (!empty($order_item_data->transfer_details)) {
                  $transfer_details = $order_item_data->transfer_details;
              }
              if (!empty($order_item_data->return_transfer_details)) {
                  $return_transfer_details = $order_item_data->return_transfer_details;
              }
          }
      }
      ?>
      <?php if (!empty($required_info) || !empty($transfer_details) || !empty($return_transfer_details)): ?>
        <div class="card sg-card mb-4">
          <div class="card-header"><i class="fa fa-id-card-o text-navy" aria-hidden="true"></i><?= __('Order Information') ?></div>
          <div class="card-body">
            <?php if (!empty($required_info)): ?>
              <h2 class="sg-section-title"><?= __('Travellers') ?></h2>
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead>
                    <tr>
                      <th><?= __('Passport Number') ?></th>
                      <th><?= __('Passport Expiry') ?></th>
                      <th><?= __('First Name') ?></th>
                      <th><?= __('Last Name') ?></th>
                      <th><?= __('Date of Birth') ?></th>
                      <th><?= __('Nationality') ?></th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($required_info as $td): ?>
                    <tr>
                      <td><?= h($td->passport_number ?? '') ?></td>
                      <td><?= h($td->passport_expiry ?? '') ?></td>
                      <td><?= h($td->passport_first_name ?? '') ?></td>
                      <td><?= h($td->passport_last_name ?? '') ?></td>
                      <td><?= h($td->date_of_birth ?? '') ?></td>
                      <td><?= h($td->nationality ?? '') ?></td>
                    </tr>
                    <?php if (!empty($td->remarks)): ?>
                      <tr><td colspan="6" class="fst-italic text-muted"><?= h($td->remarks) ?></td></tr>
                    <?php endif ?>
                  <?php endforeach ?>
                  </tbody>
                </table>
              </div>
            <?php endif ?>
            <?php foreach (['Transfer Details' => $transfer_details, 'Return Transfer Details' => $return_transfer_details] as $title => $details): ?>
              <?php if (!empty($details)): ?>
                <h2 class="sg-section-title"><?= __($title) ?></h2>
                <dl class="sg-dl">
                  <?php foreach ($details as $key => $value): ?>
                    <div><dt><?= h(Inflector::humanize($key)) ?></dt><dd><?= h(is_scalar($value) ? (string)$value : json_encode($value)) ?></dd></div>
                  <?php endforeach ?>
                </dl>
              <?php endif ?>
            <?php endforeach ?>
          </div>
        </div>
      <?php endif ?>

      <?php if (!empty($tickets_with_question_group)): ?>
      <div class="card sg-card mb-4">
        <div class="card-header"><i class="fa fa-list-alt text-navy" aria-hidden="true"></i>Ticket Information</div>
        <div class="card-body ticket-details-container">
        <?php foreach ($order->order_items as $order_item):
            $order_item_data = json_decode($order_item->order_data);
            if (empty($order_item_data) || empty($order_item_data->question_group)) {
                continue;
            }
            $question_group_answers = (array)$order_item_data->question_group;
            $question_group_keys = (array)$question_group_answers[0];
            $order_data_titles = array_combine(
                array_keys($question_group_keys),
                array_map(fn($k) => Inflector::humanize($k), array_keys($question_group_keys))
            );
            $question_group_title = $tickets_with_question_group[$order_item->ticket->id]['title'] ?? '';
        ?>
          <div class="traveller-group">
            <div class="form_title">
              <div class="fw-bold"><?= h($order_item->ticket->attraction->title, false) ?></div>
              <div class="text-muted">
                <?= h($order_item->ticket->name, false) ?>
                <?php if (!empty($order_item->date_of_visit)): ?><span class="badge badge-soft ms-1"><?= $order_item->date_of_visit->format('d M Y') ?><?= !empty($order_item->time_of_visit) ? ' ' . $order_item->time_of_visit->format('H:i') : '' ?></span><?php endif ?>
              </div>
              <?php if ($question_group_title): ?><p class="small text-muted mb-2"><?= h($question_group_title) ?></p><?php endif ?>
            </div>
            <?php foreach ($question_group_answers as $row): ?>
              <div class="table-responsive mb-1">
                <table class="table table-sm mb-2">
                  <tbody>
                    <?php foreach ($row as $title => $value): ?>
                      <tr>
                        <th class="question-group-th"><?= h($order_data_titles[$title] ?? Inflector::humanize($title)) ?></th>
                        <td><?= h(is_array($value) ? implode(', ', $value) : (is_object($value) ? implode(', ', (array)$value) : (string)$value)) ?></td>
                      </tr>
                    <?php endforeach ?>
                  </tbody>
                </table>
              </div>
            <?php endforeach ?>
          </div>
        <?php endforeach ?>
        </div>
      </div>
      <?php endif ?>
    <?php endif ?>

    <div class="card sg-card mb-4 payment-card">
      <div class="card-header"><i class="fa fa-lock text-navy" aria-hidden="true"></i><?= __('Payment Information') ?></div>
      <div class="card-body">
        <?= $this->Form->create($order, ['id' => 'confirmation-form', 'class' => 'form-deferred']) ?>
          <fieldset>
            <legend class="booking-step-label mb-2"><?= __('Pay By *') ?></legend>
            <input type="hidden" name="pay_by" value="">
            <div class="pay-methods">
              <?php if ($is_credits_enabled): ?>
                <div class="pay-method<?= $creditsShort ? ' is-disabled' : '' ?>">
                  <input class="form-check-input" type="radio" name="pay_by" id="pay-by-credits" value="credits"<?= $creditsShort ? ' disabled' : '' ?><?= !$creditsShort && $order->payment_method === 'credits' ? ' checked' : '' ?>>
                  <label class="pay-method-label" for="pay-by-credits">
                    <span class="pay-method-icon"><i class="fa fa-money" aria-hidden="true"></i></span>
                    <span class="pay-method-text">
                      <span class="pay-method-name"><?= __('Credits') ?></span>
                      <span class="pay-method-sub"><?= __('Balance: {0}', $this->Number->currency($auth_user['credits'])) ?></span>
                    </span>
                    <span class="pay-method-fee" data-method="credits"></span>
                  </label>
                  <div class="pay-method-extra">
                    <?php if ($creditsShort): ?>
                      <span class="text-danger small"><i class="fa fa-exclamation-circle me-1" aria-hidden="true"></i><?= __('Insufficient credits — short by {0}.', $this->Number->currency($order->total - $auth_user['credits'])) ?></span>
                    <?php endif ?>
                    <?= $this->Html->link('<i class="fa fa-plus-circle me-1" aria-hidden="true"></i>' . __('Topup'), ['controller' => 'AgentTopups', 'action' => 'add', '?' => ['redirect' => 'orders/confirmation']], ['escape' => false, 'class' => 'btn btn-sm ' . ($creditsShort ? 'btn-outline-primary' : 'btn-link px-0')]) ?>
                  </div>
                </div>
              <?php endif ?>
              <?php foreach ($other_payment_methods as $key => $name): ?>
                <?php $provider = $payment_providers[$key] ?? null; ?>
                <div class="pay-method">
                  <input class="form-check-input" type="radio" name="pay_by" id="pay-by-<?= h($key) ?>" value="<?= h($key) ?>"<?= $order->payment_method === $key ? ' checked' : '' ?>>
                  <label class="pay-method-label" for="pay-by-<?= h($key) ?>">
                    <span class="pay-method-icon"><i class="fa <?= $providerIcons[$provider] ?? 'fa-credit-card' ?>" aria-hidden="true"></i></span>
                    <span class="pay-method-text">
                      <span class="pay-method-name"><?= $name ?></span>
                      <span class="pay-method-sub"><?= __('Online payment') ?></span>
                    </span>
                    <span class="pay-method-fee" data-method="<?= h($key) ?>"></span>
                  </label>
                </div>
              <?php endforeach ?>
            </div>
          </fieldset>
        <?= $this->Form->end() ?>
        <div class="form-loader"><div class="spinner-border" role="status"><span class="visually-hidden"><?= __('Loading') ?></span></div></div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="sg-sticky-summary">
      <div id="tickets-sidebar" class="sg-summary">
        <?php if (!empty($order->order_items)): ?>
          <div class="sg-summary-header"><i class="fa fa-ticket me-2" aria-hidden="true"></i><?= __('Order summary') ?></div>
          <?php foreach ($order->order_items as $order_item): ?>
            <div class="card sg-summary-item">
              <div class="card-body">
                <div><?= $this->Html->link(
                    '<div class="fw-bold">' . h($order_item->ticket->attraction->title, false) . '</div>'
                    . '<div class="text-muted">' . h($order_item->ticket->name, false) . '<br><del class="price-ref">RP ' . $this->Number->currency($order_item->ticket->retail_price ?? $order_item->ticket->price) . '</del></div>',
                    ['controller' => 'Attractions', 'action' => 'view', $order_item->ticket->attraction_id],
                    ['escape' => false, 'class' => 'sg-summary-link']
                ) ?></div>
                <div class="sg-summary-unit"><?= $this->Number->currency($order_item->price) ?></div>
                <div class="row py-2 g-0 sg-summary-row">
                  <div class="col-md-6 col-6">
                    <?php if ($order_item->ticket->attraction->pricing_type == 'fdate' && $order_item->date_of_visit): ?>
                      <div><?= $order_item->date_of_visit->format('d M Y') ?></div>
                    <?php endif ?>
                    <?php if ($order_item->ticket->attraction->pricing_type == 'fdatetime' && $order_item->time_of_visit): ?>
                      <div><?= $order_item->time_of_visit->format('H:i') ?></div>
                    <?php endif ?>
                  </div>
                  <div class="col-md-2 col-2 text-end"><span>&times; <?= $order_item->qty ?></span></div>
                  <div class="col-md-4 col-4 text-end fw-semibold"><?= $this->Number->currency($order_item->price * $order_item->qty) ?></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          <div class="footer-total">
            <div class="row">
              <div class="col"><?= __('Subtotal') ?></div><div class="col text-end"><?= $this->Number->currency($subtotal) ?></div>
            </div>
            <div class="row grandtotal-row">
              <div class="col"><?= __('Grand Total') ?></div><div class="col text-end"><?= $this->Number->currency($subtotal + $other_fees) ?></div>
            </div>
          </div>
        <?php endif ?>
        <div class="sg-summary-pay">
          <?= $this->Form->button('<i class="fa fa-lock me-2" aria-hidden="true"></i>' . __('Pay Now'), ['class' => 'btn btn-cta btn-lg w-100', 'id' => 'btn-pay-now', 'type' => 'button', 'form' => 'confirmation-form', 'escapeTitle' => false, 'disabled' => $order->payment_method == null]); ?>
          <div class="pay-now-hint small text-muted text-center mt-2"><?= __('Choose a payment method to continue.') ?></div>
          <div class="pay-status" aria-live="polite"></div>
        </div>
      </div>
    </div>
  </div>
</div>
