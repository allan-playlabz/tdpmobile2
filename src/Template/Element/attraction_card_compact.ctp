<?php
/**
 * Compact attraction card: image + title + chinese title only (no price/footer).
 * Used by the recommended showcase row.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Attraction $attraction
 * @var string $base Public host prefix for images.
 */
use App\Lib\PricingType;
?>
<a href="<?= $attraction->pricing_type == PricingType::TWO_WAY_FDATETIME ? $this->Url->build(['controller'=>'Attractions','action'=>'viewTwoWay',$attraction->id]) : $this->Url->build(['controller'=>'Attractions','action'=>'view',$attraction->id]) ?>">
  <div class="card-img-top" style="background-image:<?= $attraction->featured_image ? 'url(\''.$base.urlencode($attraction->featured_image).'\')' : ''; ?>"></div>
  <div class="card-body">
    <div class="card-title">
      <?= $attraction->title ?>
      <?= !empty($attraction->title_chinese) ? '<br>'.$attraction->title_chinese : '' ?>
    </div>
  </div>
</a>
