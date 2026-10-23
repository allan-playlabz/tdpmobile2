<?php use Cake\Utility\Inflector; ?>
<style type="text/css">
	h4 {
		margin: 15px 0 5px;
	}
	table {
		border-collapse: collapse;
		width: 100%;
	}
</style>
<p>There is an error for this order and cannot get through. Please use the following error number to check <?= $ex_no ?>.</p>
<p>The following information is about the order details.</p>
<table width="100%">
	<tr>
		<td class="font-weight-bold">Order Date/Time:</td>
		<td><?= $data['order']->created->format(' Y-m-d H:i:s') ?></td>
	</tr>
	<tr>
		<td class="font-weight-bold">Order Ref No:</td>
		<td><?= $data['order']->order_ref_id ?></td>
	</tr>
	<tr>
		<td class="font-weight-bold">Purchase at:</td>
		<td><?php echo $this->Url->build('/',['fullBase'=>true]); ?></td>
	</tr>
	<tr>
		<td class="font-weight-bold">Payment Method:</td>
		<td><?php echo Inflector::humanize($data['order']->payment_method) ?></td>
	</tr>
</table>
<div class="font-weight-bold py-2 px-1" style="background-color: #dbd8ce">
	Purchased Item(s)
</div>
<table width="100%" style="border-collapse: collapse;">
	<thead>
	<tr style="background-color:#dfe6f2">
		<th>Item</th>
		<th>Product Type</th>
		<th>Unit Price</th>
		<th>Quantity</th>
		<th>Subtotal</th>
	</tr>
	</thead>
	<tbody>
	<?php if (!empty($data['order']->order_items)){
		foreach ($data['order']->order_items as $key => $order_item) {
			$order_total += $order_item->ticket->price * $order_item->qty;
	?>
		<tr class="border-top-2">
			<td>
				<div class="font-weight-bold"><?php echo $order_item->ticket->attraction->title ?></div>
				<div><?php echo $order_item->ticket->attraction->title_chinese ?></div>
				<div><?php echo $order_item->ticket->name ?></div>
			</td>
			<td>
				<div class="text-center">
					<?php echo ucwords($order_item->ticket->attraction->type) ?>
				</div>
			</td>
			<td>
				<div class="text-right">S<?php echo $this->Number->currency($order_item->ticket->price/*$order_item->price*/) ?></div>
			</td>
			<td>
				<div class="text-right"><?php echo $order_item->qty ?></div>
			</td>
			<td>
				<div class="text-right">S<?php echo $this->Number->currency($order_item->ticket->price * $order_item->qty/*$order_item->total*/) ?></div>
			</td>
		</tr>
	<?php
		}
	} ?>
	</tbody>
</table>
<div style="background-color: #dbd8ce;" class="py-2 px-1">
	<div style="text-align: right">
		<span class="font-weight-bold">Total Paid Amount: </span>
		<span>S<?php echo $this->Number->currency($order_total);//echo $this->Number->currency($data['order']->total) ?></span>
	</div>
</div>