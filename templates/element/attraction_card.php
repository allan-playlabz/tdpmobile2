<?php
/**
 * Attraction card body (image + title + price + footer badges).
 * Reused by the main attractions grid and the recommended showcase row.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Attraction $attraction
 * @var string $base Public host prefix for images.
 */
use App\Lib\PricingType;
?>
<a href="<?= $attraction->pricing_type == PricingType::TWO_WAY_FDATETIME ? $this->Url->build(['controller'=>'Attractions','action'=>'viewTwoWay',$attraction->id]) : $this->Url->build(['controller'=>'Attractions','action'=>'view',$attraction->id]) ?>">
  <div class="card-img-top" style="background-image:<?= $attraction->featured_image ? 'url(\''.$base.urlencode($attraction->featured_image).'\')' : ''; ?>"></div>

    <div class="card-body position-relative">
      <div class="card-title">
        <?= $attraction->title ?>
        <?= !empty($attraction->title_chinese) ? '<br>'.$attraction->title_chinese :'' ?>
      </div>
        <?php
        if ($attraction->pricing_type == 'settlement') {
          if (!empty($attraction->ticket) && !empty($attraction->ticket->settlement_agents) && !empty($attraction->ticket->settlement_agents[0]->settlement)) {
              echo '<div class="rate"><del class="text-muted old-price">RP '.$this->Number->currency($attraction->ticket->price).'</del> <div class="font-weight-bold">'.$this->Number->currency($attraction->ticket->settlement_agents[0]->settlement->rate).'</div></div>';
          }
        }
        else {
          if (!empty($attraction->ticket)) {
              echo '<div class="rate"><del class="text-muted old-price">RP '.$this->Number->currency($attraction->ticket->price).'</del> <div><small>'. __('Please select date') . '</small></div></div>';
          }
        }

        ?>
        <?= isset($attraction->fav_id) && $attraction->fav_id !== null ? '<i class="position-absolute fa fa-bookmark fav-attraction text-green"></i>' : '' ?>
    </div>
    <div class="card-footer">
      <div>
        <?php
        if (!empty($attraction->ticket) && $attraction->pricing_type == 'settlement') {
            echo '<div class="fixed_date text-success"><i class="fa fa-ticket" aria-hidden="true"></i>' . __('Open Date') . '</div>';
        } elseif (!empty($attraction->ticket) && $attraction->pricing_type == 'fdate') {
            echo '<div class="fixed_date text-success"><i class="fa fa-ticket" aria-hidden="true"></i>' . __('Fixed Date') . '</div>';
        } elseif (!empty($attraction->ticket) && $attraction->pricing_type == 'fdatetime') {
            echo '<div class="fixed_date text-success"><i class="fa fa-ticket" aria-hidden="true"></i>' . __('Fixed Date/Time') . '</div>';
        } elseif (!empty($attraction->ticket) && $attraction->pricing_type == PricingType::TWO_WAY_FDATETIME) {
          echo '<div class="fixed_date text-success"><i class="fa fa-ticket" aria-hidden="true"></i>' . __('2-Way Fixed Date/Time') . '</div>';
        }
        ?>
        <?php
        if (!empty($attraction->ticket) && $attraction->first_open_date) {
            echo '<div class="open_date"><i class="fa fa-calendar" aria-hidden="true"></i> '. $attraction->first_open_date .'</div>';
          }
        ?>
      </div>
      <div>
        <?php
        if (!empty($attraction->ticket) && $attraction->ticket->instant_confirmation) {
            echo '<div class="instant_confirmation text-success"><i class="fa fa-bolt" aria-hidden="true"></i>' . __('Instant') . '</div>';
        } else {
            echo '<div class="instant_confirmation"><i class="fa fa-bolt" aria-hidden="true"></i>' . __('Request') . '</div>';
        }
        ?>
        <?php
        if (!empty($attraction->ticket) && ($attraction->ticket->require_travellers_info || $attraction->ticket->question_group_id)) {
            echo '<div class="require_travellers_info"><i class="fa fa-id-card-o" aria-hidden="true"></i>' . __('Require info') . '</div>';
        }
        ?>
      </div>
    </div>
</a>
