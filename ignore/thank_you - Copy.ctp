<?php use Cake\Utility\Inflector; ?>
<h2 style="font-size: 32px;text-align: center;">Thank you for your purchase!</h2>

<?php if (!empty($order)): ?>
	<h3>Details</h3>
	<hr>
	<div style="background-color: #d0cafa" class="p-4">
		<div><strong>Transaction ID: <?= $order->id ?></strong></div>
		<div><strong>Order Reference No.: <?= $order->order_ref_id ?></strong></div>
		<div>Created: <?= $order->created->format('d M Y, h:ia') ?></div>
		<div>Modified: <?= $order->modified->format('d M Y, h:ia') ?></div>
		<br>
		<div>Reseller: <?= $order->agent_user->agent->first_name . ' '.  $order->agent_user->agent->last_name ?></div>
		<div>Reseller Reference No.: <?= $order->tracking_no ?></div>
		<div>Status: <?= $order->status=='done' ? '<span class="badge badge-success">'.ucfirst($order->status).'</span>' : '<span class="badge badge-secondary">'.ucfirst($order->status).'</span>' ?></div>

	</div>
	<br>
	<?php if (!empty($order->checkout_info)): ?>
    <?php
    $checkout_info = json_decode($order->checkout_info);
    ?>
    <?php if (!empty($checkout_info)): ?>
      <h3>Customer Information</h3>
      <hr>
      <table class="">
      <?php foreach ($checkout_info as $key => $value): ?>
        <tr>
          <td class="font-weight-bold"><?= Inflector::humanize($key) ?></td>
          <td><?= $value ?></td>
        </tr>
      <?php endforeach ?>
      </table>
    <?php endif ?>

  <?php endif ?>
  <br>
  <h3>Payment Information</h3>
	<hr>
	<table class="">
    <tr>
      <td class="font-weight-bold">Subtotal</td>
      <td class="text-right">S<?= $this->Number->currency($order->subtotal) ?></td>
    </tr>
    <tr>
      <td class="font-weight-bold">Convenience Fee</td>
      <td class="text-right">S<?= $this->Number->currency($order->convinience_fee) ?></td>
    </tr>
    <tr>
      <td class="font-weight-bold">Total</td>
      <td class="text-right">S<?= $this->Number->currency($order->total) ?></td>
    </tr>
  </table>
  <br>
	<h5>Ticket Info <small><?= $this->Html->link('<i class="fa fa-download"></i> Download All', ['controller' => 'Vouchers', 'action' => 'downloadAll', $order->id],['class'=>'','escape'=>false]) ?></small>  </h5>
	<?php $subtotal = 0; ?>
	    <?php if (!empty($order->order_items)): ?>
	    	<table class="table table-striped">
	    		<thead>
	    			<tr class="bg-dark text-white">
	    				<th>Ticket ID</th>
	    				<th>Confirmation Code</th>
	    				<th>Purchased Item</th>
	    				<th>Ticket Name & Type</th>
	    				<th>Qty</th>
	    				<th>Price</th>
	    				<th>Total</th>
	    				<th>Status</th>
	    			</tr>
	    		</thead>
	    		<tbody>
			      <?php foreach ($order->order_items as $oi_key => $order_item): ?>
			      	<tr>
		    				<td><?= $order_item->ticket->sku_id ?></td>
		    				<td>
		    					<?php if ($order_item->ticket->single_pdf){ ?>
		    						<?php for ($oi_qty=0; $oi_qty < $order_item->qty ; $oi_qty++) { ?>
		    							<div><a href="<?= $this->Url->build(['controller'=>'Vouchers','action'=>'sview',$order_item->id.'_'.$oi_key]) ?>" target="_blank">
		    							<?php 
		    							 if (!empty($order_item->vouchers)) {
		    							 	foreach ($order_item->vouchers as $key => $voucher) {
		    							 		echo $voucher->code_id .' ';
		    							 	}
		    							 } ?>
		    							</a></div>
		    						<?php } ?>
		    					<?php } elseif (!empty($order_item->vouchers)) { ?>
						      		<?php foreach ($order_item->vouchers as $key => $voucher): ?>
						      			<div><a href="<?= $this->Url->build(['controller'=>'Vouchers','action'=>'view',$voucher->id]) ?>" target="_blank"><?= $voucher->code_id ?></a></div>
						      		<?php endforeach ?>
		    					<?php } /*elseif ($order_item->ticket->api_purchase == true) { 
		    						if (!empty($order_item->api_vouchers)) {
		    							$api_vouchers = json_decode($order_item->api_vouchers);
		    							if (!empty($api_vouchers)) {
		    								foreach ($api_vouchers as $key => $voucher_code) {
		    								?>
		    								<div><a href="<?= $this->Url->build(['controller'=>'Vouchers','action'=>'apiview',$order_item->created->format('Y'),$order_item->created->format('md'),$voucher_code]) ?>" target="_blank"><?= $voucher_code ?></a></div>
		    								<?php
		    								}
		    							}
		    						}
		    					 }*/ ?>
	    					</td>
		    				<td><?= $order_item->ticket->attraction->title ?></td>
		    				<td><?= $order_item->ticket->name ?><br>
                                  <del>RP S<?= $this->Number->currency($order_item->ticket->price) ?></del></td>
		    				<td><?= $order_item->qty ?></td>
		    				<td class="text-right">S<?= $this->Number->currency($order_item->price) ?></td>
		    				<td class="text-right">S<?= $this->Number->currency($order_item->total) ?></td>
		    				<td><span class="badge badge-<?= $order->status == 'done' ? 'success' :'secondary' ?>"><?= ucwords($order->status) ?></span></td>
		    			</tr>


			      <?php endforeach; ?>
	      	</tbody>

			  </table>
	    <?php endif ?>
<?php endif ?>
