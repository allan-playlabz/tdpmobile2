<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Attraction $attraction
 */
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
      <?= h($attraction->title) ?> <?php if ($auth_user['role'] != 'viewer'): ?><i class="float-right fa <?= $attraction->is_favorite ? 'fa-bookmark text-green' : 'fa-bookmark-o' ?> btn-favorite" title="<?= $attraction->is_favorite ? __('Click to remove from favorites') : __('Click to add to favorites') ?>"></i><?php endif ?>
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
          <?php echo $this->Form->create(null,['class'=>'application-form form-deferred']) ?>
            <h3><?= __('Options') ?></h3>
            <div class="row">
              <div class="col">
                <?php echo $this->Form->control('departure_date',['class'=>'form-control','type'=>'text','label'=>__('Depart Date'), 'data-type' => 'departure','autocomplete'=>'off']) ?>
              </div>
            </div>
            <div class="row" id="time-wrapper">
              <div class="col">
                <?php echo $this->Form->control('departure_time',['class'=>'form-control','options'=>[],'empty'=>__('Please select time'),'label'=>__('Depart Time')]) ?>
              </div>
            </div>
            <div class="row">
              <div class="col">
                <?php echo $this->Form->control('return_date',['class'=>'form-control','type'=>'text','label'=>__('Return Date'), 'data-type' => 'return','autocomplete'=>'off']) ?>
              </div>
            </div>
            <div class="row" id="time-wrapper">
              <div class="col">
                <?php echo $this->Form->control('return_time',['class'=>'form-control','options'=>[],'empty'=>__('Please select time'),'label'=>__('Return Time')]) ?>
              </div>
            </div>
            <div class="tickets-wrapper">
              <!-- <div class="h4"><?= __('Select ticket(s)') ?></div> -->
              <div class="tickets">
                <h5><?= __('Depart Tickets') ?></h5>
                <div class="depart-tickets"></div>
                <hr>
                <h5><?= __('Return Tickets') ?></h5>
                <div class="return-tickets"></div>
              </div>
            </div>

            <hr>
            <div class="total">

            </div>
            <?php if ($auth_user['role'] != 'viewer'): ?>
            <div class="actions mb-4">
              <?php echo $this->Form->button(__('Buy Now'), ['name'=>'buy_now','id'=>'btn-buy-now','value'=>'buy_now','class'=>'btn btn-outline-success','style'=>'display:none']) ?>
            </div>
            <?php endif ?>
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
var cart_count = <?= $cart_count ?>;
var not_allowed_to_add = <?= isset($not_allowed_to_add) ? 'true' : 'false' ?>;
var total_text = '<?= __('Total') ?>';
var no_tickets_text = '<?= __('No tickets found!') ?>';
var select_time_text = '<?= __('Select time') ?>';
var currency_sign = '<?= __('$') ?>';
var clearTickets = function() {
  $('.tickets .depart-tickets').empty();
  $('.tickets .return-tickets').empty();
}
var showNoTickets = function() {
  $('.tickets .depart-tickets .alert.alert-danger').remove();
  $('.tickets .depart-tickets').append('<div class="alert alert-danger">' + no_tickets_text + '</div>');
  $('.tickets .return-tickets .alert.alert-danger').remove();
  $('.tickets .return-tickets').append('<div class="alert alert-danger">' + no_tickets_text + '</div>');
}
var checkTickets = function() {
  clearTickets();
  $('.tickets .depart-tickets, .tickets .return-tickets').append('<div class="spinner-border text-primary form-loader"></div>');

  $.ajax({
    url: '/attractions/get-two-way-tickets/<?= $attraction->id ?>',
    type: 'GET',
    data: {departure_date:$('#departure-date').val(),departure_time: $('#departure-time').val(), return_date:$('#return-date').val(),return_time: $('#return-time').val()},
    dataType: 'JSON',
    headers :{'X-CSRF-Token':'<?= $this->request->getParam('_csrfToken')?>'},
  }).done(function(res) {
    clearTickets()
    $('.total').html('<div class="total"><strong>' + total_text + '</strong>: $0.00</div>');
    if (res.data && res.data.tickets.length > 0) {

      var tickets = res.data.tickets;
      var tickets_max_quantity = res.data.tickets_max_quantity;
      var return_ticket_ids = res.data.return_ticket_ids;
      clearTickets();
      for (var i = 0; i < tickets.length; i++) {
        var ticket = tickets[i];
        var ticket_max_quantity = tickets_max_quantity[ticket.id];
        var is_return = !Object.hasOwn(return_ticket_ids, ticket.id);
        if (typeof ticket.max_quantity !== 'undefined' && ticket.max_quantity !== null && ticket_max_quantity > 0) {
          if (ticket_max_quantity > ticket.max_quantity) {
            ticket_max_quantity = ticket.max_quantity;
          }
        }
        var ticket_string = '<div class="card my-2">';
        ticket_string += '<div class="card-body">';
        ticket_string += '<div class="card-text">';
        ticket_string += '<div class="row">';
        ticket_string += '<div class="col-lg-5"><b>' + ticket.name + '</b><br><i class="text-10">' + ticket.sku_id + '</i><br>' + (ticket.retail_price ? ('<del>RP SGD' + ticket.retail_price.toFixed(2) + '</del>') : '') + '</div>';
        ticket_string += '<div class="col-lg-3">$' + ticket.price.toFixed(2) + '</div>';
        if (!not_allowed_to_add) {
          var disabled = typeof(return_ticket_ids[ticket.id]) === 'undefined' ? 'disabled' : '';
          var minus_button_group = '<div class="input-group-prepend"><button ' + disabled + ' class="btn btn-outline-secondary btn-subtract" type="button"><i class="fa fa-minus"></i></button></div>';
          var add_button_group = '<div class="input-group-append"><button ' + disabled + '  class="btn btn-outline-secondary btn-add" type="button"><i class="fa fa-plus"></i></button></div>';
          ticket_string += '<div class="col-lg-4"><div class="input-group">' + minus_button_group + '<input ' + (disabled !== '' ? 'readonly' :  '') + ' id="qty-' + ticket.id + '" type="text" class="form-control text-center" name="quantity['+ticket.id+']" value="0" data-price="'+ticket.price+'" data-return-ticket-id="' + return_ticket_ids[ticket.id] + '" max="'+ ticket_max_quantity +'" min="'+ ticket.min_quantity +'">' + add_button_group + '</div>';
          if (typeof tickets_max_quantity[ticket.id] == 'undefined' || tickets_max_quantity[ticket.id] == 0) {
            ticket_string += '<div class="text-danger font-weight-bold text-center">Sold out</div>';
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
            setInputValue(input, qty + 1);
          } else {
            setInputValue(input, min);
          }


          computeTotalPrice();
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
            setInputValue(input, new_value);
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
            setInputValue(input, qty);
          } else {
            setInputValue(input, min);
          }
          if (typeof input.attr('max') !== 'undefined') {
            var max = parseInt(input.attr('max'));
            if (qty > max) {
              qty = max;
              setInputValue(input, qty);
            }
          }
          if (qty > 0) {
            computeTotalPrice();
          }
          else {
            setInputValue(input, 0);
          }
          if (getTotalQty() <= 0) {
            $('#btn-add-to-cart,#btn-buy-now').hide();
          }
          else {
            $('#btn-add-to-cart,#btn-buy-now').show();
          }
        });
        if (ticket.ticket_trip_type.type == 'depart') {
          $('.tickets .depart-tickets').append($ticket_element);
        } else {
          $('.tickets .return-tickets').append($ticket_element);
        }
        
      }
    }
    else {
      showNoTickets();
    }
  })
};

var setInputValue = function (input, qty) {
  input.val(qty);
  if (input.data('return-ticket-id') != 'undefined') {
    $('#qty-' + input.data('return-ticket-id')).val(qty);
  }
}

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

var populateTimings = function(type, res) {
  var time_dropdown = '#' + type + '-time';
  $(time_dropdown).empty();
  clearTickets();
  var time_string = '<option value="">' + select_time_text + '</option>';
  $('.total').html('<div class="total"><strong>' + total_text + '</strong>: $0.00</div>');
  $(time_dropdown).append(time_string);
  if (res.data.length > 0) {
    
    var times = res.data;
    for (var i = 0; i < times.length; i++) {
      var _time = times[i];

      var time_string = '<option value="'+_time.time_value+'">' + _time.time_display + '</option>';
      $(time_dropdown).append(time_string);
    }
  }
  else {
    showNoTickets();
  }
}


$(document).ready(function() {
    $('#departure-date, #return-date').on('change',function() {
      var datepicker = $(this);
      var type = datepicker.data('type');
      if ($(this).val() != '') {
        $.ajax({
          url: '/attractions/get-fixeddate-time/<?= $attraction->id ?>',
          type: 'GET',
          data: {date:datepicker.val(), type: type},
          dataType: 'JSON',
          headers :{'X-CSRF-Token':'<?= $this->request->getParam('_csrfToken') ?>'}
        }).done(function(res) {
          populateTimings(type, res);
        });
      }
      else {
        clearTickets();
      }
    });
    $('#departure-time, #return-time').off('change').on('change', function() {
      if ($('#departure-time').val() != '' && $('#return-time').val() != '') {
        checkTickets();
      }
      else {
        clearTickets();
      }
    });

  <?php if (!empty($attraction->api_sku)): ?>
    $('#date').on('change',function() {
      if ($(this).val() != '') {
        $.ajax({
          url: '/attractions/get-availability/<?= $attraction->id ?>',
          type: 'GET',
          data: {date:$('#date').val()},
          dataType: 'JSON',
          headers :{'X-CSRF-Token':'<?= $this->request->getParam('_csrfToken') ?>'}
        }).done(function(res) {
          console.log(res);
        });
      }
      else {
        clearTickets();
      }
    });
    $('#time').off('change').on('change', function() {
      if ($(this).val() != '') {
        checkTickets();
      }
      else {
        clearTickets();
      }
    });
  <?php endif ?>

  <?php
    if ($attraction->pricing_type !='settlement' && !empty($available_dates)) {
  ?>
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

  <?php
    echo 'var available_dates = '.json_encode($available_dates).';';

  } ?>

  $('#departure-date, #return-date').datepicker({
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

    var checkEqualQuantity = function () {
      var depart_quanity = 0;
      var return_quantity = 0;

      $('.depart-tickets input[name^="quantity"]').each(function(idx, input) {
        depart_quantity = parseInt($(input).val());
      });

      $('.return-tickets input[name^="quantity"]').each(function(idx, input) {
        return_quantity = parseInt($(input).val());
      });

      return depart_quantity == return_quantity;
    };

    $('button[type="submit"]').on('click',function(e) {
      var total_qty = getTotalQty();

      if (total_qty <= 0) {
        e.preventDefault();
        alert('Please select at least 1 to continue.');
        return false;
      }
      if (checkEqualQuantity() === false) {
        e.preventDefault();
        alert('Depart ticket quantity and return ticket quantity should be equal.');
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

    if (cart_count > 0) {
      $('#btn-buy-now').on('click', function(e) {
        if (checkEqualQuantity() === false) {
          e.preventDefault();
          return;
        }
        if (!confirm('Cart will be cleared to make this purchase')) {
          e.preventDefault();
        }
      });
    }

    $('.btn-favorite').on('click',function() {
      toggleFavorite($(this), '<?= $attraction->id ?>', '<?= $this->request->getParam('_csrfToken') ?>');
    });
})

<?php $this->Html->scriptEnd() ?>
<style>
.tickets .btn { padding: 1px 6px; }
.tickets-wrapper .row > div { padding-right: 4px; padding-left: 4px; }
</style>
