<?php
/**
 * Attraction card body (image + type badge + title + price + facts), inside .card.attraction-card.
 * Used by the attractions grid. Styles: theme.css (.attraction-card).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Attraction $attraction
 * @var string $base Public host prefix for images.
 */
use App\Lib\PricingType;

$ticket = $attraction->ticket ?? null;
$url = $attraction->pricing_type == PricingType::TWO_WAY_FDATETIME
    ? $this->Url->build(['controller' => 'Attractions', 'action' => 'viewTwoWay', $attraction->id])
    : $this->Url->build(['controller' => 'Attractions', 'action' => 'view', $attraction->id]);
$typeLabels = [
    'settlement' => __('Open Date'),
    'fdate' => __('Fixed Date'),
    'fdatetime' => __('Fixed Date/Time'),
    PricingType::TWO_WAY_FDATETIME => __('2-Way Fixed Date/Time'),
];
$typeLabel = $ticket ? ($typeLabels[$attraction->pricing_type] ?? null) : null;
$netRate = null;
if ($attraction->pricing_type == 'settlement' && $ticket && !empty($ticket->settlement_agents) && !empty($ticket->settlement_agents[0]->settlement)) {
    $netRate = $ticket->settlement_agents[0]->settlement->rate;
}
$isFavourite = isset($attraction->fav_id) && $attraction->fav_id !== null;
?>
<a class="attraction-link" href="<?= $url ?>">
  <div class="attraction-media">
    <?php if ($attraction->featured_image): ?>
      <img class="card-img-top object-fit-cover" src="<?= h($base . urlencode($attraction->featured_image)) ?>" alt="" loading="lazy" decoding="async">
    <?php else: ?>
      <div class="card-img-top attraction-media-empty"><i class="fa fa-picture-o" aria-hidden="true"></i></div>
    <?php endif ?>
    <?php if ($typeLabel): ?>
      <span class="badge attraction-type attraction-type-<?= h($attraction->pricing_type) ?>"><?= $typeLabel ?></span>
    <?php endif ?>
    <?php if ($isFavourite): ?>
      <span class="fav-attraction" title="<?= __('Favourite') ?>"><i class="fa fa-bookmark text-green" aria-hidden="true"></i><span class="visually-hidden"><?= __('Favourite') ?></span></span>
    <?php endif ?>
  </div>

  <div class="card-body">
    <div class="card-title">
      <?= h($attraction->title, false) ?>
      <?php if (!empty($attraction->title_chinese)): ?><span class="title-zh"><?= h($attraction->title_chinese, false) ?></span><?php endif ?>
    </div>
    <?php if ($ticket): ?>
    <div class="rate">
      <?php if ($netRate !== null): ?>
        <div class="price-label"><?= __('Your price') ?></div>
        <div class="price-net"><?= $this->Number->currency($netRate) ?></div>
        <div class="price-ref old-price"><del><abbr title="<?= __('Retail price') ?>">RP</abbr> <?= $this->Number->currency($ticket->price) ?></del></div>
      <?php elseif ($attraction->pricing_type != 'settlement'): ?>
        <div class="text-navy fw-semibold small"><i class="fa fa-calendar-check-o me-1" aria-hidden="true"></i><?= __('Please select date') ?></div>
        <div class="price-ref old-price"><del><abbr title="<?= __('Retail price') ?>">RP</abbr> <?= $this->Number->currency($ticket->price) ?></del></div>
      <?php endif ?>
    </div>
    <?php endif ?>
  </div>

  <div class="card-footer">
    <?php if ($ticket && $ticket->instant_confirmation): ?>
      <span class="sg-fact text-success instant_confirmation"><i class="fa fa-bolt" aria-hidden="true"></i> <?= __('Instant') ?></span>
    <?php else: ?>
      <span class="sg-fact instant_confirmation"><i class="fa fa-clock-o" aria-hidden="true"></i> <?= __('Request') ?></span>
    <?php endif ?>
    <?php if ($ticket && $attraction->first_open_date): ?>
      <span class="sg-fact open_date"><i class="fa fa-calendar" aria-hidden="true"></i> <?= h($attraction->first_open_date) ?></span>
    <?php endif ?>
    <?php if ($ticket && ($ticket->require_travellers_info || $ticket->question_group_id)): ?>
      <span class="sg-fact require_travellers_info"><i class="fa fa-id-card-o" aria-hidden="true"></i> <?= __('Require info') ?></span>
    <?php endif ?>
  </div>
</a>
