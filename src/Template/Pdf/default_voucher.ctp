<?php 
use Cake\Utility\Inflector;
use Cake\I18n\Time;
$order->checkout_info = is_array($order->checkout_info) ? $order->checkout_info : json_decode($order->checkout_info,true);
 ?>
<style type="text/css">
	.text-center {
		text-align: center;
	}
	.text-right {
		text-align: right;
	}
	.section-header {
		background-color: #666661; 
		color: #ffffff;
		font-weight: bold;
		padding: 5px 3px;
		margin-top: 15px;
		text-transform: uppercase;
	}
	html,body {
		width: 100%;
		margin:0;
		padding: 7px;
		font-size: 10px;
	}
	h3, .no-padding  {
		padding: 0;
		margin: 0;
	}
</style>
	<div style="float:right;">
		<?= $this->Html->image($this->Url->build('/',['fullBase'=>true]).'img/sgattractions.png',['style'=>'max-width:200px']); ?>
	</div>
	<div style="clear: both;"></div>

<table style="margin: 15px auto;" width="100%">
	<tbody>
		<tr>
			<td style="width: 50%;">
				<div style="clear: both;float: none">
					<div>
						<b>Order Ref.: </b> <?= $order->order_ref_id ?> <br>
						<b>Customer Name: </b> <?= $order->customer_name ?> <br>

						<b>Contact Number: </b> <?= !empty($order->checkout_info['mobile']) ? $order->checkout_info['mobile']:'' ?> <br>
					</div>
				</div>
				
			</td>
			<td style="width: 50%; vertical-align: top;">
				<div class="text-right">
					<b>Created On: </b> <?= date('Y M d, h:ia') ?> <br>
					<b>Payment Method: </b> <?= Inflector::humanize($order->payment_method) ?> <br>
				</div>
			</td>
		</tr>
	</tbody>
</table>
<hr>
<table style="margin: 15px auto 0;" width="100%">
	<tbody>
		<tr>
			<td style="width: 20%;" class="text-center">
				Quantity
			</td>
			<td style="width: 60%; vertical-align: top;" class="text-center">
				Confirmation Code
			</td>
			<td class="text-center">
				Product Type
			</td>
		</tr>
		<tr>
			<td style="width: 20%;" class="text-center">
				<?= !empty($voucher->generated_code) && !empty($voucher->generated_code->redeemable) ? $voucher->generated_code->redeemable : 1 ?>				
			</td>
			<td style="width: 60%; vertical-align: top;" class="text-center">
				<p style="text-align: center; padding: 0"><?= $barcode ?>
					<div style="text-align: center;"><?= $voucher->code_id ?></div>
				</p>
			</td>
			<td class="text-center">
				<?= $ticket_name ?>
			</td>
		</tr>
	</tbody>
</table>
<?php if ($attraction_type == 'transfer'): ?>
	<div>
		<?php
		$order_item_data = json_decode($order_item->travellers_data);
        $transfer_details = [];
        $return_transfer_details = [];
        if (!empty($order_item_data)) {
          if (!empty($order_item_data->transfer_details)) {
            $transfer_details = $order_item_data->transfer_details;
          }
          if (!empty($order_item_data->return_transfer_details)) {
            $return_transfer_details = $order_item_data->return_transfer_details;
          }
        }
        ?>
		<div class="section-header">Trip Details</div>
		<table width="100%">
			<tr>
				<th style="width: 40%">Route</th>
				<th>Pick-up Date & Time</th>
				<th>Transporation</th>
			</tr>
			
	        <?php if (!empty($transfer_details)): ?>
	        	<tr>
					<td>
						<?= !empty($transfer_details->pickup_location) ? 'From '.$transfer_details->pickup_location : '' ?> <br>
						<?= !empty($transfer_details->destination_location) ? 'To '.$transfer_details->destination_location : '' ?> <br>
					</td>
					<td>
						<?php 
						$datetime = '';
						if (!empty($transfer_details->pickup_date) && !empty($transfer_details->pickup_time)) {
						 	$datetime = Time::createFromFormat('d M Y H:i',$transfer_details->pickup_date. ' ' . $transfer_details->pickup_time);
						}
						elseif (!empty($transfer_details->pickup_date)) {
							$datetime = Time::createFromFormat('d M Y',$transfer_details->pickup_date);
						}
						if (is_object($datetime)) {
							echo $datetime->format('F d, Y h:i A');
						}
						?>
					</td>
					<td>
						SG Attractions
					</td>
				</tr>
	        <?php endif ?>
			
		</table>
		<?php if (!empty($return_transfer_details)): ?>
		<div class="section-header">Return Trip Details</div>
		<table width="100%">
			<tr>
				<th style="width: 40%">Route</th>
				<th>Pick-up Date & Time</th>
				<th>Transporation</th>
			</tr>
			
	        <?php if (!empty($return_transfer_details)): ?>
	        	<tr>
					<td>
						<?= !empty($return_transfer_details->pickup_location) ? 'From '.$return_transfer_details->pickup_location : '' ?> <br>
						<?= !empty($return_transfer_details->destination_location) ? 'To '.$return_transfer_details->destination_location : '' ?> <br>
					</td>
					<td>
						<?php 
						$datetime = '';
						if (!empty($return_transfer_details->pickup_date) && !empty($return_transfer_details->pickup_time)) {
						 	$datetime = Time::createFromFormat('d M Y H:i',$return_transfer_details->pickup_date. ' ' . $return_transfer_details->pickup_time);
						}
						elseif (!empty($return_transfer_details->pickup_date)) {
							$datetime = Time::createFromFormat('d M Y',$return_transfer_details->pickup_date);
						}
						if (is_object($datetime)) {
							echo $datetime->format('F d, Y h:i A');
						}
						?>
					</td>
					<td>
						<?= $ticket_name ?>
					</td>
				</tr>
	        <?php endif ?>
			
		</table>
		<?php endif ?>
	</div>
<?php endif ?>
<div>
	<div class="section-header" style="margin-top: 5px;">TITLE AND DESCRIPTION</div>
	<h3>
		<?= $attraction_title ?><br> 
		<?= $attraction_title_chinese ?>
	</h3>
	<br>
	<div style="font-size: 12px"><?= $ticket_name ?></div>
	<div class="no-padding"><?= $ticket_description ?></div>
</div>

<div>
	<div class="section-header">TERMS and CONDITIONS</div>
	<div class="no-padding"><?= $ticket_terms_and_conditions ?></div>
</div>

<div>
	<div class="section-header">VOUCHER VALIDITY</div>
	<p><?= !empty($ticket_redemption_end_date) ? ($ticket_redemption_start_date->format('F d, Y') .' To ' .$ticket_redemption_end_date->format('F d, Y')) : $ticket_redemption_start_date ?></p>
	<p><?= !empty($flexi_ticket_validity) ? $flexi_ticket_validity : '' ?></p>
</div>
<div style="border-top:1px solid #ccc; border-bottom: 1px solid #ccc;padding: 10px 0;margin: 10px 0;">
	<p><b>Powered By:</b> SG Attractions <br>
		You may contact us at <b>+65 88582328</b> (call Viber or Whatsapp)</p>
</div>
<div>
	<p> <?= !empty($ticket_voucher_note) ? '<b>Note:</b> '.$ticket_voucher_note : '' ?></p>
</div>
<p style="text-align: left;">&nbsp;</p>