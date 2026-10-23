
<p>Greetings admin,	</p>
 <p>We found a payment discrepancy in one of the orders for <?= \Cake\Core\Configure::read('Default.site_name') ?>.</p>
<p>Below are the details.</p>
<table border="1" style="border-collapse: collapse;">
	<tr>
		<td>Order Ref</td>
		<td><?= $data['order']->order_ref_id ?></td>
	</tr>
	<tr>
		<td>Total</td>
		<td>S<?= $this->Number->currency($data['order']->total) ?></td>
	</tr>
	<tr>
		<td>Amount Paid</td>
		<td>S<?= $this->Number->currency($data['total_paid']) ?></td>
	</tr>
</table>
<br>
<p>This is an auto generated email. Please do not reply.</p>