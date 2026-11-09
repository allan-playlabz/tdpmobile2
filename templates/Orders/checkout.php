<?php
/**
 * Checkout (step 2): customer details, traveller / transfer info, booking questions + order summary.
 * CSS: theme.css (.page-orders-checkout). JS: pages/orders-checkout.js (date pickers, validation UI).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Order $order
 * @var array|null $agent
 * @var \App\Model\Entity\Ticket[]|null $tickets
 * @var array $ticket_list
 * @var array|null $tickets_with_question_group
 */
use Cake\Core\Configure;

$subtotal = 0;
$other_fees = 0;
if (!empty($agent)) {
    $agent_name = explode(' ', $agent['name']);
    if (!empty($agent_name)) {
        $agent['last_name'] = array_pop($agent_name);
        $agent['first_name'] = implode(' ', $agent_name);
    }
}

// Which sections exist (decides the step numbers; empty sections are not rendered).
$hasRequiredInfo = false;
$hasQuestionGroup = false;
foreach ((array)$tickets as $ticket) {
    if ($ticket->require_travellers_info || $ticket->attraction->type == 'transfer' || $ticket->multiple_redemption) {
        $hasRequiredInfo = true;
    }
    if (!empty($tickets_with_question_group[$ticket->id]) && !empty($ticket_list[$ticket->id])) {
        foreach ($ticket_list[$ticket->id] as $qty) {
            if ($qty > 0) {
                $hasQuestionGroup = true;
            }
        }
    }
    foreach ((array)($ticket_list[$ticket->id] ?? []) as $qty) {
        $subtotal += $ticket->price * $qty;
    }
}
$section = 1;
$forceSingleSkus = (array)Configure::read('ForceSingleCode.skus');
$ticketHeading = function ($ticket, $date, bool $retail = false) {
    $subtype = $ticket->attraction->type == 'transfer' ? ' - ' . h(Configure::read('Default.transfer_subtypes.' . $ticket->attraction->subtype)) : '';
    $when = $ticket->attraction->pricing_type != 'settlement' && !empty($date) ? ' <span class="badge badge-soft ms-1"><i class="fa fa-calendar-o me-1" aria-hidden="true"></i>' . h($date) . '</span>' : '';
    $html = '<div class="traveller-group-title"><div class="fw-bold">' . h($ticket->attraction->title, false) . '</div>'
        . '<div class="text-muted">' . h($ticket->name, false) . $subtype . $when . '</div>';
    if ($retail) {
        $html .= '<div class="price-ref"><del>RP ' . $this->Number->currency($ticket->retail_price) . '</del></div>';
    }
    return $html . '</div>';
};

$this->set('pageData', [
    'i18n' => [
        'fixErrors' => __('Please check the highlighted fields.'),
        'copied' => __('Copied from customer details'),
    ],
]);
$this->Html->css(['bootstrap-datepicker3.standalone.min'], ['block' => 'internal_css']);
$this->Html->script(['bootstrap-datepicker.min', 'pages/orders-checkout.js?v=20261106'], ['block' => 'script']);
?>
<?= $this->element('checkout_steps', ['current' => 2]) ?>

<?= $this->Form->create($order, ['class' => 'form-deferred checkout-form', 'id' => 'checkout-form']) ?>
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="mb-0"><?= __('Checkout') ?></h1>
    <?= $this->Html->link('<i class="fa fa-chevron-left" aria-hidden="true"></i> ' . __('Back'), ['action' => 'cart'], ['escape' => false, 'class' => 'btn btn-link px-0']); ?>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card sg-card mb-4 customer-card">
        <div class="card-header"><span class="sg-step-no"><?= $section++ ?></span><?= __('Customer Details') ?></div>
        <div class="card-body">
          <p class="required-legend mb-3"><?= __('Fields marked * are required.') ?></p>
          <div class="row g-3">
            <div class="col-md-6">
              <?= $this->Form->control('first_name', ['class' => 'form-control', 'required' => 'required', 'maxlength' => 50, 'pattern' => '[a-zA-Z0-9\-_\s]+', 'title' => __('English letters, numbers, spaces, - and _ only'), 'label' => __('First Name (English only)*'), 'default' => !empty($agent['first_name']) ? $agent['first_name'] : '', 'autocomplete' => 'given-name']) ?>
            </div>
            <div class="col-md-6">
              <?= $this->Form->control('last_name', ['class' => 'form-control', 'required' => 'required', 'maxlength' => 50, 'pattern' => '[a-zA-Z0-9\-_\s]+', 'title' => __('English letters, numbers, spaces, - and _ only'), 'label' => __('Last Name (English only)*'), 'default' => !empty($agent['last_name']) ? $agent['last_name'] : '', 'autocomplete' => 'family-name']) ?>
            </div>
            <div class="col-md-6">
              <?= $this->Form->control('email', ['class' => 'form-control', 'required' => 'required', 'label' => __('Email*'), 'default' => !empty($agent['email']) ? $agent['email'] : '', 'autocomplete' => 'email']) ?>
            </div>
            <div class="col-md-6">
              <?= $this->Form->control('mobile', ['class' => 'form-control', 'required' => 'required', 'label' => __('Mobile * (e.g. +6512345678)'), 'default' => !empty($agent['contact_no']) ? $agent['contact_no'] : '', 'pattern' => '[+]*[0-9]+', 'title' => '+ and numbers only', 'inputmode' => 'tel', 'autocomplete' => 'tel']) ?>
            </div>
            <div class="col-md-6">
              <?= $this->Form->control('tracking_no', ['class' => 'form-control', 'label' => __('Order Tracking Number'), 'type' => 'text', 'placeholder' => 'Unique or Empty']) ?>
              <div class="form-text"><?= __('Optional: your own reference for this order. It must be unique.') ?></div>
            </div>
          </div>
        </div>
      </div>

      <?php if (!empty($tickets) && $hasRequiredInfo): ?>
      <div class="card sg-card mb-4 order-info-card">
        <div class="card-header"><span class="sg-step-no"><?= $section++ ?></span><?= __('Order Information') ?></div>
        <div class="card-body">
          <?php foreach ($tickets as $ticket): ?>
            <?php if ($ticket->require_travellers_info || $ticket->attraction->type == 'transfer' || $ticket->multiple_redemption): ?>
            <div class="ticket">
              <?php if (($ticket->require_travellers_info || ($ticket->multiple_redemption && $ticket->type == 'normal')) && !empty($ticket_list[$ticket->id])): ?>
                <?php foreach ($ticket_list[$ticket->id] as $date => $qty): ?>
                <div class="traveller-group">
                  <?= $ticketHeading($ticket, $date) ?>
                  <?php if ($ticket->multiple_redemption && $ticket->type == 'normal'): ?>
                    <div class="redemption-option">
                      <?php if (in_array($ticket->sku_id, $forceSingleSkus)): ?>
                        <div class="d-none">
                          <?= $this->Form->control('multiple_redemption.' . $ticket->id . '.' . $date, ['label' => __('Redeem as single code'), 'type' => 'checkbox', 'checked' => true]); ?>
                        </div>
                        <div class="small"><i class="fa fa-check-square-o me-1 text-navy" aria-hidden="true"></i><?= __('Redeem as single code') ?></div>
                      <?php else: ?>
                        <?= $this->Form->control('multiple_redemption.' . $ticket->id . '.' . $date, ['label' => __('Redeem as single code'), 'type' => 'checkbox']); ?>
                      <?php endif ?>
                    </div>
                  <?php endif ?>
                  <?php if ($ticket->require_travellers_info): ?>
                    <?php for ($i = 0; $i < $qty; $i++): ?>
                      <div class="traveller" data-traveller-index="<?= $i ?>">
                        <div class="traveller-title">
                          <span><i class="fa fa-user-o me-1" aria-hidden="true"></i><?= __('Traveller {0} of {1}', $i + 1, $qty) ?></span>
                          <?php if ($i === 0): ?>
                            <button type="button" class="btn-link-plain small copy-customer"><i class="fa fa-clone me-1" aria-hidden="true"></i><?= __('Copy name from customer details') ?></button>
                          <?php endif ?>
                        </div>
                        <div class="row g-3">
                          <div class="col-md-6">
                            <?= $this->Form->control('passport_first_name.' . $ticket->id . '.' . $date . '.' . $i, ['class' => 'form-control traveller-first-name', 'label' => __('Passport First Name *'), 'required' => 'required']) ?>
                          </div>
                          <div class="col-md-6">
                            <?= $this->Form->control('passport_last_name.' . $ticket->id . '.' . $date . '.' . $i, ['class' => 'form-control traveller-last-name', 'label' => __('Passport Last Name *'), 'required' => 'required']) ?>
                          </div>
                          <div class="col-md-6">
                            <?= $this->Form->control('passport_number.' . $ticket->id . '.' . $date . '.' . $i, ['class' => 'form-control', 'label' => __('Passport Number *'), 'required' => 'required']) ?>
                          </div>
                          <div class="col-md-6">
                            <?= $this->Form->control('passport_expiry.' . $ticket->id . '.' . $date . '.' . $i, ['class' => 'form-control passport-expiry', 'label' => __('Passport Expiry *'), 'required' => 'required', 'autocomplete' => 'off', 'placeholder' => 'd M yyyy']) ?>
                          </div>
                          <div class="col-md-6">
                            <?= $this->Form->control('date_of_birth.' . $ticket->id . '.' . $date . '.' . $i, ['class' => 'form-control dob', 'label' => __('Date of Birth *'), 'required' => 'required', 'autocomplete' => 'off', 'placeholder' => 'd M yyyy']) ?>
                          </div>
                          <div class="col-md-6">
                            <?= $this->Form->control('nationality.' . $ticket->id . '.' . $date . '.' . $i, ['class' => 'form-control', 'label' => __('Nationality *'), 'required' => 'required']) ?>
                          </div>
                          <div class="col-12">
                            <?= $this->Form->control('remarks.' . $ticket->id . '.' . $date . '.' . $i, ['class' => 'form-control', 'label' => __('Remarks'), 'autocomplete' => 'off', 'type' => 'textarea', 'rows' => 2]) ?>
                          </div>
                        </div>
                      </div>
                    <?php endfor ?>
                  <?php endif ?>
                </div>
                <?php endforeach ?>
              <?php endif ?>

              <?php foreach ((array)($ticket_list[$ticket->id] ?? []) as $date => $qty): ?>
                <?php if ($ticket->attraction->type == 'transfer'):
                  $extra_fields = Configure::read('Default.land_transfer_details');
                  if (!empty($extra_fields) && is_array($extra_fields)): ?>
                  <div class="traveller-group">
                    <?= $ticketHeading($ticket, $date, true) ?>
                    <h3 class="traveller-subtitle"><i class="fa fa-car me-1" aria-hidden="true"></i><?= __('Transfer Details') ?></h3>
                    <div class="row g-3">
                    <?php foreach ($extra_fields as $extra_field_name => $extra_field): ?>
                      <div class="col-md-6">
                      <?php
                      $flightOptional = $extra_field_name == 'flight_number' && ($ticket->attraction->subtype == 3 || $ticket->attraction->subtype == 4);
                      if ($extra_field['type'] == 'date') {
                          echo $this->Form->control($extra_field_name . '.' . $ticket->id, ['type' => 'text', 'class' => 'datepicker form-control', 'label' => $extra_field['label'] . '*', 'required' => 'required', 'autocomplete' => 'off']);
                      } elseif ($extra_field['type'] == 'time') {
                          echo $this->Form->control($extra_field_name . '.' . $ticket->id, ['type' => 'time', 'class' => 'timepicker form-control', 'required' => 'required', 'label' => ['text' => $extra_field['label'] . '*', 'class' => 'd-block'],
                              'hour' => ['class' => 'form-select d-inline w-auto'], 'minute' => ['class' => 'form-select d-inline w-auto']]);
                      } else {
                          echo $this->Form->control($extra_field_name . '.' . $ticket->id, ['type' => $extra_field['type'], 'required' => $flightOptional ? false : 'required', 'class' => 'form-control', 'label' => $extra_field['label'] . ($flightOptional ? '' : '*')]);
                      }
                      ?>
                      </div>
                    <?php endforeach ?>
                    </div>

                    <?php if ($ticket->attraction->subtype == Configure::read('Default.transfer_subtypes.2')): ?>
                      <h3 class="traveller-subtitle"><i class="fa fa-undo me-1" aria-hidden="true"></i><?= __('Return Trip') ?></h3>
                      <div class="row g-3">
                      <?php foreach ($extra_fields as $extra_field_name => $extra_field): ?>
                        <div class="col-md-6">
                        <?php
                        if ($extra_field['type'] == 'date') {
                            echo $this->Form->control('return_' . $extra_field_name . '.' . $ticket->id, ['type' => 'text', 'required' => 'required', 'autocomplete' => 'off', 'class' => 'datepicker form-control', 'label' => $extra_field['label'] . '*']);
                        } elseif ($extra_field['type'] == 'time') {
                            echo $this->Form->control('return_' . $extra_field_name . '.' . $ticket->id, ['type' => 'time', 'required' => 'required', 'class' => 'timepicker form-control', 'label' => ['text' => $extra_field['label'] . '*', 'class' => 'd-block'],
                                'hour' => ['class' => 'form-select d-inline w-auto'], 'minute' => ['class' => 'form-select d-inline w-auto']]);
                        } else {
                            echo $this->Form->control('return_' . $extra_field_name . '.' . $ticket->id, ['type' => $extra_field['type'], 'required' => $extra_field_name == 'flight_number' ? false : 'required', 'class' => 'form-control', 'label' => $extra_field['label'] . '*']);
                        }
                        ?>
                        </div>
                      <?php endforeach ?>
                      </div>
                    <?php endif ?>
                  </div>
                  <?php endif ?>
                <?php endif ?>
              <?php endforeach ?>
            </div>
            <?php endif ?>
          <?php endforeach ?>
          <div class="has-required-info d-none"></div>
        </div>
      </div>
      <?php endif ?>

      <?php if (!empty($tickets) && $hasQuestionGroup): ?>
      <div class="card sg-card mb-4 ticket-details-card">
        <div class="card-header"><span class="sg-step-no"><?= $section++ ?></span><?= __('Ticket Details') ?></div>
        <div class="card-body">
          <?php foreach ($tickets as $ticket): ?>
            <div class="ticket-details-container">
            <?php foreach ((array)($ticket_list[$ticket->id] ?? []) as $date => $qty): ?>
              <?php if (!empty($tickets_with_question_group[$ticket->id])): ?>
                <div class="traveller-group">
                <?php
                for ($i = 0; $i < $qty; $i++) {
                    $this->QuestionGroup->render(
                        $tickets_with_question_group[$ticket->id],
                        $this->Form,
                        $ticket->id . '.' . $date . '.' . $i,
                        $ticket->attraction->title,
                        $ticket->name,
                        $date,
                        $i + 1,
                        $this->Form->getTemplates(),
                        $i == $qty - 1
                    );
                    if ($ticket->lead_pax_only) {
                        break;
                    }
                }
                ?>
                </div>
              <?php endif ?>
            <?php endforeach ?>
            </div>
          <?php endforeach ?>
        </div>
      </div>
      <?php endif ?>

      <div class="checkout-actions sg-panel">
        <div class="checkout-actions-total">
          <span class="price-label"><?= __('Grand Total') ?></span>
          <span class="price-net"><?= $this->Number->currency($subtotal + $other_fees) ?></span>
        </div>
        <?= $this->Form->button(__('Continue') . ' <i class="fa fa-arrow-right ms-1" aria-hidden="true"></i>', ['class' => 'btn btn-cta btn-lg', 'id' => 'btn-continue', 'escapeTitle' => false]); ?>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="sg-sticky-summary">
        <?= $this->element('selected_tickets', ['subtotal' => $subtotal, 'other_fees' => $other_fees]) ?>
      </div>
    </div>
  </div>
<?= $this->Form->end() ?>
<div class="form-loader"><div class="spinner-border" role="status"><span class="visually-hidden"><?= __('Loading') ?></span></div></div>
