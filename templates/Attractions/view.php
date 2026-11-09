<?php
/**
 * Attraction detail + booking panel (settlement / fdate / fdatetime; grouped products).
 * CSS: theme.css (.page-attractions-view). JS: ticket-row.js + pages/attractions-view.js,
 * fed by $pageData (no inline scripts).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Attraction $attraction
 * @var array $auth_user
 * @var array $tickets_max_quantity
 * @var array $tickets_slots
 * @var array|null $available_dates
 * @var int|null $cart_count
 */
$base = \Cake\Core\Configure::read('AWSPublic.public_host');
$isViewer = ($auth_user['role'] ?? null) === 'viewer';
$canAdd = !isset($not_allowed_to_add);
$pricingType = $attraction->pricing_type;
$step = 1;

$this->set('pageData', [
    'attractionId' => $attraction->id,
    'pricingType' => $pricingType,
    'hasApiSku' => !empty($attraction->api_sku),
    'availableDates' => ($pricingType != 'settlement' && !empty($available_dates)) ? array_values((array)$available_dates) : [],
    'noDates' => $pricingType != 'settlement' && empty($available_dates),
    'canAdd' => $canAdd,
    'cartHasTwoWay' => !empty($cart_has_two_way_fdatetime),
    'urls' => [
        'tickets' => $this->Url->build(['controller' => 'Attractions', 'action' => 'getTickets', $attraction->id]),
        'times' => $this->Url->build(['controller' => 'Attractions', 'action' => 'getFixeddateTime', $attraction->id]),
        'availability' => $this->Url->build(['controller' => 'Attractions', 'action' => 'getAvailability', $attraction->id]),
    ],
    'i18n' => [
        'total' => __('Total'),
        'noTickets' => __('No tickets found!'),
        'selectTime' => __('Select time'),
        'soldOut' => __('Sold out'),
        'retailPrice' => __('Retail price'),
        'minimumSellingPrice' => __('Minimum selling price'),
        'quantity' => __('Quantity'),
        'less' => __('Less'),
        'more' => __('More'),
        'loadFailed' => __('Tickets could not be loaded.'),
        'retry' => __('Retry'),
        'selectAtLeastOne' => __('Please select at least 1 to continue.'),
        'cartWillBeCleared' => __('Cart will be cleared to make this purchase'),
        'pickDateFirst' => __('Pick a date to see the tickets.'),
        'pickTimeFirst' => __('Pick a time to see the tickets.'),
    ],
]);
?>
<div class="row g-4">
  <div class="col-lg-8">
    <?= $this->element('attraction_detail', ['attraction' => $attraction, 'base' => $base]) ?>
  </div>

  <div class="col-lg-4">
    <div class="sg-booking" id="options">
      <?= $this->Form->create(null, ['class' => 'application-form form-deferred']) ?>
        <div class="sg-booking-header">
          <h3 class="mb-0"><?= __('Options') ?></h3>
        </div>
        <div class="sg-booking-body">
          <?php if (!$canAdd): ?>
            <div class="alert alert-warning small" role="alert">
              <i class="fa fa-exclamation-triangle alert-icon" aria-hidden="true"></i>
              <div class="alert-body">
                <?= __('Tickets from this merchant can\'t be added while your cart has tickets from another merchant.') ?>
                <?= $this->Html->link(__('Go to cart'), ['controller' => 'Orders', 'action' => 'cart'], ['class' => 'alert-link']) ?>
              </div>
            </div>
          <?php endif ?>

          <?php if ($pricingType != 'settlement'): ?>
            <div class="booking-step">
              <label class="booking-step-label" for="date"><span class="sg-step-no"><?= $step++ ?></span><?= __('Select Date') ?></label>
              <div class="input-group">
                <span class="input-group-text bg-white"><i class="fa fa-calendar text-navy" aria-hidden="true"></i></span>
                <?= $this->Form->text('date', ['id' => 'date', 'class' => 'form-control', 'autocomplete' => 'off', 'placeholder' => __('Select Date'), 'readonly' => false]) ?>
              </div>
            </div>
            <?php if ($pricingType == 'fdatetime'): ?>
              <div class="booking-step" id="time-wrapper">
                <label class="booking-step-label" for="time"><span class="sg-step-no"><?= $step++ ?></span><?= __('Select Time') ?></label>
                <?= $this->Form->select('time', [], ['id' => 'time', 'class' => 'form-select', 'empty' => __('Please select time')]) ?>
              </div>
            <?php endif ?>
          <?php endif ?>

          <div class="tickets-wrapper booking-step">
            <div class="booking-step-label"><span class="sg-step-no"><?= $step++ ?></span><?= __('Select ticket(s)') ?></div>
            <div class="tickets">
              <?php if ($attraction->product_type == 'grouped'): ?>
                <div id="attraction-group">
                <?php foreach ($attraction->attractions as $ap_key => $attraction_product): ?>
                  <?php foreach ((array)$attraction_product->tickets as $ticket): ?>
                    <?php if ($ticket->fixed_date_ticket): ?>
                      <div class="collapse <?= $ap_key == 0 ? 'show' : '' ?>" id="collapse-<?= h($attraction_product->id) ?>" data-bs-parent="#attraction-group">
                        <?= $this->element('ticket_row', [
                            'ticket' => $ticket,
                            'maxQty' => $tickets_max_quantity[$ticket->id] ?? null,
                            'slot' => $tickets_slots[$ticket->id] ?? null,
                            'canAdd' => $canAdd,
                            'details' => false,
                        ]) ?>
                      </div>
                    <?php endif ?>
                  <?php endforeach ?>
                <?php endforeach ?>
                </div>
              <?php elseif (!empty($attraction->tickets)): ?>
                <?php foreach ($attraction->tickets as $ticket): ?>
                  <?php
                  $maxQty = $tickets_max_quantity[$ticket->id] ?? null;
                  if ($ticket->max_quantity && $maxQty > $ticket->max_quantity) {
                      $maxQty = $ticket->max_quantity;
                  }
                  ?>
                  <?= $this->element('ticket_row', [
                      'ticket' => $ticket,
                      'maxQty' => $maxQty,
                      'slot' => $tickets_slots[$ticket->id] ?? null,
                      'canAdd' => $canAdd,
                  ]) ?>
                <?php endforeach ?>
              <?php elseif ($pricingType != 'settlement'): ?>
                <p class="tickets-placeholder"><i class="fa fa-hand-o-up me-1" aria-hidden="true"></i><?= __('Pick a date to see the tickets.') ?></p>
              <?php endif ?>
            </div>
          </div>
        </div>

        <div class="sg-booking-footer">
          <div class="total"></div>
          <?php if (!$isViewer): ?>
          <div class="actions">
            <?= $this->Form->button('<i class="fa fa-bolt me-1" aria-hidden="true"></i>' . __('Buy Now'), ['type' => 'submit', 'name' => 'buy_now', 'id' => 'btn-buy-now', 'value' => 'buy_now', 'class' => 'btn btn-cta btn-lg w-100', 'escapeTitle' => false, 'disabled' => true]) ?>
            <?= $this->Form->button('<i class="fa fa-cart-plus me-1" aria-hidden="true"></i>' . __('Add to Cart'), ['type' => 'submit', 'id' => 'btn-add-to-cart', 'name' => 'add_to_cart', 'value' => 'add_to_cart', 'class' => 'btn btn-outline-primary w-100', 'escapeTitle' => false, 'disabled' => true]) ?>
            <div class="cta-hint small text-muted text-center"><?= __('Select at least 1 ticket') ?></div>
            <?php if (!empty($cart_count)): ?>
              <?= $this->Html->link('<i class="fa fa-shopping-cart me-1" aria-hidden="true"></i>' . __('View Cart'), ['controller' => 'Orders', 'action' => 'cart'], ['class' => 'btn btn-link w-100 view-cart', 'escape' => false]) ?>
            <?php endif ?>
          </div>
          <?php endif ?>
        </div>
      <?= $this->Form->end() ?>
      <div class="form-loader"><div class="spinner-border" role="status"><span class="visually-hidden"><?= __('Loading') ?></span></div></div>
    </div>
  </div>
</div>

<?php if (!$isViewer && $canAdd): ?>
<div class="sg-mobile-bar">
  <div class="sg-mobile-bar-total">
    <div class="price-label"><?= __('Total') ?></div>
    <div class="sg-mobile-bar-amount price-net price-net-sm"><?= $this->Number->currency(0) ?></div>
  </div>
  <button type="button" class="btn btn-cta" data-mobile-cta><?= __('Buy Now') ?></button>
</div>
<?php endif ?>

<?php
$this->Html->css(['bootstrap-datepicker3.standalone.min'], ['block' => 'internal_css']);
$this->Html->script([
    'bootstrap-datepicker.min',
    'favorite-attraction.js?v=20261106',
    'ticket-row.js?v=20261109',
    'pages/attractions-view.js?v=20261106',
], ['block' => 'script']);
?>
