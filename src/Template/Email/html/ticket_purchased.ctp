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
<h3>Ticket(s) purchased!</h3>
<p>Someone ordered <?= count($data['tickets']) ?> ticket(s) for <?= $data['attraction']->title ?>.</p>
<div>
	<h4>Order Information</h4>
  <table class="table" border="1">
    <tr>
      <td>Order Ref ID</td>
      <td><?= $data['order']->order_ref_id ?></td>
    </tr>
    <tr>
      <td>Payment Method</td>
      <td><?= Inflector::humanize($data['order']->payment_method) ?></td>
    </tr>
  </table>
  <br>
	<h4>Ticket(s) Information</h4>
	<?php if (!empty($data['tickets'])): ?>
		<table style="width: 100%; border-collapse: collapse;" border="1">
			<thead>
				<tr>
					<th>SKU ID</th>
					<th>Name</th>
					<th>Quantity</th>
					<th>Date/Time</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($data['tickets'] as $key => $order_item): ?>
					<tr>
						<td><?php echo $order_item->ticket->sku_id ?></td>
						<td><?php echo $order_item->ticket->name ?>
            <?php if (!$order_item->ticket->instant_confirmation): ?>
              <br><div style="color:#db2531">Pending Confirmation</div>
            <?php endif ?><br><del>RP S<?php echo $this->Number->currency($order_item->ticket->price) ?></del></td>
						<td><?php echo $order_item->qty ?></td>
						<td><?php
							if ($order_item->ticket->attraction->pricing_type != 'settlement') {
								if ($order_item->ticket->attraction->pricing_type == 'fdate') {
                  echo $order_item->date_of_visit->format('d M Y');
                }
                else {
                  echo $order_item->date_of_visit->format('d M Y') . ' ' . $order_item->time_of_visit->format('h:ia');
                }
							}
              else {
                echo 'open';
              }
						?></td>
					</tr>
				<?php endforeach ?>
			</tbody>
		</table>
		
	<?php endif ?>
	<?php
    $required_info = [];
    $transfer_details = [];
    $return_transfer_details = [];
    foreach ($data['tickets'] as $key => $order_item) {
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
    
    <?php if (!empty($required_info)) { ?>
      <h4>Travellers</h4>
      <table class="table" border="1">
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
        <h4>Transfer Details</h4>
        <table class="table" border="1">
        <?php foreach ($transfer_details as $key => $value): ?>
          <tr>
            <td><?= Inflector::humanize($key) ?></td>
            <td><?= $value ?></td>
          </tr>
        <?php endforeach ?>
        </table>
    <?php endif ?>
    <?php if (!empty($return_transfer_details)): ?>
        <h4>Return Transfer Details</h4>
        <table class="table" border="1">
        <?php foreach ($return_transfer_details as $key => $value): ?>
          <tr>
            <td><?= Inflector::humanize($key) ?></td>
            <td><?= $value ?></td>
          </tr>
        <?php endforeach ?>
        </table>
    <?php endif ?>
              
</div>
<p>Auto-generated email by <?= \Cake\Core\Configure::read('Default.site_name') ?> <br>
<b>DO NOT REPLY</b></p>
</p>