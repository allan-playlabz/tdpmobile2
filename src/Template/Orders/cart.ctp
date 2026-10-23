<?php
use Cake\Core\Configure;
$base = \Cake\Core\Configure::read('AWSPublic.public_host');
?>
<?= $this->Form->create(null,['class'=>'form-deferred']) ?>
<?php $total = 0; ?>
<?php if (empty($tickets)): ?>
  <div class="alert alert-info">No items on cart.</div>
<?php else: ?>

  <div class="mt-3">
    <?php //echo $this->Form->control('valid_for_foreign_only',['class'=>'form-control']) ?>
  </div>
  <h1><?= __('Your Cart') ?></h1>

  <?php if (!empty($tickets)): ?>

    <?php foreach ($tickets as $ticket): ?>
      <div class="card mb-1 ticket-card">
        <div class="card-body">
          <div class="row">
            <div class="col-5 col-md-2">
              <?php echo $this->Html->link($this->Html->image($base.$ticket->attraction->featured_image,['class'=>'img-thumbnail']),['controller'=>'attractions','action'=>'view',$ticket->attraction_id],['escape'=>false]) ?>
            </div>
            <div class="col-7 col-md-3">
              <?php echo $this->Html->link('<div class="font-weight-bold">'.$ticket->attraction->title .'</div> <div class="text-muted">'. $ticket->name.'<br><del>RP '.$this->Number->currency($ticket->retail_price) .'</del></div>',['controller'=>'attractions','action'=>'view',$ticket->attraction_id],['escape'=>false]) ?> <?= $ticket->attraction->type =='transfer' ? ' - '.Configure::read('Default.transfer_subtypes.'.$ticket->attraction->subtype) : '' ?>
              <div><?php echo $this->Number->currency($ticket->price) ?></div>
            </div>
          </div>
          <?php if ($ticket_list[$ticket->id]): ?>
          <?php foreach ($ticket_list[$ticket->id] as $date => $qty): ?>
            <?php $total += $ticket->price * $qty; ?>
            <div class="row py-2">
              <div class="col-4 col-md-4">
                <?php if ($ticket->attraction->pricing_type !='settlement'): ?>
                  <div><?php echo $date ?></div>
                <?php endif ?>
              </div>
              <div class="col-4 col-md-5">
                <div class="input-group">
                  <div class="input-group-prepend"><button class="btn btn-outline-secondary btn-sm btn-subtract" type="button"><i class="fa fa-minus"></i></button></div>
                  <?php 
                  if ($ticket->max_quantity && !empty($tickets_max_quantity[$ticket->id])) {
                    if ($ticket->max_quantity < $tickets_max_quantity[$ticket->id]) {
                      $tickets_max_quantity[$ticket->id] = $ticket->max_quantity;
                    }
                  } ?>
                  <?= $this->Form->text('quantity.'.$ticket->id.'.'.$date,['value'=>$qty,'class'=>'form-control p-1 text-center','data-price'=>$ticket->price,'max'=> !empty($tickets_max_quantity[$ticket->id]) ? $tickets_max_quantity[$ticket->id] : '','min'=> $ticket->min_quantity]) ?>

                  <div class="input-group-append"><button class="btn btn-outline-secondary btn-sm btn-add" type="button"><i class="fa fa-plus"></i></button></div>
                </div>

              </div>
              <div class="col-3 col-md-2 row-total text-right">
                <?php echo $this->Number->currency($ticket->price * $qty) ?>
              </div>
              <div class="col-1 col-md-1"><?= $this->Form->postLink('<i class="fa fa-times"></i>',['action'=>'removeTicket',$ticket->id,$date],['escape'=>false,'confirm'=>__('Remove {0} from cart?', $ticket->name),'class'=>'btn btn-danger btn-sm tr-10','block'=>'postLink']) ?></div>
            </div>
          <?php endforeach ?>
          <?php endif ?>

        </div>
      </div>
    <?php endforeach; ?>
    <hr>
    <div class="row mt-3">
      <div class="col text-right pr-5">
        <span class="total"><strong><?= __('Total') ?></strong>: <?= $this->Number->currency($total) ?></span>
      </div>
    </div>
  <?php endif ?>
  <br>
  <div> <?= $this->Form->button(__('Checkout'),['class'=>'btn btn-primary']); ?></div>

<?php endif ?>
<?= $this->Form->end() ?>
<div class="spinner-border text-primary form-loader"></div>
<?= $this->fetch('postLink') ?>
<?= $this->Html->css(['bootstrap-datepicker3.standalone.min'],['block'=>'internal_css']); ?>
<?php $this->start('css') ?>
<style type="text/css">
  .total {
    font-size: 16px;
  }
  a:hover {
    text-decoration: none;
  }
  .ticket-card .row {
    margin-right: -5px;
    margin-left: -5px;
  }
  .ticket-card .col-4,.ticket-card .col-3,.ticket-card .col-1,.ticket-card .col-md-5,.ticket-card .col-md-4,.ticket-card .col-md-2,.ticket-card .col-md-1 {
    padding-right: 5px;
    padding-left: 5px;
  }
</style>
<?php $this->end() ?>
<?= $this->Html->script(['bootstrap-datepicker.min'],['block'=>'scriptBottom']) ?>
<?php $this->Html->scriptStart(['block' => true]) ?>
    var d = new Date();
    var cart_total = <?= $total ?>;
    var total_text = '<?= __('Total') ?>';
    d.setMonth(d.getMonth() - 1);

    $('.dob').datepicker({format:'d M yyyy',endDate: d});
    d.setMonth(d.getMonth() + 2)
    $('.passport-expiry').datepicker({format:'d M yyyy',startDate: d});

    var computeTotalPrice = function () {
      cart_total = 0;
      $('input[name^="quantity"]').each(function(idx, i) {
        var input = $(i);
        var qty = parseInt(input.val());
        if (qty > 0) {
          var row_total = parseFloat(input.data('price')) * qty;
          input.parent().parent().parent().find('.row-total').text('SGD'+row_total.toFixed(2));
          cart_total += row_total;
        }
      });
      $('.total').html('<strong>' + total_text + '</strong>: SGD' + cart_total.toFixed(2));
    };

    var getTotalQty = function () {
      var $total_qty = 0;
      $('input[name^="quantity"]').each(function(idx, input) {
        $total_qty += parseInt($(input).val());
      });
      return $total_qty;
    };

    $(document).ready(function() {
      $('.btn-add').on('click',function() {
      var input = $(this).parent().parent().find('input');
      var qty = parseInt(input.val());
      if (qty >= parseInt(input.attr('max'))) {
        return;
      }

      var min = parseInt(input.attr('min')) > 1 ? parseInt(input.attr('min')) : 1;
      if (qty + 1 >= min) {
        input.val(qty + 1);
      } else {
        input.val(min);
      }
      computeTotalPrice();
      $(this).parent().parent().parent().parent().find('.row-total').first().html('SGD'+(parseFloat(input.data('price')) * (qty+1)).toFixed(2));

      if (getTotalQty() <= 0) {
        $('#btn-add-to-cart,#btn-buy-now').hide();
      }
      else {
        $('#btn-add-to-cart,#btn-buy-now').show();
      }
    });
    $('.btn-subtract').on('click',function() {
      var input = $(this).parent().parent().find('input');
      var qty = parseInt(input.val());
      var min = parseInt(input.attr('min')) > 1 ? parseInt(input.attr('min')) : 0;
      if (qty > 1) {
        var new_value = parseInt(input.val()) - 1;
        if (min > 0 && new_value < min) {
          new_value = min;
        }
        $(this).parent().parent().parent().parent().find('.row-total').first().html('SGD'+(parseFloat(input.data('price')) * (new_value)).toFixed(2));
        input.val(new_value);
        computeTotalPrice();
      }
      if (getTotalQty() <= 0) {
        $('#btn-add-to-cart,#btn-buy-now').hide();
      }
      else {
        $('#btn-add-to-cart,#btn-buy-now').show();
      }
    });
    $('input[name^="quantity"]').on('change',function() {
      var input = $(this);
      var qty = parseInt(input.val());
      var min = parseInt(input.attr('min')) > 1 ? parseInt(input.attr('min')) : 0;
      var new_value = parseInt(input.val());
      if (min > 0 && new_value < min) {
        new_value = min;
        input.val(new_value);
      }
      computeTotalPrice();
    });
    computeTotalPrice();
    })
<?php $this->Html->scriptEnd() ?>
