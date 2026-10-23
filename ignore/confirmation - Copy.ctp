<?php
use Cake\Utility\Inflector;
use Cake\Core\Configure;
$subtotal = 0;
$other_fees = 0;
 ?>
  <div class="row">
    <div class="col">
      <?= $this->Html->link('<i class="fa fa-chevron-left"></i> '.__('Back'),['action'=>'checkout'],['escape'=>false]); ?>
    </div>
  </div>
  <h3>Confirmation</h3>
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
          <div>Customer Information</div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4">
              <?php echo $this->Form->control('tracking_no',['class'=>'form-control','label'=>'Order Tracking Number','type'=>'text','value'=>$checkout_info->tracking_no,'disabled'=>'disabled']) ?>
            </div>
            <div class="col-md-4">
              <?php echo $this->Form->control('first_name',['class'=>'form-control','required'=>'required','label'=>'Customer First Name*','value'=>$checkout_info->first_name,'disabled'=>'disabled']) ?>
            </div>
            <div class="col-md-4">
              <?php echo $this->Form->control('last_name',['class'=>'form-control','required'=>'required','label'=>'Customer Last Name*','value'=>$checkout_info->last_name,'disabled'=>'disabled']) ?>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <?php echo $this->Form->control('nric_passport_no',['class'=>'form-control','required'=>'required','label'=>'NRIC / Passport No.*','value'=>$checkout_info->nric_passport_no,'disabled'=>'disabled']) ?>
            </div>
            <div class="col-md-4">
              <?php echo $this->Form->control('email',['class'=>'form-control','required'=>'required','label'=>'Email*','value'=>$checkout_info->email,'disabled'=>'disabled']) ?>
            </div>
            <div class="col-md-4">
              <?php echo $this->Form->control('mobile',['class'=>'form-control','required'=>'required','label'=>'Mobile*','value'=>$checkout_info->mobile,'disabled'=>'disabled']) ?>
            </div>
          </div>
        </div>
      </div>
    <?php
        }
      }
    ?>
      <?php if (!empty($order->order_items)): ?>
        <?php
        $required_info = [];
        $transfer_details = [];
        $return_transfer_details = [];
        foreach ($order->order_items as $key => $order_item) {
          $order_item_data = json_decode($order_item->order_data);
          if (!empty($order_item_data)) {
            if (!empty($order_item_data->required_info)) {
              $required_info = array_merge($required_info,$order_item_data->required_info);
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
        <?php if (!empty($required_info) || !empty($transfer_details) || !empty($return_transfer_details)): ?>
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
                      <th>Passport Number</th>
                      <th>Passport Expiry</th>
                      <th>First Name</th>
                      <th>Last Name</th>
                      <th>Date of Birth</th>
                      <th>Nationality</th>
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
                    <h6>Transfer Details</h6>
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
                    <h6>Return Transfer Details</h6>
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
        <?php endif ?>
      <?php endif ?>
        <div class="card mb-2">
          <div class="card-header bg-primary text-white">
            <div>Payment Information</div>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-12">
                <?= $this->Form->create($order,['id'=>'confirmation-form','class'=>'form-deferred']) ?>
                <div class="form-group">
                  <?php echo $this->Form->control('pay_by',['options'=>['credits'=>'Credits (Balance: S'.$this->Number->currency($auth_user['credits']) .') '.$this->Html->link('Topup',['controller'=>'AgentTopups','action'=>'add','?'=>['redirect'=>'orders/confirmation']]),'credit_card'=>'Credit Card'],'type'=>'radio','disabled'=>$auth_user['credits'] < $order->total ? ['credits'] : null,'label'=>'Pay By *','escape'=>false,'templates'=>['inputContainer'=>'<div class="radio">{{content}}</div>']]) ?>
                </div>

                <div> <?= $this->Form->button('Pay Now',['class'=>'btn btn-primary','id'=>'btn-pay-now','type'=>'button','disabled'=> $order->payment_method == null ? true : false]); ?></div>
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
                <div><?php echo $this->Html->link('<div class="font-weight-bold">'.$order_item->ticket->attraction->title .'</div> <div class="text-muted">'. $order_item->ticket->name.'<br><del>RP S'.$this->Number->currency($order_item->ticket->price) .'</del></div>',['action'=>'view',$order_item->ticket->attraction_id],['escape'=>false]) ?></div>
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
              <div class="col">Subtotal</div><div class="col text-right"><?= $this->Number->currency($subtotal) ?></div>
            </div>
            <div class="row grandtotal-row">
              <div class="col">Grand Total</div><div class="col text-right"><?= $this->Number->currency($subtotal + $other_fees) ?></div>
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
    function showProcessing(el) {
      $(el).after('<div class="spinner-border" role="status"><span class="sr-only">Processing...</span></div>');
      $(el).hide();
    }

    function hideProcessing(el) {
      $(".spinner-border").remove();
      $(el).show();
    }

    $('.datepicker').datepicker({format:'d M yyyy'});

    $('input[name="pay_by"]').on('change',function() {
      if ($(this).is(':checked')) {
        $('#btn-pay-now').prop('disabled',false);
        if ($(this).val() == 'credit_card') {
          var convenience_fee = Math.ceil((subtotal * 3.5) +100)/100;
          var total = subtotal + convenience_fee;
          $('.grandtotal-row').before('<div class="row convenience-fee-row"><div class="col">Convenience Fee</div><div class="col convenience-fee text-right">$'+convenience_fee.toFixed(2)+'</div></div>');
          $('.grandtotal-row .col.text-right').html('$'+total.toFixed(2));
        }
        else {
          $('.convenience-fee-row').remove();
          $('.grandtotal-row .col.text-right').html('$'+subtotal.toFixed(2));
        }
      }
    })

    $('#btn-pay-now').on('click',function() {
      var pay_by = $("input[name='pay_by']:checked"). val();
      var main_form = $(this).closest('form');
      //main_form.submit();
      if (pay_by == 'credits') {
        main_form.submit();
        $(this).prop('disabled',true);
        $(this).after('&nbsp;&nbsp;<div class="spinner-border text-primary form-loader"></div>');
      }
      else if(pay_by == 'credit_card'){
        main_form.submit();
        $(this).prop('disabled',true);
        /*showProcessing($('#btn-pay-now').first());*/
      }
      else {
        alert('Please choose a payment method!');
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
