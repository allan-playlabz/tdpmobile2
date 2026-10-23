<?php
use Cake\Utility\Inflector;
use Cake\Core\Configure;
$subtotal = 0;
$other_fees = 0;
if ($this->request->getSession()->check('language') && !empty($other_payment_methods)) {
  foreach ($other_payment_methods as $other_payment_method_key => $other_payment_method_name) {
    $other_payment_methods[$other_payment_method_key] = __($other_payment_method_name);
  }
}
$payment_methods = $is_credits_enabled ? ['credits'=>__('Credits (Balance: {0})', $this->Number->currency($auth_user['credits'])).$this->Html->link(__('Topup'),['controller'=>'AgentTopups','action'=>'add','?'=>['redirect'=>'orders/confirmation']])] : [];
$payment_methods = array_merge($payment_methods, $other_payment_methods);
 ?>
  <div class="row">
    <div class="col">
      <?= $this->Html->link('<i class="fa fa-chevron-left"></i> '.__('Back'),['action'=>'checkout'],['escape'=>false]); ?>
    </div>
  </div>
  <h3><?= __('Confirmation') ?></h3>
  <br>
  <div class="row">
    <div class="col-md-6 col-lg-8">
    <?php
      if (!empty($order->checkout_info) && !is_array($order->checkout_info)) {
        $checkout_info = json_decode($order->checkout_info);
        if (!empty($checkout_info)) {
    ?>
      <div class="card mb-2">
        <div class="card-header bg-primary text-white">
          <div><?= __('Customer Information') ?></div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4">
              <?php echo $this->Form->control('tracking_no',['class'=>'form-control','label'=>__('Order Tracking Number'),'type'=>'text','value'=>$checkout_info->tracking_no,'disabled'=>'disabled']) ?>
            </div>
            <div class="col-md-4">
              <?php echo $this->Form->control('first_name',['class'=>'form-control','required'=>'required','label'=>__('First Name (English only)*'),'value'=>$checkout_info->first_name,'disabled'=>'disabled']) ?>
            </div>
            <div class="col-md-4">
              <?php echo $this->Form->control('last_name',['class'=>'form-control','required'=>'required','label'=>__('Last Name (English only)*'),'value'=>$checkout_info->last_name,'disabled'=>'disabled']) ?>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <?php echo $this->Form->control('email',['class'=>'form-control','required'=>'required','label'=>__('Email*'),'value'=>$checkout_info->email,'disabled'=>'disabled']) ?>
            </div>
            <div class="col-md-4">
              <?php echo $this->Form->control('mobile',['class'=>'form-control','required'=>'required','label'=>__('Mobile*'),'value'=>$checkout_info->mobile,'disabled'=>'disabled']) ?>
            </div>
          </div>
        </div>
      </div>
    <?php
        }
      }
    ?>
      <?php if (!empty($order->order_items)) { ?>
        <?php
        $required_info = [];
        $transfer_details = [];
        $return_transfer_details = [];
        foreach ($order->order_items as $key => $order_item) {
          $order_item_data = json_decode($order_item->order_data);
          if (!empty($order_item_data)) {
            if (!empty($order_item_data->required_info)) {
              $required_info = array_merge($required_info, (array)$order_item_data->required_info);
            }
            if (!empty($order_item_data->transfer_details)) {
              $transfer_details = $order_item_data->transfer_details;
            }
            if (!empty($order_item_data->return_transfer_details)) {
              $return_transfer_details = $order_item_data->return_transfer_details;
            }
          }
        }

        ?>
        <?php if (!empty($required_info) || !empty($transfer_details) || !empty($return_transfer_details)) { ?>
          <div class="card mb-2">
            <div class="card-header bg-primary text-white">
              Order Information
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <?php if (!empty($required_info)) { ?>
                  <h6>Travellers</h6>
                  
                  <table class="table">
                    <tr>
                      <th><?= __('Passport Number') ?></th>
                      <th><?= __('Passport Expiry') ?></th>
                      <th><?= __('First Name') ?></th>
                      <th><?= __('Last Name') ?></th>
                      <th><?= __('Date of Birth') ?></th>
                      <th><?= __('Nationality') ?></th>
                    </tr>
                  <?php foreach ($required_info as $key => $td) { ?>
                    <tr>
                      <td><?= $td->passport_number ?></td>
                      <td><?= !empty($td->passport_expiry) ? $td->passport_expiry : '' ?></td>
                      <td><?= $td->passport_first_name ?></td>
                      <td><?= $td->passport_last_name ?></td>
                      <td><?= $td->date_of_birth ?></td>
                      <td><?= $td->nationality ?></td>
                    </tr>
                    <?php if (!empty($td->remarks)): ?>
                      <tr><td colspan="6"><i><?= $td->remarks ?></i></td></tr>
                    <?php endif ?>
                  <?php } ?>
                  </table>
                <?php } ?>
                <?php if (!empty($transfer_details)): ?>
                    <h6><?= __('Transfer Details') ?></h6>
                    <table class="table">
                    <?php foreach ($transfer_details as $key => $value): ?>
                      <tr>
                        <td><?= Inflector::humanize($key) ?></td>
                        <td><?= $value ?></td>
                      </tr>
                    <?php endforeach ?>
                    </table>
                <?php endif ?>
                <?php if (!empty($return_transfer_details)): ?>
                    <h6><?= __('Return Transfer Details') ?></h6>
                    <table class="table">
                    <?php foreach ($return_transfer_details as $key => $value): ?>
                      <tr>
                        <td><?= Inflector::humanize($key) ?></td>
                        <td><?= $value ?></td>
                      </tr>
                    <?php endforeach ?>
                    </table>
                <?php endif ?>
              </div>
            </div>
          </div>
        <?php } ?>
        <?php if (!empty($tickets_with_question_group)) { ?>
        <div class="card card-primary my-3">
          <div class="card-header bg-primary text-white">
              Ticket Information
          </div>
          <div class="card-body ticket-details-container">
          <?php 
          foreach ($order->order_items as $key => $order_item) {
            $order_item_data = json_decode($order_item->order_data);
            if (!empty($order_item_data)) {
              if (!empty($order_item_data->question_group)) {
                $question_group_answers = (array)$order_item_data->question_group;
                $question_group_keys = (array)$question_group_answers[0];
                $order_data_titles = array_keys($question_group_keys);
                $order_data_titles = array_map(function ($t) {
                  return Inflector::humanize($t);
                }, $order_data_titles);
                $order_data_titles = array_combine(array_keys($question_group_keys), $order_data_titles);
                $attraction_ticket_name = $order_item->ticket->attraction->title . '<br>' . $order_item->ticket->name;
                if (!empty($order_item->date_of_visit)) {
                    $attraction_ticket_name .= '<br>' . $order_item->date_of_visit->format('d M Y');
                }
                if (!empty($order_item->time_of_visit)) {
                    $attraction_ticket_name .= '<br>' . $order_item->time_of_visit->format('H:i');
                }
                $question_group_title = '';
                if (!empty($tickets_with_question_group) && !empty($tickets_with_question_group[$order_item->ticket->id])) {
                    $question_group_title = $tickets_with_question_group[$order_item->ticket->id]['title'];
                }
                ?>
                <div class="border-bottom pb-3 mb-3">
                  <div class="form_title>">
                      <h5><?= $attraction_ticket_name ?> </h5>
                      <p><?= $question_group_title ?></p>
                  </div>
                  <?php foreach ($question_group_answers as $row) { ?>
                    <div class="table-responsive mb-1">
                      <table class="table table-sm">
                        <tbody>
                          <?php foreach ($row as $title => $value) { ?>
                            <tr>
                              <th class="question-group-th"><?= $order_data_titles[$title] ?></th>
                              <td><?= is_array($value) ? implode(', ', $value) : $value ?></td>
                            </tr>
                          <?php } ?>
                      
                        </tbody>
                      </table>
                    
                    </div> 
                  <?php
                  }
                  ?>
                </div>
              <?php
              }
            }
          }
          ?>
          </div>
        </div>
        <?php } ?>
      <?php } ?>
        <div class="card mb-2">
          <div class="card-header bg-primary text-white">
            <div><?= __('Payment Information') ?></div>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-12">
                <?= $this->Form->create($order,['id'=>'confirmation-form','class'=>'form-deferred']) ?>
                <div class="form-group">
                  <?php echo $this->Form->control('pay_by',[
                    'options'=>$payment_methods,
                    'type'=>'radio',
                    'disabled'=>$auth_user['credits'] < $order->total ? ['credits'] : null,
                    'label'=>__('Pay By *'),
                    'escape'=>false,
                    'templates'=>[
                      'inputContainer'=>'<div class="radio">{{content}}</div>',
                      'radio' => '<input type="radio" name="{{name}}" value="{{value}}" {{attrs}} class="form-check-input">',
                      'radioFormGroup' => '<div class="form-group">{{label}}{{input}}</div>',
                      'radioWrapper' => '<div class="form-check">{{label}}</div>',
                      ]
                    ]) ?>
                </div>

                <div> <?= $this->Form->button(__('Pay Now'),['class'=>'btn btn-primary','id'=>'btn-pay-now','type'=>'button','disabled'=> $order->payment_method == null ? true : false]); ?></div>
                <?= $this->Form->end() ?>
                <div class="spinner-border text-primary form-loader"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-4">
      <div id="tickets-sidebar">
        <?php if (!empty($order->order_items)): ?>
          <?php foreach ($order->order_items as $order_item): ?>
            <div class="card">
              <div class="card-body">
                <div><?php echo $this->Html->link('<div class="font-weight-bold">'.$order_item->ticket->attraction->title .'</div> <div class="text-muted">'. $order_item->ticket->name.'<br><del>RP '.$this->Number->currency($order_item->ticket->price) .'</del></div>',['action'=>'view',$order_item->ticket->attraction_id],['escape'=>false]) ?></div>
                <div><?php echo $this->Number->currency($order_item->price) ?></div>

                  <?php $subtotal += ($order_item->price * $order_item->qty) ?>
                  <div class="row py-2">
                    <div class="col-md-6">
                      <?php if ($order_item->ticket->attraction->pricing_type =='fdate'): ?>
                          <div><?php echo $order_item->date_of_visit->format('d M Y') ?></div>
                      <?php endif ?>
                      <?php if ($order_item->ticket->attraction->pricing_type =='fdatetime'): ?>
                          <div><?php echo $order_item->time_of_visit->format('H:i') ?></div>
                      <?php endif ?>
                    </div>
                    <div class="col-md-2 text-right p-0">
                      <span class="py-1"> &times; <?php echo $order_item->qty ?> </span>
                    </div>
                    <div class="col-md-4 text-right"><?php echo $this->Number->currency($order_item->price * $order_item->qty) ?></div>
                  </div>
              </div>
            </div>
          <?php endforeach; ?>
          <div class="py-4 px-4 footer-total">
            <div class="row">
              <div class="col"><?= __('Subtotal') ?></div><div class="col text-right"><?= $this->Number->currency($subtotal) ?></div>
            </div>
            <div class="row grandtotal-row">
              <div class="col"><?= __('Grand Total') ?></div><div class="col text-right"><?= $this->Number->currency($subtotal + $other_fees) ?></div>
            </div>
          </div>
        <?php endif ?>
        </div>
        <?php $this->Html->scriptStart(['block' => true]) ?>
            $(window).on('scroll', function (event) {
              var scrollValue = $(window).scrollTop();
              if (scrollValue > 120) {
                  if (!$('#tickets-sidebar').hasClass('sticky-top')) {
                    $('#tickets-sidebar').addClass('sticky-top');
                  }
              } else{
                  $('#tickets-sidebar').removeClass('sticky-top');
              }
            });
        <?php $this->Html->scriptEnd() ?>
    </div>
  </div>
<?= $this->Html->css(['bootstrap-datepicker3.standalone.min','checkout-style'],['block'=>'internal_css']); ?>
<?= $this->Html->script(['bootstrap-datepicker.min'],['block'=>'scriptBottom']) ?>
<?php $this->Html->scriptStart(['block' => true]) ?>
    var subtotal = <?= $subtotal ?>;
    var other_fees = <?= $other_fees ?>;
    var other_payment_methods = <?= json_encode(array_keys($other_payment_methods)) ?>;
    var markups = <?= json_encode($markups) ?>;
    var payment_providers = <?= json_encode($payment_providers) ?>;
    var check_status_count = 0;
    var LONG_WAITING = 5;
    var MAX_CHECK = 120; /* check every 5 secs in 10 minutes */
    function showProcessing(el) {
      $(el).after('<div class="spinner-border" role="status"><span class="sr-only"><?= __('Processing') ?>...</span></div>');
      $(el).hide();
    }

    function roundUp(value, precision) {
      var pow = Math.pow(10, precision);
        return Math.ceil(pow * value) / pow;
    }

    function hideProcessing(el) {
      $(".spinner-border").remove();
      $(el).show();
    }

    $('.datepicker').datepicker({format:'d M yyyy'});

    $('input[name="pay_by"]').on('change',function() {
      if ($(this).is(':checked')) {
        $('#btn-pay-now').prop('disabled',false);
        if (markups[$(this).val()]) {
          var markup_value = markups[$(this).val()]['value'];
          var markup_add = markups[$(this).val()]['add'];
          var markup_type = markups[$(this).val()]['type'];
          var provider = payment_providers[$(this).val()] || null;
          $('.convenience-fee-row').remove();
          $('.grandtotal-row .col.text-right').html('$'+subtotal.toFixed(2));
          var convenience_fee = 0;
          var total = subtotal + convenience_fee;
          if (markup_type === 'percentage') {
            if (provider == '2C2P' || provider == 'Paypal') {
              total = roundUp((subtotal + markup_add) * (100 / (100 - markup_value)), 2);
              convenience_fee = roundUp(total - subtotal, 2);
            } else {
              convenience_fee = Math.ceil((subtotal * markup_value)) / 100 + markup_add;
              total = subtotal + convenience_fee;
            }
            
          }
          else {
            convenience_fee = markup_value + markup_add;
            total = subtotal + convenience_fee;
          }
          
          $('.grandtotal-row').before(
            '<div class="row convenience-fee-row">' +
              '<div class="col">Convenience Fee</div>' +
              '<div class="col convenience-fee text-right">$' + convenience_fee.toFixed(2) + '</div>' +
            '</div>');
          $('.grandtotal-row .col.text-right').html('$'+total.toFixed(2));
        }
        else {
          $('.convenience-fee-row').remove();
          $('.grandtotal-row .col.text-right').html('$'+subtotal.toFixed(2));
        }
      }
    });

    function checkStatus() {
      console.log('recheck status');

      check_status_count++;

      $.ajax({
        url: "<?= $this->Url->build(['controller'=>'Orders','action'=>'getStatus']) ?>",
        type: "GET",
        dataType: "JSON"
      }).always(function(res, status, xhr) {
        /*console.log(xhr);*/
        console.log(res);
        if (xhr.status == 200 && res.status == "1000") {
          if (res.data.order && res.data.order.status == 'done') {
            window.location = '<?= $this->Url->build(['controller'=>'Orders','action'=>'thankYou']) ?>';
          } else if (res.data.order && res.data.order.status == 'in_progress') {
            setTimeout(function() {
              checkStatus();
            },5000);
          } else {
            /* 5 minutes */
            if (check_status_count < 60) { 
              setTimeout(function() {
                checkStatus();
              },5000);
            } else {
              $(this).prop('disabled',false);
              $('.proccessing-message').after('<div class="alert alert-warning my-2">Your order is taking longer than expected. We will send you an email when it is ready. For more information, please contact <?= Configure::read('Default.company_contact_no') ?>. Ref: '+ res.data.order.order_ref_id+'</div>').remove();
            }
            
          }
        } else {
          $(this).prop('disabled',false);
          $('.proccessing-message').after('<div class="alert alert-warning my-2">Your order is taking longer than expected. We will send you an email when it is ready. For more information, please contact <?= Configure::read('Default.company_contact_no') ?>. Ref: '+ res.data.order.order_ref_id+'</div>').remove();
        }
      });
    }

    $('#btn-pay-now').one('click',function() {
      var pay_by = $("input[name='pay_by']:checked"). val();
      var main_form = $(this).closest('form');
     
      if (pay_by == 'credits') {
        $(this).prop('disabled',true);
        $(this).after('&nbsp;&nbsp;<div class="alert alert-info my-2 proccessing-message"><span class="spinner-border text-primary" style="width: 20px; height:20px"></span> &nbsp; <?= __('We are processing your order. Please do not navigate away from this page</div>') ?>');
        main_form.submit();
      }
      else if(other_payment_methods.includes(pay_by)){
        main_form.submit();
        $(this).prop('disabled',true);
        /*showProcessing($('#btn-pay-now').first());*/
      }
      else {
        alert('<?= __('Please choose a payment method!') ?>');
      }
    });

<?php $this->Html->scriptEnd() ?>
<?php $this->start('css') ?>
<style type="text/css">
  .radio label {
    display: block
  }
  .grandtotal-row {
    font-size: 18px;
  }
  a:hover {
    text-decoration: none;
  }
</style>
<?php $this->end() ?>
