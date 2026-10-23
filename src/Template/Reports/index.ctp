<div class="row">
  <div class="col px-4 text-right">
    <?= $this->Html->link('Download Generated Reports', ['controller' => 'Reports', 'action' => 'reports']) ?>
  </div>
</div>
<h3><?= __('Sales Reports') ?></h3>
<div class="row">
    <div class="col-md-6">
        <?php
        echo $this->Form->create();
        echo $this->Form->control('issue_start_date',['type'=>'text','class'=>'datepicker form-control','autocomplete'=>'off', 'label' => __('Issue Start Date'), 'default'=> $this->request->getData('issue_start_date') ? :date('01 M Y')]);
        echo $this->Form->control('issue_end_date',['type'=>'text','class'=>'datepicker form-control','autocomplete'=>'off', 'label' => __('Issue End Date'), 'default'=> $this->request->getData('issue_start_date')]);
        ?>

        <?php
        echo $this->Form->button(__('Generate Report'),['class'=>'btn btn-primary']);
        echo $this->Form->end();
        ?>
    </div>
</div>
<?php if (!empty($orders)) { 
  $report_headers = [
  __('No'),
  __('Date Issued'),
  __('Time Issued'),
  __('Rdemption Start Date'),
  __('Rdemption End Date'),
  __('Reseller Name'),
  __('Reseller Email'),
  __('Order Tracking Number'),
  __('Order Reference'),
  __('Platform'),
  __('Confirmation No.'),
  __('Merchant'),
  __('Attraction Name'),
  __('Product Type'),
  __('Package Name'),
  __('Ticket Type'),
  __('Customer Name'),
  __('Payment Method'),
  __('Sales Price'),
  __('Quantity Issued'),
  __('Total Sales Price')
];
  ?>
<div class="row">
    <div class="col">
        <table class="table table-striped">
            <thead>
                <tr>
                    <?php foreach ($report_headers as $key => $value): ?>
                      <th><?= $value ?></th>
                    <?php endforeach ?>
                </tr>
            </thead>
            <tbody>
              <?php 
              $no = 1;
              foreach ($orders as $key => $order) {
                foreach ($order->order_items as $key => $order_item) {
                  foreach ($order_item->vouchers as $key => $voucher) {
                    $code = null;
                    ?>
                    $record = [];
                    <td><?= $no++; ?></td>
                    <td><?= $order->created->format('m-d-Y'); ?></td>
                    <td><?= $order->created->format('H:i:s'); ?></td>
                    <td><?= $order_item->ticket->redemption_start_date ? $order_item->ticket->redemption_start_date->format('m-d-Y H:i ?>:s'):''; ?></td>
                    <td><?= $order_item->ticket->redemption_end_date ? $order_item->ticket->redemption_end_date->format('m-d-Y H:i:s') :'';?></td>
                    <td><?= $agent->first_name. ' '.$agent->last_name; ?></td>
                    <td><?= $agent->email; ?></td>
                    <td><?= $order->tracking_no; ?></td>
                    <td><?= $order->order_ref_id; ?></td>
                    <td><?= 'B2B'; ?></td>
                    <td><?= $voucher->code_id; ?></td>
                    <td><?= $order_item->ticket->attraction->merchant->name; ?></td>
                    if ($order_item->ticket->attraction->type == 'single') {
                      <td><?= $order_item->ticket->attraction->title; ?></td>
                      <td><?= __('Attraction') ?></td>
                    } else {
                      <td></td>
                      if ($order_item->ticket->attraction->type == 'package') {
                        <td><?= __('Package') ?></td>
                      } else {
                        <td><?= __('Land Transfer') ?></td>
                      }
                      
                    }
                    if ($order_item->ticket->attraction->type == 'package') {
                      <td><?= $order_item->ticket->attraction->title; ?></td>
                    } else {
                      <td><?= ''; ?></td>
                    }
                    <td><?= $order_item->ticket->name; ?></td>
                    <td><?= !empty($order->customer_name) ? $order->customer_name : '-'; ?></td>
                    <td><?= ucwords($order->payment_method); ?></td>
                    <td><?= $order_item->price; ?></td>
                    <td><?= $quantity_issued = 1; ?></td>
                    <?php
                    if ($voucher->type == 'generated') {
                      $gc = $this->GeneratedCodes->find()->where(['code'=>$voucher->code_id])->first();
                      if (!empty($gc)) { ?>
                        <td><?= $quantity_issued = $gc->redeemable; ?></td>
                    <?php
                      }
                    }
                    ?>
                    <td><?= $quantity_issued; ?></td>
                    <td><?= $record['sales_price'] * $quantity_issued; ?></td>
              <?php
                  }
                }
              }
              ?>
            </tbody>
        </table>
    </div>
</div>
<?php } ?>
<?= $this->Html->css(['bootstrap-datepicker3.standalone.min'],['block'=>'internal_css']); ?>
<?= $this->Html->script(['bootstrap-datepicker.min'],['block'=>'scriptBottom']) ?>
<?php $this->Html->scriptStart(['block' => true]) ?>
$(document).ready(function() {

  $('.datepicker').datepicker({format:'d M yyyy',endDate:'today','autoclose':true});
});
<?php $this->Html->scriptEnd() ?>
