<?php
/**
 * Two-way (depart + return) attraction: detail + booking panel.
 * CSS: theme.css (.page-attractions-view-two-way). JS: ticket-row.js + pages/attractions-view-two-way.js.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Attraction $attraction
 * @var array $auth_user
 * @var int $cart_count
 * @var array|null $available_dates
 */
$base = \Cake\Core\Configure::read('AWSPublic.public_host');
$isViewer = ($auth_user['role'] ?? null) === 'viewer';
$canAdd = !isset($not_allowed_to_add);

$this->set('pageData', [
    'attractionId' => $attraction->id,
    'availableDates' => !empty($available_dates) ? array_values((array)$available_dates) : [],
    'noDates' => empty($available_dates),
    'canAdd' => $canAdd,
    'cartCount' => (int)($cart_count ?? 0),
    'urls' => [
        'tickets' => $this->Url->build(['controller' => 'Attractions', 'action' => 'getTwoWayTickets', $attraction->id]),
        'times' => $this->Url->build(['controller' => 'Attractions', 'action' => 'getFixeddateTime', $attraction->id]),
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
        'matchesDeparture' => __('Matches departure quantity'),
        'selectAtLeastOne' => __('Please select at least 1 to continue.'),
        'quantitiesEqual' => __('Depart ticket quantity and return ticket quantity should be equal.'),
        'cartWillBeCleared' => __('Cart will be cleared to make this purchase'),
        'pickTripFirst' => __('Pick the departure and return times to see the tickets.'),
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

          <fieldset class="booking-leg">
            <legend class="booking-step-label"><span class="sg-step-no">1</span><i class="fa fa-plane me-1 text-navy" aria-hidden="true"></i><?= __('Departure') ?></legend>
            <div class="row g-2">
              <div class="col-7">
                <label class="form-label small" for="departure-date"><?= __('Depart Date') ?></label>
                <?= $this->Form->text('departure_date', ['id' => 'departure-date', 'class' => 'form-control', 'data-type' => 'departure', 'autocomplete' => 'off']) ?>
              </div>
              <div class="col-5" id="departure-time-wrapper">
                <label class="form-label small" for="departure-time"><?= __('Depart Time') ?></label>
                <?= $this->Form->select('departure_time', [], ['id' => 'departure-time', 'class' => 'form-select', 'empty' => __('Please select time')]) ?>
              </div>
            </div>
          </fieldset>

          <fieldset class="booking-leg">
            <legend class="booking-step-label"><span class="sg-step-no">2</span><i class="fa fa-plane fa-flip-horizontal me-1 text-navy" aria-hidden="true"></i><?= __('Return') ?></legend>
            <div class="row g-2">
              <div class="col-7">
                <label class="form-label small" for="return-date"><?= __('Return Date') ?></label>
                <?= $this->Form->text('return_date', ['id' => 'return-date', 'class' => 'form-control', 'data-type' => 'return', 'autocomplete' => 'off']) ?>
              </div>
              <div class="col-5" id="return-time-wrapper">
                <label class="form-label small" for="return-time"><?= __('Return Time') ?></label>
                <?= $this->Form->select('return_time', [], ['id' => 'return-time', 'class' => 'form-select', 'empty' => __('Please select time')]) ?>
              </div>
            </div>
          </fieldset>

          <div class="tickets-wrapper booking-step">
            <div class="booking-step-label"><span class="sg-step-no">3</span><?= __('Select ticket(s)') ?></div>
            <div class="tickets">
              <h4 class="booking-leg-title"><?= __('Depart Tickets') ?></h4>
              <div class="depart-tickets"><p class="tickets-placeholder"><?= __('Pick the departure and return times to see the tickets.') ?></p></div>
              <h4 class="booking-leg-title"><?= __('Return Tickets') ?></h4>
              <div class="return-tickets"></div>
            </div>
          </div>
        </div>

        <div class="sg-booking-footer">
          <div class="total"></div>
          <?php if (!$isViewer): ?>
          <div class="actions">
            <?= $this->Form->button('<i class="fa fa-bolt me-1" aria-hidden="true"></i>' . __('Buy Now'), ['type' => 'submit', 'name' => 'buy_now', 'id' => 'btn-buy-now', 'value' => 'buy_now', 'class' => 'btn btn-cta btn-lg w-100', 'escapeTitle' => false, 'disabled' => true]) ?>
            <div class="cta-hint small text-muted text-center"><?= __('Select at least 1 ticket') ?></div>
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
    'ticket-row.js?v=20261109c',
    'pages/attractions-view-two-way.js?v=20261106',
], ['block' => 'script']);
?>
