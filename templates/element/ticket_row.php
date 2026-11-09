<?php
/**
 * One bookable ticket in the attraction booking panel. webroot/js/ticket-row.js (sgTickets.renderRow)
 * builds the same markup for tickets loaded over AJAX — keep the two in sync.
 *
 * Hooks used by JS / e2e: .ticket-option, .ticket-name, .ticket-qty, .input-group > .btn-subtract /
 * input[name^="quantity["] (data-price, min, max, data-slot-*) / .btn-add, .remaining-count, .sold-out.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Ticket $ticket
 * @var int|null $maxQty Selectable quantity (null/0 = sold out).
 * @var array|null $slot Shared-slot info ['slot_id' => …, 'pool' => …].
 * @var bool $canAdd False when the agent may not add this merchant's tickets (no stepper).
 * @var bool $details Show SKU, retail / minimum selling price and validity.
 */
$details = $details ?? true;
$canAdd = $canAdd ?? true;
$soldOut = empty($maxQty);
?>
<div class="ticket-option">
  <div class="ticket-info">
    <b class="ticket-name"><?= h($ticket->name, false) ?></b>
    <?php if ($details): ?>
      <?php if (!empty($ticket->sku_id)): ?><div class="ticket-sku text-10"><?= h($ticket->sku_id) ?></div><?php endif ?>
      <div class="ticket-meta">
        <?php if ($ticket->redemption_type == 'redemption_date'): ?>
          <i class="fa fa-calendar-o" aria-hidden="true"></i> <?= __('Valid From') ?> <?= $ticket->redemption_start_date ? $ticket->redemption_start_date->format('d M Y') : '' ?> <?= __('To') ?> <?= $ticket->redemption_end_date ? $ticket->redemption_end_date->format('d M Y') : '' ?>
        <?php elseif (!empty($ticket->duration)): ?>
          <i class="fa fa-hourglass-half" aria-hidden="true"></i> <?= __('Valid for') ?> <?= h($ticket->duration . ' ' . __($ticket->duration_type)) ?>
        <?php endif ?>
      </div>
    <?php endif ?>
  </div>
  <div class="ticket-buy">
    <div class="ticket-price">
      <div class="price-net price-net-sm"><?= $this->Number->currency($ticket->price) ?></div>
      <?php if ($details): ?>
      <div class="price-ref">
        <?php if ($ticket->retail_price !== null): ?><del><abbr title="<?= __('Retail price') ?>" data-bs-toggle="tooltip">RP</abbr> <?= $this->Number->currency($ticket->retail_price) ?></del><?php endif ?>
        <?php if (!empty($ticket->minimum_selling_price)): ?><br><abbr title="<?= __('Minimum selling price') ?>" data-bs-toggle="tooltip">MSP</abbr> <?= $this->Number->currency($ticket->minimum_selling_price) ?><?php endif ?>
      </div>
      <?php endif ?>
    </div>
    <div class="ticket-qty">
      <?php if ($canAdd): ?>
        <div class="input-group sg-stepper">
          <button class="btn btn-outline-secondary btn-subtract" type="button" aria-label="<?= __('Less') ?>"><i class="fa fa-minus" aria-hidden="true"></i></button>
          <?= $this->Form->text('quantity.' . $ticket->id, [
              'value' => 0,
              'class' => 'form-control',
              'inputmode' => 'numeric',
              'aria-label' => __('Quantity') . ' ' . strip_tags((string)$ticket->name),
              'data-price' => $ticket->price,
              'min' => ($ticket->min_quantity ? $ticket->min_quantity : '0'),
              'max' => $maxQty,
              'data-slot-id' => $slot['slot_id'] ?? null,
              'data-slot-pool' => $slot ? $slot['pool'] : null,
              'data-max-qty' => ($slot && $ticket->max_quantity) ? $ticket->max_quantity : null,
          ]) ?>
          <button class="btn btn-outline-secondary btn-add" type="button" aria-label="<?= __('More') ?>"><i class="fa fa-plus" aria-hidden="true"></i></button>
        </div>
        <?php if ($soldOut): ?>
          <div class="sold-out text-danger fw-bold"><?= __('Sold out') ?></div>
        <?php endif ?>
      <?php endif ?>
      <?php if ($ticket->show_stock): ?>
        <div class="remaining-count">Remaining: <?= $maxQty ?? '' ?></div>
      <?php endif ?>
    </div>
  </div>
  <?php if (!empty($ticket->agent_notification)): ?>
    <div class="ticket-note"><?= $ticket->agent_notification ?></div>
  <?php endif ?>
</div>
