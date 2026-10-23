<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Attraction[]|\Cake\Collection\CollectionInterface $attractions
 */
use Cake\Core\Configure;
use Cake\Routing\Router;
$base = Configure::read('TDP.image_base');
// $attractions = $attractions->toArray();
$sCategories = $selected_categories;
?>
<div class="row py-3">
    <div class="col-md-4">
        <?php echo $this->Form->create(null,['type'=>'post','class'=>'form-inline']) ?>
        <?php echo $this->Form->control('search',['class'=>'form-control','default'=>$this->request->getQuery('search'),'label'=>false,'placeholder'=>'Name of Attractions']) ?>
        <?php echo $this->Form->submit('Search',['class'=>'btn btn-primary form-group']) ?>
        <?php echo $this->Html->link('Show All',['action'=>'index'],['class'=>'btn btn-primary ml-1 form-group']) ?>
        <br />
        <div class="search-categories w-100 py-4">
          <h4>Categories</h4>
          <div class="accordion" id="accordion">
            <?php $cardCount = 1; ?>
            <?php foreach ($categories as $key => $value) { ?>
            <div class="card">
              <div class="card-header" id="heading-<?=$cardCount?>">
                <h6 class="p-2 mb-0">
                  <input class="headclass headclass<?=$cardCount?> ml-2" data-headclass-id="<?=$cardCount?>" type="checkbox" /> <?=$value['name']?>
                  <button class="btn btn-link float-right collapsed" type="button" data-toggle="collapse" data-target="#collapse<?=$cardCount?>" aria-expanded="true" aria-controls="collapse<?=$cardCount?>"><i class="fa fa-angle-down" aria-hidden="true"></i>
                  </button>
                </h6>
              </div>

              <div id="collapse<?=$cardCount?>" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                <div class="card-body">
                <ul>
                  <?php foreach ($value['child_categories'] as $ckey => $cvalue) {
                    if(in_array($cvalue['id'],$sCategories)) {
                      echo '<li><input class="subclass subclass'.$cardCount.'" data-headclass-id="'.$cardCount.'" type="checkbox" name="categories[]" value="'.$cvalue['id'].'" checked />'.$cvalue['name'].'</li>';
                    } else {
                      echo '<li><input class="subclass subclass'.$cardCount.'" data-headclass-id="'.$cardCount.'" name="categories[]" value="'.$cvalue['id'].'" type="checkbox" />'.$cvalue['name'].'</li>';
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

        <?php echo $this->Form->end() ?>
    </div>
    <div class="col-md-8">
      <div class="row">
      <?php if (!empty($attractions)): ?>
          <?php foreach ($attractions as $attraction): ?>
          <div class="col-md-4 mb-2">
            <div class="card attraction-card">
              <a href="/attractions/view/<?=$attraction->id?>" target="_blank">
                <div class="card-img-top" style="background-image:<?= $attraction->featured_image ? 'url(\''.$base.$attraction->featured_image.'\')' : ''; ?>"></div>

                  <div class="card-body position-relative">
                    <div class="card-title">
                      <?=$attraction->title ?>
                    </div>
                      <?php
                      if ($attraction->pricing_type == 'settlement') {
                        if (!empty($attraction->tickets) && !empty($attraction->tickets[0]->settlement_agents) && !empty($attraction->tickets[0]->settlement_agents[0]->settlement)) {
                            echo '<div class="rate"><del class="text-muted old-price">S'.$this->Number->currency($attraction->tickets[0]->price).'</del> <div class="font-weight-bold">S'.$this->Number->currency($attraction->tickets[0]->settlement_agents[0]->settlement->rate).'</div></div>';
                        }
                      }
                      else {

                        if (!empty($attraction->tickets)) {
                            echo '<div class="rate"><del class="text-muted old-price">RP S'.$this->Number->currency($attraction->tickets[0]->price).'</del> <div><small>Please select date</small></div></div>';
                        }
                      }

                      ?>
                  </div>
                  <div class="card-footer">
                    <div>
                      <?php
                      if (!empty($attraction->tickets) && $attraction->pricing_type == 'settlement') {
                          echo '<div class="fixed_date text-success"><i class="fa fa-ticket" aria-hidden="true"></i> Open Date</div>';
                      } elseif (!empty($attraction->tickets) && $attraction->pricing_type == 'fdate') {
                          echo '<div class="fixed_date text-success"><i class="fa fa-ticket" aria-hidden="true"></i> Fixed Date</div>';
                      }
                      else {
                          echo '<div class="fixed_date text-success"><i class="fa fa-ticket" aria-hidden="true"></i> Fixed Date/Time</div>';
                      }
                      ?>
                      <?php
                      if (!empty($attraction->tickets) && $attraction->first_open_date) {
                          echo '<div class="open_date"><i class="fa fa-calendar" aria-hidden="true"></i> '. $attraction->first_open_date .'</div>';
                        }
                      ?>
                    </div>
                    <div>
                      <?php
                      if (!empty($attraction->tickets) && $attraction->tickets[0]->instant_confirmation) {
                          echo '<div class="instant_confirmation text-success"><i class="fa fa-bolt" aria-hidden="true"></i> Instant</div>';
                      } else {
                          echo '<div class="instant_confirmation"><i class="fa fa-bolt" aria-hidden="true"></i> Request</div>';
                      }
                      ?>
                      <?php
                      if (!empty($attraction->tickets) && $attraction->tickets[0]->require_travellers_info) {
                          echo '<div class="require_travellers_info"><i class="fa fa-id-card-o" aria-hidden="true"></i> Require info</div>';
                      }
                      ?>
                    </div>
                  </div>
              </a>
            </div>
          </div>
          <?php endforeach; ?>
      <?php else: ?>
          <div class="col">
              <div class="alert alert-warning">No attractions found.</div>
          </div>
      <?php endif ?>
    </div>
  </div>
</div>
<style>
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
    if(subclass_total==subclass_checked) {
      $(this).prop("checked",true);
    }
  });
  $('.subclass').click(function(){
    if($(this).prop("checked") == false){
      $('.headclass'+$(this).data('headclass-id')).prop("checked",false);
    }
  });
});
<?php $this->Html->scriptEnd() ?>
