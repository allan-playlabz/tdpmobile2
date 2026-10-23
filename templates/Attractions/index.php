<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Attraction[]|\Cake\Collection\CollectionInterface $attractions
 */

use App\Lib\PricingType;
use Cake\Core\Configure;
use Cake\Routing\Router;
$base = \Cake\Core\Configure::read('AWSPublic.public_host');
// $attractions = $attractions->toArray();
$sCategories = $selected_categories;
?>
<?php if (!empty($recommended_attractions)): ?>
<div class="recommended-section pt-3">
  <div class="mb-2">
    <span class="badge p-2 text-white" style="background:linear-gradient(90deg,#28a745,#007bff);"><?= __('Recommend') ?></span>
  </div>
  <div class="recommended-carousel-wrap position-relative">
    <?php if (count($recommended_attractions) > 1): ?>
      <button type="button" class="rec-nav rec-prev" aria-label="<?= __('Previous') ?>"><i class="fa fa-chevron-left"></i></button>
      <button type="button" class="rec-nav rec-next" aria-label="<?= __('Next') ?>"><i class="fa fa-chevron-right"></i></button>
    <?php endif ?>
    <div class="recommended-carousel">
      <div class="recommended-track">
        <?php foreach ($recommended_attractions as $attraction): ?>
        <div class="recommended-item">
          <div class="card attraction-card attraction-card-compact">
            <?= $this->element('attraction_card_compact', ['attraction' => $attraction, 'base' => $base]) ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php endif ?>
<div class="row py-3">
    <div class="col-md-4 col-lg-3">
        <?php
          $queryParams = $this->request->getQueryParams();
          $formUrl = ['controller'=>'Attractions','action'=>'index', '?' => $queryParams];
          echo $this->Form->create(null,['type'=>'get','class'=>'form-inline','url'=>$formUrl]);
        ?>
        <?php echo $this->Form->control('search',['class'=>'form-control','default'=>$this->request->getQuery('search'),'label'=>false,'placeholder'=>__('Name of Attractions')]) ?>
        <?php echo $this->Form->submit(__('Search'),['class'=>'btn btn-primary form-group']) ?>
        <?php echo $this->Html->link(__('Show All'),['action'=>'index'],['class'=>'btn btn-primary ml-1 form-group']) ?>
        <br />
        <div class="search-categories w-100 py-4">
          <h4><?= __('Categories') ?></h4>
          <div class="accordion" id="accordion">
            <?php $cardCount = 1; ?>
            <?php foreach ($categories as $key => $value) { ?>
            <div class="card">
              <div class="card-header" id="heading-<?=$cardCount?>">
                <h6 class="p-2 mb-0">
                  <input class="headclass headclass<?=$cardCount?> ml-2" data-headclass-id="<?=$cardCount?>" name="categories[]" value="<?= $value['id'] ?>" type="checkbox" <?= in_array($value['id'], $sCategories) ? 'checked' : '' ?> /> <?=$value['name']?>
                  <button class="btn btn-link float-right collapsed" type="button" data-toggle="collapse" data-target="#collapse<?=$cardCount?>" aria-expanded="true" aria-controls="collapse<?=$cardCount?>"><i class="fa fa-angle-down" aria-hidden="true"></i>
                  </button>
                </h6>
              </div>

              <div id="collapse<?=$cardCount?>" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                <div class="card-body">
                <ul>
                  <?php foreach ($value['child_categories'] as $ckey => $cvalue) {
                    if(in_array($cvalue['id'],$sCategories)) {
                      echo '<li><input class="subclass subclass'.$cardCount.'" data-headclass-id="'.$cardCount.'" type="checkbox" name="categories[]" value="'.$cvalue['id'].'" checked /> '.$cvalue['name'].'</li>';
                    } else {
                      echo '<li><input class="subclass subclass'.$cardCount.'" data-headclass-id="'.$cardCount.'" name="categories[]" value="'.$cvalue['id'].'" type="checkbox" /> '.$cvalue['name'].'</li>';
                    }
                  }?>
                </ul>
                </div>
              </div>
            </div>
            <?php
              $cardCount++;
              }
            ?>
          </div>
        </div>

        <div class="search-countries w-100 py-2">
          <h4><?= __('Country') ?></h4>
          <div class="card">
            <div class="card-body p-2">
              <div class="country-checkboxes accordion" id="country-accordion" style="text-align:left;">
                <?php $countryIndex = 1; ?>
                <?php $selected_city_strings = array_map('strval', $selected_cities); ?>
                <?php foreach ($citiesByCountry as $countryName => $countryCities): ?>
                  <?php $isLastCountry = $countryIndex == count($citiesByCountry); ?>
                  <div class="country-group <?= $isLastCountry ? '' : 'border-bottom' ?>">
                    <div class="d-flex align-items-center py-1" id="country-heading-<?= $countryIndex ?>">
                      <input class="country-check" type="checkbox"
                             data-country-id="<?= $countryIndex ?>" id="country-<?= $countryIndex ?>" />
                      <label class="font-weight-bold mb-0 ml-2" for="country-<?= $countryIndex ?>"><?= h($countryName) ?></label>
                      <button class="btn btn-link collapsed ml-auto p-0" type="button" data-toggle="collapse" data-target="#country-collapse-<?= $countryIndex ?>" aria-expanded="false" aria-controls="country-collapse-<?= $countryIndex ?>"><i class="fa fa-angle-down" aria-hidden="true"></i></button>
                    </div>
                    <div id="country-collapse-<?= $countryIndex ?>" class="collapse" aria-labelledby="country-heading-<?= $countryIndex ?>" data-parent="#country-accordion">
                      <div class="pl-3 pb-2">
                        <?php foreach ($countryCities as $city): ?>
                          <div class="d-flex align-items-center pt-1">
                            <input class="city-check city-check-<?= $countryIndex ?>" type="checkbox"
                                   data-country-id="<?= $countryIndex ?>"
                                   name="cities[]" value="<?= h($city->id) ?>"
                                   id="city-<?= $countryIndex ?>-<?= h($city->id) ?>"
                                   <?= in_array((string)$city->id, $selected_city_strings, true) ? 'checked' : '' ?> />
                            <label class="mb-0 ml-2" for="city-<?= $countryIndex ?>-<?= h($city->id) ?>"><?= h($city->name) ?></label>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                  <?php $countryIndex++; ?>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <?php echo $this->Form->end() ?>
    </div>
    <div class="col-md-8 col-lg-9">
      <div class="row">
      <?php if (!empty($attractions)): ?>
          <?php foreach ($attractions as $attraction): ?>
          <div class="col-md-4 col-lg-3 mb-2">
            <div class="card attraction-card">
              <?= $this->element('attraction_card', ['attraction' => $attraction, 'base' => $base]) ?>
            </div>
          </div>
          <?php endforeach; ?>
          <div class="col-12">
            <div class="paginator">
                <ul class="pagination">
                    <?= $this->Paginator->first('<< ' . __('first')) ?>
                    <?= $this->Paginator->prev('< ' . __('previous')) ?>
                    <?= $this->Paginator->numbers() ?>
                    <?= $this->Paginator->next(__('next') . ' >') ?>
                    <?= $this->Paginator->last(__('last') . ' >>') ?>
                </ul>
                <p><?= $this->Paginator->counter(['format' => __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')]) ?></p>
            </div>
          </div>
      <?php else: ?>
          <div class="col">
              <div class="alert alert-warning"><?= __('No attractions found.') ?></div>
          </div>
      <?php endif ?>
    </div>
  </div>
</div>
<style>
.attraction-card,.attraction-card .card-footer {
  border-color: #aaa;
}
.attraction-card a {
  color: #444;
  text-decoration: none;
}
.attraction-card .card-title {
  font-size: 14px;
  font-weight: 400;
  line-height: 20px;
  max-height: 40px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}
.attraction-card .rate { font-size: 16px; position:absolute; bottom: 9px; }
.attraction-card .fixed_date {}
.attraction-card .instant_confirmation {}
.attraction-card .card-body { height: 100px; }
.attraction-card .card-body,.attraction-card .card-footer { padding: 9px; }
.attraction-card .card-footer { display: flex; }
.attraction-card .card-footer div { flex:auto; }
.card-img-top { height: 154px; background-size: cover; background-position: center; }
.search-categories ul { padding-inline-start: 0px; }
.search-categories li { list-style-type: none; }
.search-categories a { color: initial; }
.rate .old-price {
  font-size: 12px;
  position: relative;
  top: 8px;
}
/* Recommended auto-scrolling carousel */
.recommended-section { margin-bottom: 2.5rem; }
.recommended-carousel-wrap { position: relative; }
.recommended-carousel { overflow: hidden; }
.rec-nav {
  position: absolute; top: 50%; transform: translateY(-50%); z-index: 2;
  width: 34px; height: 34px; border: none; border-radius: 50%;
  background: rgba(0,0,0,0.5); color: #fff; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.rec-nav:hover { background: rgba(0,0,0,0.7); }
.rec-nav:focus { outline: none; }
.rec-prev { left: -6px; }
.rec-next { right: -6px; }
.recommended-track { display: flex; will-change: transform; }
.recommended-item { flex: 0 0 16.666%; max-width: 16.666%; padding: 0 6px; }
@media (max-width: 991px) { .recommended-item { flex: 0 0 25%; max-width: 25%; } }
@media (max-width: 767px) { .recommended-item { flex: 0 0 33.333%; max-width: 33.333%; } }
@media (max-width: 575px) { .recommended-item { flex: 0 0 50%; max-width: 50%; } }
/* Compact recommended card: image + title only, no footer/price, uniform height */
.attraction-card-compact .card-img-top { height: 110px; }
.attraction-card-compact .card-body { height: 62px; }
.attraction-card-compact .card-title { -webkit-line-clamp: 2; line-clamp: 2; max-height: none; }
</style>
<?php $this->Html->scriptStart(['block' => true]) ?>
$(document).ready(function(){
  $('.headclass').click(function(){
    if($(this).prop("checked") == true){
      $('.subclass'+$(this).data('headclass-id')).prop("checked",true);
    } else {
      $('.subclass'+$(this).data('headclass-id')).prop("checked",false);
    }
  });
  $('.headclass').each(function(){
    var headclass_id = $(this).data('headclass-id');
    var subclass_total = $('.subclass'+headclass_id).length;
    var subclass_checked = $('.subclass'+headclass_id+':checked').length;
    if(subclass_total==subclass_checked && subclass_total > 0) {
      $(this).prop("checked",true);
    }
  });
  $('.subclass').click(function(){
    if($(this).prop("checked") == false){
      $('.headclass'+$(this).data('headclass-id')).prop("checked",false);
    }
  });

  // Country <-> city checkbox syncing (with partial/indeterminate state).
  function syncCountry(id){
    var $cities = $('.city-check-'+id);
    var total = $cities.length;
    if(total === 0){ return; }
    var checked = $cities.filter(':checked').length;
    var $country = $('.country-check[data-country-id="'+id+'"]');
    if(checked === 0){
      $country.prop('checked', false).prop('indeterminate', false);
    } else if(checked === total){
      $country.prop('checked', true).prop('indeterminate', false);
    } else {
      $country.prop('checked', false).prop('indeterminate', true);
    }
  }
  $('.country-check').click(function(){
    var checked = $(this).prop('checked');
    $('.city-check-'+$(this).data('country-id')).prop('checked', checked);
    $(this).prop('indeterminate', false);
  });
  $('.city-check').click(function(){
    syncCountry($(this).data('country-id'));
  });
  // Initialise each country's state from its cities on page load.
  $('.country-check').each(function(){
    syncCountry($(this).data('country-id'));
  });

  // Recommended carousel: auto-advance + manual prev/next, looping seamlessly.
  (function(){
    var $wrap = $('.recommended-carousel-wrap');
    if (!$wrap.length) { return; }
    var $carousel = $wrap.find('.recommended-carousel');
    var $track = $carousel.find('.recommended-track');
    var $originals = $track.children('.recommended-item');
    var origCount = $originals.length;
    if (origCount <= 1) { return; }
    // Clone enough items so both forward loop-back and prev-wrap stay seamless
    // even with few items (up to 6 visible on large screens).
    while ($track.children('.recommended-item').length < origCount + 6) {
      $originals.clone(true).appendTo($track);
    }
    var idx = 0, animating = false, timer = null;
    function itemWidth(){ return $track.children('.recommended-item').first().outerWidth(true); }
    function go(animate){
      $track.css('transition', animate ? 'transform 0.6s ease' : 'none');
      $track.css('transform', 'translateX(' + (-idx * itemWidth()) + 'px)');
    }
    // Move by one card. The animating lock keeps rapid clicks from desyncing.
    function move(delta){
      if (animating) { return; }
      animating = true;
      // Going before the first card: jump to the equivalent cloned position
      // (identical view) with no transition, then animate one step back.
      if (delta < 0 && idx <= 0) {
        idx = origCount;
        go(false);
        $track[0].offsetHeight; // force reflow so the next transition applies
      }
      idx += delta;
      go(true);
      setTimeout(function(){
        if (idx >= origCount) { idx -= origCount; go(false); }
        animating = false;
      }, 620);
    }
    function startAuto(){ stopAuto(); timer = setInterval(function(){ move(1); }, 3000); }
    function stopAuto(){ if (timer) { clearInterval(timer); timer = null; } }
    $wrap.find('.rec-next').on('click', function(e){ e.preventDefault(); move(1); });
    $wrap.find('.rec-prev').on('click', function(e){ e.preventDefault(); move(-1); });
    // Pause auto-advance while the user is interacting so it doesn't fight them.
    $wrap.on('mouseenter', stopAuto).on('mouseleave', startAuto);
    $(window).on('resize', function(){ go(false); });
    startAuto();
  })();

  <?php if($auth_user['announcements_read']==0) {?>
    $('#read-new').click();
  <?php } ?>
});
<?php $this->Html->scriptEnd() ?>
