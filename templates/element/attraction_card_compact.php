<?php
/**
 * Compact attraction card: image + title + chinese title only (no price/footer).
 * Used by the recommended showcase row. Styles: theme.css (.attraction-card-compact).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Attraction $attraction
 * @var string $base Public host prefix for images.
 */
use App\Lib\PricingType;

$url = $attraction->pricing_type == PricingType::TWO_WAY_FDATETIME
    ? $this->Url->build(['controller' => 'Attractions', 'action' => 'viewTwoWay', $attraction->id])
    : $this->Url->build(['controller' => 'Attractions', 'action' => 'view', $attraction->id]);
?>
<a class="attraction-link" href="<?= $url ?>">
  <div class="attraction-media">
    <?php if ($attraction->featured_image): ?>
      <img class="card-img-top object-fit-cover" src="<?= h($base . urlencode($attraction->featured_image)) ?>" alt="" loading="lazy" decoding="async">
    <?php else: ?>
      <div class="card-img-top attraction-media-empty"><i class="fa fa-picture-o" aria-hidden="true"></i></div>
    <?php endif ?>
  </div>
  <div class="card-body">
    <div class="card-title">
      <?= h($attraction->title, false) ?>
      <?php if (!empty($attraction->title_chinese)): ?><span class="title-zh"><?= h($attraction->title_chinese, false) ?></span><?php endif ?>
    </div>
  </div>
</a>
