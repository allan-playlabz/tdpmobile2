<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Attraction $attraction
 */

use App\Lib\PricingType;
use Cake\Core\Configure;
$base = \Cake\Core\Configure::read('AWSPublic.public_host');
$now = Cake\I18n\Time::now();
?>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/"><?= __('Home') ?></a></li>
    <li class="breadcrumb-item"><?= $attraction->has('category') ? $this->Form->postLink($attraction->category->name, ['controller' => 'Attractions', 'action' => 'index'],['data'=>['categories[]'=>$attraction->category->id]]) : '' ?></li>
    <li class="breadcrumb-item active" aria-current="page"><?= h($attraction->title) ?></li>
  </ol>
</nav>
<div class="row">
<div class="col-md-8">
    <div><?php echo $this->Html->image($base.$attraction->featured_image,['class'=>'img-fluid','style'=>'width:100%']) ?></div>
    <h1 class="mt-4">
      <?= h($attraction->title) ?> <i class="float-right fa <?= $attraction->is_favorite ? 'fa-bookmark text-green' : 'fa-bookmark-o' ?> btn-favorite" title="<?= $attraction->is_favorite ? __('Click to remove from favorites') : __('Click to add to favorites') ?>"></i>
      <?php if (Configure::read('Default.show_attraction_sku')) { 
        echo '<br><small style="font-size: 12px" class="text-muted">'. $attraction->sku_id . '</small>';
      } ?>
    </h1>
    <div><?= $attraction->title_chinese ?></div>
    <div class="mw-100 overflow-hidden">
      <p><?= str_replace('<img src="../../images/','<img src="'.$base.'/images/',$attraction->description); ?></p>
    </div>
  <div id="accordion">
    <div class="card">
      <div class="card-header p-4" id="headingTwo">
        <h5 class="mb-0">
          <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" style="height:49px;">
            <?= __('Terms And Conditions') ?>
          </button>
        </h5>
      </div>
      <div id="collapseTwo" class="collapse show" aria-labelledby="headingTwo" data-parent="#accordion">
        <div class="card-body">
        <?= $attraction->terms_and_conditions; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="col-md-4">
    <div class="row">
        <div class="col-md" id="options">
          <?php echo $this->Form->create(null,['class'=>'application-form form-deferred']); ?>
            <h3><?= __('Options') ?></h3>
            <div class="row">
              <div class="col">
                <?php echo $this->Form->control('date',['class'=>'form-control date-picker','type'=>'text', 'label'=>__('Date'),'autocomplete'=>'off']) ?>
              </div>
            </div>
            <?php if (!empty($attraction->tickets)){ ?>
              <?php foreach ($attraction->tickets as $key => $ticket){ ?>
                  <?php if ($attraction->pricing_type == PricingType::MANDAI_PACKAGE){ ?>
                  <?php 
                    $package_config = json_decode($ticket->package_config);
                    foreach($package_config as $park_idx => $park) {
                      if ($park->is_dated) {
                    ?>
                    <div class="row">
                      <div class="col">
                        <?php echo $this->Form->control('dates[' . $park->sku_id . ']',['class'=>'form-control date-picker park-date','type'=>'text', 'data-type' => 'date', 'label'=>__('{0} Visit Date', $park->label),'autocomplete'=>'off']) ?>
                      </div>
                    </div>
                    <?php if (!empty($park->timeslots)){ ?>
                      <div class="row" id="time-wrapper">
                        <div class="col">
                          <?php echo $this->Form->control('times[' . $park->sku_id . ']',['class'=>'form-control park-time','options'=>array_combine($park->timeslots, $park->timeslots),'data-type' => 'time', 'empty'=>__('Please select time'),'label'=>__('{0} Visit Time', $park->label)]) ?>
                        </div>
                      </div>
                    <?php } ?>
                    <?php
                      }
                    }
                  } ?>
              <?php } ?>
            <?php } ?>
            <div class="tickets-wrapper">
              <div class="h4"><?= __('Select ticket(s)') ?></div>
              <div class="tickets">
                  <?php if (!empty($attraction->tickets)):  ?>
                    <?php foreach ($attraction->tickets as $key => $ticket): ?>
                        
                        <?php
                        $ticket_dates = [];
                        if (!empty($ticket->fixeddates)) {
                          foreach ($ticket->fixeddates as $key => $fd) {
                            $fd_date = $fd->date;
                            $fd_time = $fd->time;
                            if ($fd->date) {
                              $fd_date = $fd->date->format('d M Y');
                            }
                            if ($fd->time->format('H') != 0) {
                              $fd_time = $fd->time->format('H:i');
                            }
                            else {
                              $fd_time = __('Any Time');
                            }
                            if (empty($ticket_dates[$fd_date])) {
                              $ticket_dates[$fd_date.'|'.$fd_time] = $fd_date.' '.$fd_time;
                            }
                          }
                        }
                         ?>
                          <div class="card-body d-none ticket-card ticket-<?= $ticket->id ?>">
                            <div class="card-text">
                              <div class="row">
                                <div class="col-lg-5"><b><?= $ticket->name ?></b> <br>
                                    <i class="text-10"><?= $ticket->sku_id ?></i> <br>
                                  <span>RP <?= $this->Number->currency($ticket->retail_price) ?></span> <br>
                                  <?php if (!empty($ticket->minimum_selling_price)) { ?>
                                  <span>MSP <?= $this->Number->currency($ticket->minimum_selling_price) ?></span> <br>
                                  <?php } ?>
                                  <?php if ($ticket->redemption_type=='redemption_date') { ?>
                                    <div><?= __('Valid From') ?> <?php echo $ticket->redemption_start_date ? $ticket->redemption_start_date->format('d M Y'):'' ?> To <?php echo $ticket->redemption_end_date ? $ticket->redemption_end_date->format('d M Y'):'' ?></div>
                                  <?php } else { ?>
                                    <div><?= __('Valid for') ?> <?php echo $ticket->duration.' '.__($ticket->duration_type) ?> </div>
                                  <?php } ?>
                                </div>
                                <div class="col-lg-3"><?= $this->Number->currency($ticket->price) ?></div>
                                <div class="col-lg-4">
                                  <?php if (!isset($not_allowed_to_add)): ?>
                                  <?php 
                                  if ($ticket->max_quantity && $tickets_max_quantity[$ticket->id] > $ticket->max_quantity) {
                                    $tickets_max_quantity[$ticket->id] = $ticket->max_quantity;
                                  }
                                  ?>
                                  <div class="input-group"><div class="input-group-prepend"><button class="btn btn-outline-secondary btn-subtract" type="button"><i class="fa fa-minus"></i></button></div><?= $this->Form->text('quantity.'.$ticket->id,['value'=>0,'class'=>'form-control p-1 text-center','data-price'=>$ticket->price,'min'=>($ticket->min_quantity ? $ticket->min_quantity : '0'),'max'=> $tickets_max_quantity[$ticket->id]]) ?>
                                  <div class="input-group-append"><button class="btn btn-outline-secondary btn-add" type="button"><i class="fa fa-plus"></i></button></div>
                                  </div>
                                  <?php if (empty($tickets_max_quantity[$ticket->id])) {
                                    echo '<div class="text-danger font-weight-bold text-center">'. __('Sold out') .'</div>';
                                   } ?>
                                  <?php endif ?>
                                  <?php if ($ticket->show_stock): ?>
                                    <div class="text-center">Remaining: <?= $tickets_max_quantity[$ticket->id] ?></div>
                                  <?php endif ?>
                                </div>
                              </div>
                              <?php if (!empty($ticket->agent_notification)): ?>
                                <div>
                                  <?= $ticket->agent_notification ?>
                                </div>
                              <?php endif ?>
                            </div>
                          </div>

                    <?php endforeach ?>
                  <?php endif ?>

              </div>
            </div>
            <div class="total">

            </div>
            <div class="actions mb-4">
              <?php echo $this->Form->button(__('Buy Now'), ['name'=>'buy_now','id'=>'btn-buy-now','value'=>'buy_now','class'=>'btn btn-outline-success','style'=>'display:none']) ?>
            </div>
            <?php echo $this->Form->end() ?>
            <div class="spinner-border text-primary form-loader"></div>
        </div>
    </div>

    <div class="row">
        <?php if (!empty($attraction->attraction_medias)): ?>
            <?php foreach ($attraction->attraction_medias as $key => $attraction_media): ?>
                <div class="col-md-4 col-sm-6"><?php echo $this->Html->image($base.$attraction_media->url,['class'=>'img-fluid']) ?></div>
            <?php endforeach ?>
        <?php endif ?>
    </div>


</div>
</div>
<?= $this->Html->css(['bootstrap-datepicker3.standalone.min'],['block'=>'internal_css']); ?>
<?= $this->Html->script(['bootstrap-datepicker.min', 'favorite-attraction.js?v=2025'],['block'=>'scriptBottom']) ?>
<?php $this->Html->scriptStart(['block' => true]) ?>
var cart_total = 0;
var cart_has_two_way_fdatetime = <?= !empty($cart_has_two_way_fdatetime) ? 'true' : 'false' ?>;
var not_allowed_to_add = <?= isset($not_allowed_to_add) ? 'true' : 'false' ?>;
var total_text = '<?= __('Total') ?>';
var no_tickets_text = '<?= __('No tickets found!') ?>';
var select_time_text = '<?= __('Select time') ?>';
var currency_sign = '<?= __('$') ?>';
var attraction_pricing_type = '<?= $attraction->pricing_type ?>';
var attraction_id = '<?= $attraction->id ?>';
var csrf_token = '<?= $this->request->getParam('_csrfToken') ?>';
var available_dates = <?= json_encode($available_dates ?? []) ?>;
var tickets_max_quantity = 0;
var sku_id = '<?= $attraction->tickets[0]->sku_id ?>';
var get_package_availability_url = '<?= $this->Url->build(['controller' => 'Attractions', 'action' => 'getPackageAvailability'], ['fullBase' => true]) ?>';
                        
var checkTickets = function() {
  $('.tickets').empty().append('<div class="spinner-border text-primary form-loader"></div>');

  $.ajax({
    url: '/attractions/get-tickets/<?= $attraction->id ?>',
    type: 'GET',
    data: {date:$('#date').val(),time: $('#time').val()},
    dataType: 'JSON',
    headers :{'X-CSRF-Token':'<?= $this->request->getParam('_csrfToken')?>'},
  }).done(function(res) {
    $('.tickets').empty();
    $('.total').html('<div class="total"><strong>' + total_text + '</strong>: $0.00</div>');
    if (res.data && res.data.tickets.length > 0) {

      var tickets = res.data.tickets;
      var tickets_max_quantity = res.data.tickets_max_quantity;
      $('.tickets').empty();
      for (var i = 0; i < tickets.length; i++) {
        var ticket = tickets[i];
        var ticket_max_quantity = tickets_max_quantity[ticket.id];
        if (typeof ticket.max_quantity !== 'undefined' && ticket.max_quantity !== null && ticket_max_quantity > 0) {
          if (ticket_max_quantity > ticket.max_quantity) {
            ticket_max_quantity = ticket.max_quantity;
          }
        }
        var ticket_string = '<div class="card my-2">';
        ticket_string += '<div class="card-body">';
        ticket_string += '<div class="card-text">';
        ticket_string += '<div class="row">';
        let msp = ticket.minimum_selling_price ? '<br><span>MSP SGD' + ticket.minimum_selling_price.toFixed(2) + '</span>' : '';
        ticket_string += '<div class="col-lg-5"><b>' + ticket.name + '</b><br><i class="text-10">' + ticket.sku_id + '</i><br><span>RP SGD' + ticket.retail_price.toFixed(2) + '</span>' + msp + '</div>';
        ticket_string += '<div class="col-lg-3">$' + ticket.price.toFixed(2) + '</div>';
        if (!not_allowed_to_add) {
          if (typeof tickets_max_quantity[ticket.id] == 'undefined' || tickets_max_quantity[ticket.id] == 0) {
            ticket_string += '<div class="col-lg-4"><div class="input-group"><div class="input-group-prepend"><button class="btn btn-outline-secondary btn-subtract" type="button"><i class="fa fa-minus"></i></button></div><input type="text" class="form-control text-center" name="quantity['+ticket.id+']" value="0" data-price="'+ticket.price+'" max="0" min="'+ ticket.min_quantity +'"><div class="input-group-append"><button class="btn btn-outline-secondary btn-add" type="button"><i class="fa fa-plus"></i></button></div></div>';
            ticket_string += '<div class="text-danger font-weight-bold text-center">Sold out</div>';
          } else {
            ticket_string += '<div class="col-lg-4"><div class="input-group"><div class="input-group-prepend"><button class="btn btn-outline-secondary btn-subtract" type="button"><i class="fa fa-minus"></i></button></div><input type="text" class="form-control text-center" name="quantity['+ticket.id+']" value="0" data-price="'+ticket.price+'" max="'+ ticket_max_quantity +'" min="'+ ticket.min_quantity +'"><div class="input-group-append"><button class="btn btn-outline-secondary btn-add" type="button"><i class="fa fa-plus"></i></button></div></div>';
            if (ticket.show_stock) {
              ticket_string += '<div class="text-center">Remaining: ' + tickets_max_quantity[ticket.id] + '</div>';
            }
          }
          ticket_string += '</div>';
        }
        if (ticket.agent_notification) {
          ticket_string += '<div>'+ ticket.agent_notification +'</div>';
        }
        ticket_string += '</div>';
        ticket_string += '</div>';
        ticket_string += '</div>';
        var $ticket_element = $(ticket_string);
        $ticket_element.find('.btn-add').on('click',function() {
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
          console.log(getTotalQty());
          if (getTotalQty() <= 0) {
            $('#btn-add-to-cart,#btn-buy-now').hide();
          }
          else {
            $('#btn-add-to-cart,#btn-buy-now').show();
          }
        });
        $ticket_element.find('.btn-subtract').on('click',function() {
          var input = $(this).parent().parent().find('input');
          var qty = parseInt(input.val());
          var min = parseInt(input.attr('min')) > 1 ? parseInt(input.attr('min')) : 0;
          if (qty > 0) {
            var new_value = parseInt(input.val()) - 1;
            if (min > 0 && new_value < min) {
              new_value = 0;
            }
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
        $ticket_element.find('input[name^="quantity"]').on('change',function() {
          var input = $(this);
          var qty = parseInt(input.val());
          var min = parseInt(input.attr('min')) > 1 ? parseInt(input.attr('min')) : 0;
          if (qty >= min) {
            input.val(qty);
          } else {
            input.val(min);
          }
          if (typeof input.attr('max') !== 'undefined') {
            var max = parseInt(input.attr('max'));
            if (qty > max) {
              qty = max;
              input.val(qty);
            }
          }
          if (qty > 0) {
            computeTotalPrice();
          }
          else {
            input.val(0);
          }
          if (getTotalQty() <= 0) {
            $('#btn-add-to-cart,#btn-buy-now').hide();
          }
          else {
            $('#btn-add-to-cart,#btn-buy-now').show();
          }
        });
        $('.tickets').append($ticket_element);
      }
    }
    else {
      $('.tickets .alert.alert-danger').remove();
      $('.tickets').append('<div class="alert alert-danger">' + no_tickets_text + '</div>');
    }
  })
};

var getTotalQty = function () {
  var $total_qty = 0;
  $('input[name^="quantity"]').each(function(idx, input) {
    $total_qty += parseInt($(input).val());
  });
  return $total_qty;
};

var computeTotalPrice = function () {
  cart_total = 0;
  $('input[name^="quantity"]').each(function(idx, i) {
    var input = $(i);
    var qty = parseInt(input.val());
    if (qty > 0) {
      cart_total += parseFloat(input.data('price')) * qty;
    }
  });
  $('.total').html('<strong>' + total_text + '</strong>: SGD' + cart_total.toFixed(2));
};

$(document).ready(function() {
  $('#date').datepicker({
    format:'d M yyyy',
    'startDate':'today',
    'autoclose':true,
    beforeShowDay: function(date){
      if (typeof available_dates != 'undefined' && available_dates.length > 0 && available_dates.indexOf(formatDate(date)) < 0) {
      return {
          enabled: false
        };
      }
      else if (<?= $attraction->pricing_type !='settlement' && empty($available_dates) ? 1 : 0 ?>) {
        return {enabled: false};
      }
      else {
        return {
          enabled: true
        };
      }
    }
  });
  $('.date-picker').on('change',function() {
    let all_filled = true;
    let data = {date: $('#date').val(), sku_id: sku_id};
    $('.park-date, .park-time').each(function(idx, el) {
      if ($(el).val() != '' && all_filled) {
        all_filled = true;
        if ($(el).data('type') == 'time') {
          data[$(el).attr('name')] = $(el).val();
        } else {
          data[$(el).attr('name')] = $(el).val();
        }
        
      } else {
        all_filled = false;
        //return false; // break the loop
      }
    });
    if ($('#date').val() == '') {
      all_filled = false;
      return;
    }
    if (!all_filled) {
      return;
    } else {
      $('.tickets').removeClass('d-none');
    }
    console.log(data);
    //var sku_id = $(this).data('sku-id');
      $.ajax({
        url: get_package_availability_url,
        type: 'GET',
        data: data,
        dataType: 'JSON',
        headers :{'X-CSRF-Token': csrf_token}
      }).done(function(res) {
        for (ticket_id in res.data) {
          let qty = res.data[ticket_id];
          if (qty > 0) {
            //$('.ticket-' + ticket_id).removeClass('d-none');
            $('.tickets .ticket-card').removeClass('d-none');
            $('input[name="quantity[' + ticket_id + ']"]').attr('max', qty);
          }
        }
      });
  });
  $('#time').off('change').on('change', function() {
    if ($(this).val() != '') {
      //checkTickets();
    }
    else {
      $('.tickets').empty();
    }
  });
  
  function formatDate(d) {
    var day = String(d.getDate())
    //add leading zero if day is is single digit
    if (day.length == 1)
      day = '0' + day
    var month = String((d.getMonth()+1))
    //add leading zero if month is is single digit
    if (month.length == 1)
      month = '0' + month
    return day + "-" + month + "-" + d.getFullYear()
  }

  

  $('.date-picker').datepicker({
    format:'d M yyyy',
    'startDate':'today',
    'autoclose':true
  });


    var cart_total = 0;

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
      if (qty > 0) {
        var new_value = parseInt(input.val()) - 1;
        if (min > 0 && new_value < min) {
          new_value = 0;
        }
        input.val(new_value);

      }
      computeTotalPrice();
      if (getTotalQty() <= 0) {
        $('#btn-add-to-cart,#btn-buy-now').hide();
      }
      else {
        $('#btn-add-to-cart,#btn-buy-now').show();
      }
    });

    $('button[type="submit"]').on('click',function(e) {
      var total_qty = getTotalQty();

      if (total_qty <= 0) {
        e.preventDefault();
        alert('Please select at least 1 to continue.');
        return false;
      }
    });
    $('input[name^="quantity"]').on('change',function() {
      var input = $(this);
      var qty = parseInt(input.val());
      var min = parseInt(input.attr('min')) > 0 ? parseInt(input.attr('min')) : 0;
      if (qty >= min) {
        input.val(qty);
      } else {
        input.val(min);
      }
      if (typeof input.attr('max') !== 'undefined') {
        var max = parseInt(input.attr('max'));
        if (qty > max) {
          qty = max;
          input.val(qty);
        }
      }
      if (qty > 0) {
        computeTotalPrice();
      }
      else {
        input.val(0);
      }
      if (getTotalQty() <= 0) {
        $('#btn-add-to-cart,#btn-buy-now').hide();
      }
      else {
        $('#btn-add-to-cart,#btn-buy-now').show();
      }
    });
    computeTotalPrice();

    if (cart_has_two_way_fdatetime) {
      $('#btn-buy-now').on('click', function(e) {
        if (!confirm('Cart will be cleared to make this purchase')) {
          e.preventDefault();
        }
      });
      $('#btn-add-to-cart').on('click', function(e) {
        if (!confirm('Cart will be cleared to make this purchase')) {
          e.preventDefault();
        }
      });
    }

    $('.btn-favorite').on('click',function() {
      toggleFavorite($(this), attraction_id, csrf_token);
    });
})

<?php $this->Html->scriptEnd() ?>
<style>
.tickets .btn { padding: 1px 6px; }
.tickets-wrapper .row > div { padding-right: 4px; padding-left: 4px; }
</style>
