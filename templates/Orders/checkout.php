<?php
use Cake\Core\Configure;
$subtotal = 0;
$other_fees = 0;
$has_question_group = false;
if (!empty($agent)) {
  $agent_name = explode(' ',$agent['name']);
  if (!empty($agent_name)) {
    $agent['last_name'] = array_pop($agent_name);
    $agent['first_name'] = implode(' ',$agent_name);
  }
}

 ?>
<?= $this->Form->create($order,['class'=>'form-deferred']) ?>
  <div class="row">
    <div class="col">
      <?= $this->Html->link('<i class="fa fa-chevron-left"></i> '.__('Back'),['action'=>'cart'],['escape'=>false]); ?>
    </div>
  </div>

  <!-- <div class="row steps-wrapper">
    <div class="col">
      <ul class="steps-progress">
            <li class="active">Checkout</li>
            <li>Customer Details</li>
            <li>Payment</li>
      </ul>
    </div>
  </div> -->

  <div class="mt-3 row">
    <div class="col-md-6 col-lg-8">
      <div class="card mb-2">
        <div class="card-header bg-primary text-white">
          <div><?= __('1. Customer Details') ?></div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4">
              <?php echo $this->Form->control('tracking_no',['class'=>'form-control','label'=>__('Order Tracking Number'),'type'=>'text','placeholder'=>'Unique or Empty']) ?>
            </div>
            <div class="col-md-4">
              <?php echo $this->Form->control('first_name',['class'=>'form-control','required'=>'required','maxlength'=>50, 'pattern' => '[a-zA-Z0-9\-_\s]+', 'label'=>__('First Name (English only)*'),'default'=>!empty($agent['first_name']) ? $agent['first_name'] :'']) ?>
            </div>
            <div class="col-md-4">
              <?php echo $this->Form->control('last_name',['class'=>'form-control','required'=>'required','maxlength'=>50, 'pattern' => '[a-zA-Z0-9\-_\s]+', 'label'=>__('Last Name (English only)*'),'default'=>!empty($agent['last_name']) ? $agent['last_name'] :'']) ?>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <?php echo $this->Form->control('email',['class'=>'form-control','required'=>'required','label'=>__('Email*'),'default'=>!empty($agent['email']) ? $agent['email'] :'']) ?>
            </div>
            <div class="col-md-4">
              <?php echo $this->Form->control('mobile',['class'=>'form-control','required'=>'required','label'=>__('Mobile * (e.g. +6512345678)'),'default'=> !empty($agent['contact_no']) ? $agent['contact_no'] :'', 'pattern' => '[+]*[0-9]+', 'title' => '+ and numbers only']) ?>
            </div>
          </div>
        </div>
      </div>
      <?php if (!empty($tickets)): ?>
      <div class="card mb-2 order-info-card">
        <div class="card-header bg-primary text-white">
          <div><?= __('2. Order Information') ?></div>
        </div>
        <div class="card-body">
            <?php $i = 1;
            $has_required_info = false; ?>


              <?php foreach ($tickets as $key => $ticket): ?>
                <?php if ($ticket->require_travellers_info || $ticket->attraction->type =='transfer' || $ticket->multiple_redemption): ?>
                  <?php $has_required_info = true; ?>
                <div class="ticket">
                  <?php if (($ticket->require_travellers_info || ($ticket->multiple_redemption && $ticket->type == 'normal')) && !empty($ticket_list[$ticket->id])): ?>
                    <div class="my-2 p-4 border bg-eee">
                    <?php foreach ($ticket_list[$ticket->id] as $date => $qty): ?>
                      <h5><div class="font-weight-bold"><?= $ticket->attraction->title ?></div>
                      <div class="text-muted"><?= $ticket->name ?><?= $ticket->attraction->type =='transfer' ? ' - '.Configure::read('Default.transfer_subtypes.'.$ticket->attraction->subtype) : '' ?> <?= $ticket->attraction->pricing_type != 'settlement' ? '('.$date.')' : '' ?></div></h5>
                      <?php if ($ticket->multiple_redemption && $ticket->type == 'normal'){ ?>
                        <div class="row">
                          <?php if (in_array($ticket->sku_id, Configure::read('ForceSingleCode.skus'))) { ?>
                            <div class="d-none">
                            <?= $this->Form->control('multiple_redemption.'.$ticket->id.'.'.$date,['label'=>__('Redeem as single code'),'type'=>'checkbox', 'checked' => true]); ?>
                            </div>
                            <div class="pl-3">Redeem as single code</div>
                          <?php } else { ?>
                            <?= $this->Form->control('multiple_redemption.'.$ticket->id.'.'.$date,['label'=>__('Redeem as single code'),'type'=>'checkbox']); ?>
                          <?php } ?>
                        </div>
                      <?php } ?>
                      <?php if ($ticket->require_travellers_info){ ?>
                        <?php for ($i=0; $i < $qty; $i++) {  ?>
                          <label>Traveller <?= $i+1 ?></label>
                          <div class="row mb-2">
                            <div class="col-md">
                              <?php echo $this->Form->control('passport_number.'.$ticket->id.'.'.$date.'.'.$i,['class'=>'form-control','label'=>__('Passport Number *'),'required'=>'required']) ?>
                            </div>
                            <div class="col-md">
                              <?php echo $this->Form->control('passport_expiry.'.$ticket->id.'.'.$date.'.'.$i,['class'=>'form-control passport-expiry','label'=>__('Passport Expiry *'),'required'=>'required','autocomplete'=>'off']) ?>
                            </div>
                          </div>
                          <div class="row mb-2">
                            <div class="col-md">
                              <?php echo $this->Form->control('passport_first_name.'.$ticket->id.'.'.$date.'.'.$i,['class'=>'form-control','label'=>__('Passport First Name *'),'required'=>'required']) ?>
                            </div>
                            <div class="col-md">
                              <?php echo $this->Form->control('passport_last_name.'.$ticket->id.'.'.$date.'.'.$i,['class'=>'form-control','label'=>__('Passport Last Name *'),'required'=>'required']) ?>
                            </div>
                          </div>
                          <div class="row mb-2">
                            <div class="col-md">
                              <?php echo $this->Form->control('date_of_birth.'.$ticket->id.'.'.$date.'.'.$i,['class'=>'form-control dob','label'=>__('Date of Birth *'),'required'=>'required','autocomplete'=>'off']) ?>
                            </div>
                            <div class="col-md">
                              <?php echo $this->Form->control('nationality.'.$ticket->id.'.'.$date.'.'.$i,['class'=>'form-control','label'=>__('Nationality *'),'required'=>'required']) ?>
                            </div>
                          </div>
                          <div class="row mb-2">
                            <div class="col-md">
                              <?php echo $this->Form->control('remarks.'.$ticket->id.'.'.$date.'.'.$i,['class'=>'form-control','label'=>__('Remarks'),'autocomplete'=>'off','type'=>'textarea','rows'=>2]) ?>
                            </div>
                          </div>
                        <?php } ?>
                    <?php } ?>
                    <?php endforeach ?>
                    </div>
                    <?php endif ?>
                    <?php foreach ($ticket_list[$ticket->id] as $date => $qty): ?>
                    <?php if ($ticket->attraction->type == 'transfer') {

                      $extra_fields = Configure::read('Default.land_transfer_details');
                      $no = 0;
                      if (!empty($extra_fields) && is_array($extra_fields)) { ?>
                        <div class="my-2 p-4 border bg-eee">
                        <h5><div class="font-weight-bold"><?= $ticket->attraction->title ?></div>
                            <div class="text-muted"><?= $ticket->name ?><?= $ticket->attraction->type =='transfer' ? ' - '.Configure::read('Default.transfer_subtypes.'.$ticket->attraction->subtype) : '' ?> <?= $ticket->attraction->pricing_type != 'settlement' ? '('.(!empty($date) ? $date :'').')' : '' ?><br>
                                  <del>RP <?= $this->Number->currency($ticket->retail_price) ?></del></div>
                          </h5>
                          <h6 class="border-top py-2 mt-2 font-weight-bold">Transfer Details</h6>
                        <?php
                        foreach ($extra_fields as $extra_field_name => $extra_field) {
                          if ($no%2 == 0) {
                            echo '<div class="row">';
                          }
                          echo '<div class="col-md">';
                          if ($extra_field['type'] == 'date') {
                            echo $this->Form->control($extra_field_name.'.'.$ticket->id,['type'=>'text','class'=>'datepicker form-control','label'=>$extra_field['label'].'*','required'=>'required','autocomplete'=>'off']);
                          }
                          elseif ($extra_field['type'] == 'time') {
                            echo $this->Form->control($extra_field_name.'.'.$ticket->id,['type'=>'time','class'=>'timepicker form-control','required'=>'required','label'=>['text'=>$extra_field['label'].'*','class'=>'d-block'],
                            'hour'=>['class'=>'form-control d-inline w-auto'],'minute'=>['class'=>'form-control d-inline w-auto']]);
                          }
                          else {
                            echo $this->Form->control($extra_field_name.'.'.$ticket->id,['type'=>$extra_field['type'],'required'=>$extra_field_name == 'flight_number' && ($ticket->attraction->subtype == 3 || $ticket->attraction->subtype == 4)  ? false : 'required','class'=>'form-control','label'=>$extra_field['label'].($extra_field_name == 'flight_number' && ($ticket->attraction->subtype ==3 || $ticket->attraction->subtype == 4)  ? false : '*')]);
                          }
                          echo '</div>';
                          if ($no%2 == 1) {
                            echo '</div>';
                          }
                          $no++;
                        }
                        ?>

                        <?php
                        $no = 0;
                        if ($ticket->attraction->subtype == Configure::read('Default.transfer_subtypes.2')) {
                        ?>
                          <h6 class="border-top py-2 mt-2 font-weight-bold">Return Trip</h6>
                          <?php
                          foreach ($extra_fields as $extra_field_name => $extra_field) {
                            if ($no%2 == 0) {
                              echo '<div class="row">';
                            }
                            echo '<div class="col-md">';
                            if ($extra_field['type'] == 'date') {
                              echo $this->Form->control('return_'.$extra_field_name.'.'.$ticket->id,['type'=>'text','required'=>'required','autocomplete'=>'off','class'=>'datepicker form-control','label'=>$extra_field['label'].'*']);
                            }
                            elseif ($extra_field['type'] == 'time') {
                              echo $this->Form->control('return_'.$extra_field_name.'.'.$ticket->id,['type'=>'time','required'=>'required','class'=>'timepicker form-control','label'=>['text'=>$extra_field['label'].'*','class'=>'d-block'],
                              'hour'=>['class'=>'form-control d-inline w-auto'],'minute'=>['class'=>'form-control d-inline w-auto']]);
                            }
                            else {
                              echo $this->Form->control('return_'.$extra_field_name.'.'.$ticket->id,['type'=>$extra_field['type'],'required'=>$extra_field_name == 'flight_number' ? false : 'required','class'=>'form-control','label'=>$extra_field['label'].'*']);
                            }
                            echo '</div>';
                            if ($no%2 == 1) {
                              echo '</div>';
                            }
                            $no++;
                          }
                        }
                        ?>
                        </div>
                      <?php
                      }
                    }
                    ?>
                    <?php endforeach ?>
                  </div>
                  <?php endif ?>
              <?php endforeach ?>
            <?= $has_required_info ? '<div class="has-required-info d-none"></div>' : '' ?>
        </div>
      </div>
      <div class="card mb-2 ticket-details-card">
        <div class="card-header bg-primary text-white">
          <div><?= __('{0}. Ticket Details', $has_required_info ? '3' : '2') ?></div>
        </div>
        <div class="card-body">
            <?php foreach ($tickets as $key => $ticket): ?>
              <div class="ticket-details-container mt-3">
              <?php
                foreach ($ticket_list[$ticket->id] as $date => $qty) {
                  if (!empty($tickets_with_question_group) && !empty($tickets_with_question_group[$ticket->id])) {
                  ?>
                  <div class="border-bottom pb-3 mb-3">
                  <?php
                  for ($i=0; $i < $qty; $i++) {
                      $has_question_group = true;
                        $is_last_row = $i == $qty-1;
                        $this->QuestionGroup->render(
                          $tickets_with_question_group[$ticket->id],
                          $this->Form,
                          $ticket->id . '.' . $date . '.' . $i,
                          $ticket->attraction->title,
                          $ticket->name,
                          $date,
                          $i + 1,                            
                          $this->Form->getTemplates(),
                          $is_last_row
                        );
                        $has_required_info = true;
                    
                    if ($ticket->lead_pax_only) {
                      break;
                    }
                  }
                  ?>
                  </div>
                <?php
                  }
                }
              ?>
              </div>
            <?php endforeach ?>
        </div>
      </div>
    <?php endif ?>
    </div>
    <div class="col-md-6 col-lg-4">
      <?= $this->element('selected_tickets',['subtotal'=>$subtotal,'other_fees'=>$other_fees]) ?>
    </div>

  </div>


  <br>

  <div> <?= $this->Form->button(__('Continue'),['class'=>'btn btn-primary']); ?></div>

<?= $this->Form->end() ?>
<div class="spinner-border text-primary form-loader"></div>
<?= $this->fetch('postLink') ?>
<?= $this->Html->css(['bootstrap-datepicker3.standalone.min','checkout-style'],['block'=>'internal_css']); ?>
<?= $this->Html->script(['bootstrap-datepicker.min'],['block'=>'scriptBottom']) ?>
<?php $this->start('css') ?>
<style type="text/css">
  a:hover {
    text-decoration: none;
  }
  .bg-eee {
    background-color: #eee;
  }
  .card-header.bg-primary {
    font-size: 18px;
  }
  <?php
  if (!$has_required_info) {
     echo '.order-info-card {
      display: none;
    }';
  }
  if (!$has_question_group) {
    echo '.ticket-details-card {
      display: none;
    }';
   } ?>
</style>
<?php $this->end() ?>
<?php $this->Html->scriptStart(['block' => true]) ?>
    var d = new Date();
    d.setMonth(d.getMonth() - 1);

    $('.dob').datepicker({format:'d M yyyy',endDate: d,'autoclose':true});
    $('.date-picker').datepicker({format:'d M yyyy', 'autoclose':true});
    d.setMonth(d.getMonth() + 2)
    $('.passport-expiry').datepicker({format:'d M yyyy',startDate: d,'autoclose':true});
    $('.datepicker').datepicker({format:'d M yyyy',startDate: 'today','autoclose':true});
    $('.timepicker').datepicker({format:'hh mm','autoclose':true});
    if ($('.has-required-info').length == 0) {
      $('.order-info-card').hide();
    }
<?php $this->Html->scriptEnd() ?>
