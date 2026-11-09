<?php
/**
 * Order summary sidebar (checkout, confirmation). Sticky via CSS (.sg-sticky-summary on the column).
 *
 * e2e (readSidebar) reads: #tickets-sidebar .card > .card-body children [link, unit price],
 * .row.py-2 > .col-md-6 (date) / .col-md-2 (× qty) / .col-md-4 (line total),
 * .footer-total .row > .col (label) + .col.text-end (value); .grandtotal-row is rewritten by the
 * confirmation page JS (convenience fee). Keep these hooks.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Ticket[] $tickets
 * @var array $ticket_list
 * @var float|int $other_fees
 */
$subtotal = 0;
?>
<div id="tickets-sidebar" class="sg-summary">
<?php if (!empty($tickets)): ?>
  <div class="sg-summary-header"><i class="fa fa-ticket me-2" aria-hidden="true"></i><?= __('Order summary') ?></div>
  <?php foreach ($tickets as $ticket): ?>
    <div class="card sg-summary-item">
      <div class="card-body">
        <div><?= $this->Html->link(
            '<div class="fw-bold">' . h($ticket->attraction->title, false) . '</div>'
            . '<div class="text-muted">' . h($ticket->name, false) . '<br><del class="price-ref">RP ' . $this->Number->currency($ticket->retail_price) . '</del></div>',
            ['controller' => 'Attractions', 'action' => 'view', $ticket->attraction_id],
            ['escape' => false, 'class' => 'sg-summary-link']
        ) ?></div>
        <div class="sg-summary-unit"><?= $this->Number->currency($ticket->price) ?></div>
        <?php if (!empty($ticket_list[$ticket->id])): ?>
        <?php foreach ($ticket_list[$ticket->id] as $date => $qty): ?>
          <?php $subtotal += ($ticket->price * $qty) ?>
          <div class="row py-2 g-0 sg-summary-row">
            <div class="col-md-6 col-6"><?php if ($ticket->attraction->pricing_type != 'settlement'): ?><i class="fa fa-calendar-o me-1 text-muted" aria-hidden="true"></i><?= h($date) ?><?php endif ?></div>
            <div class="col-md-2 col-2 text-end"><span>&times; <?= $qty ?></span></div>
            <div class="col-md-4 col-4 text-end fw-semibold"><?= $this->Number->currency($ticket->price * $qty) ?></div>
          </div>
        <?php endforeach ?>
        <?php endif ?>
      </div>
    </div>
  <?php endforeach; ?>
  <div class="footer-total">
    <div class="row">
      <div class="col"><?= __('Subtotal') ?></div><div class="col text-end"><?= $this->Number->currency($subtotal) ?></div>
    </div>
    <div class="row grandtotal-row">
      <div class="col"><?= __('Grand Total') ?></div><div class="col text-end"><?= $this->Number->currency($subtotal + ($other_fees ?? 0)) ?></div>
    </div>
  </div>
<?php endif ?>
</div>
