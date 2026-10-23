<p>Greetings admin,	</p>
 <p>We found an issue verifying payment for and order at <?= \Cake\Core\Configure::read('Default.site_name') ?>.</p>
<p>Below are the details.</p>
<table border="1" style="border-collapse: collapse;">
	<tr>
		<td>Code</td>
		<td><?= $data['code'] ?></td>
	</tr>
	<tr>
		<td>Description</td>
		<td><?= $data['description'] ?></td>
	</tr>
    <tr>
		<td>Message</td>
		<td><?= $data['message'] ?></td>
	</tr>
	<tr>
		<td>Data</td>
		<td><pre><?= json_encode($data['data'], JSON_PRETTY_PRINT) ?></pre></td>
	</tr>
</table>
<br>
<p>This is an auto generated email. Please do not reply.</p>