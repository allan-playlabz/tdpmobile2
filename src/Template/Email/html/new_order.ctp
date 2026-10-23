<?php 
use Cake\Utility\Inflector; 
$order_total = 0;
$email_from = !empty($data['email_from']) ? $data['email_from'] : \Cake\Core\Configure::read('Default.site_name');
$base = \Cake\Core\Configure::read('AWSPublic.public_host');
?>
<h4>Dear <?php echo $data['checkout_info']['first_name'] .' ' .$data['checkout_info']['last_name'] ?>,</h4>
<p>Thank you for your purchase at <?= $email_from ?>, Please find the attached voucher(s).</p>
<p>The following information is about your order details.</p>
<table width="100%" cellpadding="3">
	<tr>
		<td style="font-weight:bold">Order Date/Time:</td>
		<td><?= $data['order']->created->format(' Y-m-d H:i:s') ?></td>
	</tr>
	<tr>
		<td style="font-weight:bold">Name:</td>
		<td> <?php echo $data['checkout_info']['first_name'] .' ' .$data['checkout_info']['last_name'] ?></td>
	</tr>
	<tr>
		<td style="font-weight:bold">IC/ Passport No.:</td>
		<td> <?php echo $data['checkout_info']['nric_passport_no'] ?></td>
	</tr>
	<tr>
		<td style="font-weight:bold">Email:</td>
		<td> <?php echo $data['checkout_info']['email'] ?></td>
	</tr>
	<tr>
		<td style="font-weight:bold">Mobile:</td>
		<td> <?php echo $data['checkout_info']['mobile'] ?></td>
	</tr>
</table>
<br>
<hr>
<br>
<div>Order Reference No: <?= $data['order']->order_ref_id ?></div>
<br>
<div>
	<?php if (!empty($data['order']->order_items)){
		foreach ($data['order']->order_items as $key => $order_item) {
			$order_total += $order_item->ticket->price * $order_item->qty;
	?>
		<div style="padding-top: 5px;padding-bottom: 5px; display: flex;flex-direction: row">
			<?php echo !empty($order_item->ticket->attraction->featured_image) ? $this->Html->image($base.$order_item->ticket->attraction->featured_image,['style'=>'width:120px;vartical-align:top;float:left']) : ''?>
			<div style="float:left;padding-left: 10px">
				<div style="font-weight:bold"><?php echo $order_item->ticket->attraction->title ?></div>
				<div><?php echo $order_item->ticket->attraction->title_chinese ?></div>
				<div><?php echo $order_item->ticket->name ?></div>
				<div>Valid From <?php echo $order_item->ticket->redemption_start_date->format('Y M d') ?> To <?php echo $order_item->ticket->redemption_end_date->format('Y M d') ?></div>
				<div>Quantity: <?php echo $order_item->qty ?></div>
				<?php if (!$order_item->ticket->instant_confirmation): ?>
					<div style="color:#db2531">Pending Confirmation</div>
				<?php endif ?>
			</div>
			
		</div>
	<?php
		}
	} ?>
</div>
<br>
<br>
<p>Auto-generated email by <?= $email_from ?> <br>
<b>DO NOT REPLY</b></p>
</p>