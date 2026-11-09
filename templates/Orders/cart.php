<?php
/**
 * Cart: item cards + sticky order summary. Quantities are POSTed with Checkout.
 * CSS: theme.css (.page-orders-cart). JS: pages/orders-cart.js.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Ticket[]|null $tickets
 * @var array $ticket_list
 * @var array $tickets_max_quantity
 */
use Cake\Core\Configure;

$base = Configure::read('AWSPublic.public_host');
$total = 0;
$itemCount = 0;
$this->set('pageData', ['i18n' => ['total' => __('Total')]]);
$this->Html->script('pages/orders-cart.js?v=20261106', ['block' => 'script']);
?>
<?= $this->element('checkout_steps', ['current' => 1]) ?>
<h1 class="mb-3"><?= __('Your Cart') ?></h1>

<?php if (empty($tickets)): ?>
  <div class="sg-empty sg-panel text-center py-5 px-3">
    <i class="fa fa-shopping-cart" aria-hidden="true"></i>
    <p class="lead mb-1">No items on cart.</p>
    <p class="text-muted"><?= __('Browse attractions and add tickets to get started.') ?></p>
    <?= $this->Html->link(__('Browse attractions'), ['controller' => 'Attractions', 'action' => 'index'], ['class' => 'btn btn-cta']) ?>
  </div>
<?php else: ?>
<?= $this->Form->create(null, ['class' => 'form-deferred', 'id' => 'cart-form']) ?>
  <div class="row g-4">
    <div class="col-lg-8">
      <?php foreach ($tickets as $ticket): ?>
        <?php
        $viewUrl = ['controller' => 'Attractions', 'action' => 'view', $ticket->attraction_id];
        $maxQty = !empty($tickets_max_quantity[$ticket->id]) ? $tickets_max_quantity[$ticket->id] : null;
        if ($ticket->max_quantity && $maxQty && $ticket->max_quantity < $maxQty) {
            $maxQty = $ticket->max_quantity;
        }
        ?>
        <div class="card ticket-card cart-item mb-3">
          <div class="card-body">
            <div class="cart-item-head">
              <a class="cart-item-thumb" href="<?= $this->Url->build($viewUrl) ?>" tabindex="-1" aria-hidden="true">
                <?php if ($ticket->attraction->featured_image): ?>
                  <img src="<?= h($base . $ticket->attraction->featured_image) ?>" alt="" class="object-fit-cover" loading="lazy">
                <?php else: ?>
                  <span class="attraction-media-empty"><i class="fa fa-picture-o"></i></span>
                <?php endif ?>
              </a>
              <div class="min-w-0 flex-grow-1">
                <a href="<?= $this->Url->build($viewUrl) ?>" class="cart-item-link">
                  <div class="fw-bold cart-item-title"><?= h($ticket->attraction->title, false) ?></div>
                  <div class="text-muted"><?= h($ticket->name, false) ?><?= $ticket->attraction->type == 'transfer' ? ' - ' . h(Configure::read('Default.transfer_subtypes.' . $ticket->attraction->subtype)) : '' ?></div>
                </a>
                <div class="cart-item-prices">
                  <span class="fw-semibold text-success"><?= $this->Number->currency($ticket->price) ?></span>
                  <?php if ($ticket->retail_price !== null): ?>
                    <del class="price-ref" title="<?= __('Retail price') ?>">RP <?= $this->Number->currency($ticket->retail_price) ?></del>
                  <?php endif ?>
                </div>
              </div>
            </div>

            <?php if (!empty($ticket_list[$ticket->id])): ?>
            <div class="cart-lines">
              <?php foreach ($ticket_list[$ticket->id] as $date => $qty): ?>
                <?php $total += $ticket->price * $qty; $itemCount += $qty; ?>
                <div class="row py-2 g-2 align-items-center cart-line">
                  <div class="col-4 cart-line-date"><?php if ($ticket->attraction->pricing_type != 'settlement'): ?><?= h($date) ?><?php endif ?></div>
                  <div class="col-4 col-sm-4">
                    <div class="input-group sg-stepper">
                      <button class="btn btn-outline-secondary btn-subtract" type="button" aria-label="<?= __('Less') ?>"><i class="fa fa-minus" aria-hidden="true"></i></button>
                      <?= $this->Form->text('quantity.' . $ticket->id . '.' . $date, [
                          'value' => $qty,
                          'class' => 'form-control',
                          'inputmode' => 'numeric',
                          'aria-label' => __('Quantity'),
                          'data-price' => $ticket->price,
                          'max' => $maxQty ?? '',
                          'min' => $ticket->min_quantity,
                      ]) ?>
                      <button class="btn btn-outline-secondary btn-add" type="button" aria-label="<?= __('More') ?>"><i class="fa fa-plus" aria-hidden="true"></i></button>
                    </div>
                  </div>
                  <div class="col-3 row-total text-end"><?= $this->Number->currency($ticket->price * $qty) ?></div>
                  <div class="col-1 text-end">
                    <a href="#" role="button" class="btn btn-danger btn-sm tr-10 cart-remove"
                       data-post-url="<?= $this->Url->build(['action' => 'removeTicket', $ticket->id, $date]) ?>"
                       data-confirm="<?= h(__('Remove {0} from cart?', strip_tags((string)$ticket->name))) ?>"
                       aria-label="<?= __('Remove') ?>" title="<?= __('Remove') ?>"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
                  </div>
                </div>
              <?php endforeach ?>
            </div>
            <?php endif ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="col-lg-4">
      <div class="sg-panel sg-sticky-summary cart-summary p-4">
        <h2 class="h4 mb-3"><?= __('Order summary') ?></h2>
        <div class="d-flex justify-content-between small text-muted mb-1">
          <span><?= __('Tickets') ?></span><span class="cart-item-count"><?= $itemCount ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-baseline summary-total">
          <span class="total"><strong><?= __('Total') ?></strong>: <span class="total-amount"><?= $this->Number->currency($total) ?></span></span>
        </div>
        <?= $this->Form->button(__('Checkout'), ['class' => 'btn btn-cta btn-lg w-100 mt-3', 'id' => 'btn-checkout']) ?>
        <p class="small text-muted mt-2 mb-0"><i class="fa fa-info-circle me-1" aria-hidden="true"></i><?= __('Quantity changes are saved when you check out.') ?></p>
        <?= $this->Html->link('<i class="fa fa-arrow-left me-1" aria-hidden="true"></i>' . __('Continue shopping'), ['controller' => 'Attractions', 'action' => 'index'], ['class' => 'btn btn-link w-100 mt-1', 'escape' => false]) ?>
      </div>
    </div>
  </div>
<?= $this->Form->end() ?>
<div class="form-loader"><div class="spinner-border" role="status"><span class="visually-hidden"><?= __('Loading') ?></span></div></div>

<div class="sg-mobile-bar">
  <div class="sg-mobile-bar-total">
    <div class="price-label"><?= __('Total') ?></div>
    <div class="sg-mobile-bar-amount price-net price-net-sm"><?= $this->Number->currency($total) ?></div>
  </div>
  <button type="submit" form="cart-form" class="btn btn-cta"><?= __('Checkout') ?></button>
</div>
<?php endif ?>
