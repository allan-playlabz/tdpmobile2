<?php use Cake\Utility\Inflector; 
use Cake\Core\Configure; ?>

<div class="container py-5">
<div class="text-center">
	<div class="badge badge-success m-auto"><i class="fa fa-check fa-5x"></i></div>
</div>

<h2 style="font-size: 32px;text-align: center;" class="font-weight-bold mb-4"><?= __('Thank you for your purchase!') ?></h2>
<?php if (!empty($order) && $order->status == 'done'): ?>
	<div class="text-center">Click <a href="<?= $this->Url->build(['controller' => 'Orders', 'action' => 'view', $order->order_ref_id]) ?>">here</a> to view order details and download vouchers or <a href="/">create</a> another order.</div>
<?php elseif (!empty($order)): ?>
	<?php if ($order->status == 'failed') { ?>
		<div class="alert alert-danger"><?= __('There is an error processing this order. Please try again or contact admin.') ?></div>
	<?php } else { ?>
		<div class ="alert alert-info processing-message"><span class="spinner-border text-primary" style="width: 20px; height:20px"></span> &nbsp;<?= __('We are processing your order. Please do not navigate away from this page. We will show you the details here once done.') ?></div>
		<?php $this->Html->scriptStart(['block' => true]) ?>
	    var check_status_count = 0;
	    var check_frequency = 1000;
		var checking_status = false;
		var order_ref_id = '<?= $order->order_ref_id ?>';
		var completing_msg = `<?= __('<p>We are completing your order now.</p>
		<p>This may take typically 5-15 minutes. During peak period, it may take up to 30 minutes for the attraction to return with a confirmation.</p>
		<p>You will receive an email with the tickets once completed.</p>
		<p>For assistance on your ticket or if you do not receive the final ticket after 30 minutes, please get in touch with our Customer Service Agent with the following Order Reference: ') ?>${order_ref_id}<?= __('.</p>') ?>`;
	    function showProcessing(el) {
	      $(el).after('<div class="alert alert-info my-2 processing-message"><span class="spinner-border text-primary" style="width: 20px; height:20px"></span> &nbsp; <?= __('We are processing your order. Please do not navigate away from this page</div>') ?>');
	      $(el).hide();
	    }

	    function hideProcessing(el) {
	      $(".processing-message").remove();
	      $(el).show();
	    }


	    function checkStatus() {
		  if (checking_status) {
			return;
		  }
		  checking_status = true;
	      check_status_count++;
		  check_frequency = check_frequency + 100;
	      $.ajax({
	        url: "<?= $this->Url->build(['controller'=>'Orders','action'=>'getStatus']) ?>",
	        type: "GET",
	        dataType: "JSON"
	      }).always(function(res, status, xhr) {
			checking_status = false;
	        if (xhr.status == 200 && res.status == "1000") {
	          if (res.data.order && res.data.order.status == 'done') {
	            window.location = '<?= $this->Url->build(['controller'=>'Orders','action'=>'thankYou']) ?>';
	          }
			  	if (check_status_count > 30) {
					$('.processing-message').html(
						completing_msg
						);
				}
				setTimeout(function() {
	              checkStatus();
	            },check_frequency);
	        } else {
	        	if (xhr.status == 404 || res.status == 404) {
	        		window.location = '<?= $this->Url->build('/') ?>';
	        	} else {
	        		setTimeout(function() {
	              checkStatus();
	            },check_frequency);
	        	}
	        }
	      }).fail(function(xhr) {
	      	if (xhr.status == 403) {
				window.location = '<?= $this->Url->build('/') ?>';
	      	}
	      });
	    }

	    	checkStatus();
	    	showProcessing($('.working-alert').first());
		<?php $this->Html->scriptEnd() ?>
	<?php } ?>
<?php endif ?>
</div>