<?php

use Cake\I18n\DateTime;
use Cake\Core\Configure;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use Cake\Utility\Inflector;

$subtotal = 0;
$other_fees = 0;
$hide_before_date = new \Cake\I18n\DateTime('-6 months');
$order_created = new \Cake\I18n\DateTime($order['created']);

function getBadgeInfo($voucher)
{
  $bg_color = 'default';
  $status_to_show = '';
  switch ($voucher['status']) {
    case 'purchased':
      $bg_color = 'primary';
      $status_to_show = __('Valid');
      if ($voucher['type'] != 'generated') {
        $status_to_show = __('Purchased');
      }
      break;
    case 'used':
      $bg_color = 'success';
      $status_to_show = __('Redeemed');
      break;
    case 'expired':
      $bg_color = 'warning';
      $status_to_show = __('Expired');
      break;
    case 'partially_redeemed_expired':
      $bg_color = 'warning';
      $status_to_show = __('Partially Redeemed Expired');
      break;
    case 'cancelled':
      $bg_color = 'danger';
      $status_to_show = __('Cancelled');
      break;
    case 'partially_redeemed_cancelled':
      $bg_color = 'danger';
      $status_to_show = '';
      break;
    default:
      //use default
      break;
  }

  return ['bg_color' => $bg_color, 'status_to_show' => $status_to_show];
}
?>

<?php if (!empty($order)) { ?>
  <div class="row">
    <div class="col-md-6 col-lg-8">
      <div class="card mb-3">
        <div class="card-header">
          <h5><?= __('Order Information') ?></h5>
        </div>
        <div class="card-body">
          <table class="table table-borderless">
            <tr>
              <td><?= __('Order Ref') ?></td>
              <td><?= $order['order_ref_id'] ?></td>
            </tr>
            <?php if (!empty($order['tracking_no'])) : ?>
            <tr>
              <td><?= __('Tracking No') ?></td>
              <td><?= h($order['tracking_no']) ?></td>
            </tr>
            <?php endif ?>
            <tr>
              <td><?= __('Payment Method') ?></td>
              <td><?= Inflector::humanize($order['payment_method']) ?></td>
            </tr>
            <tr>
              <td><?= __('Payment Status') ?></td>
              <td><?= Inflector::humanize($order['status']) ?></td>
            </tr>
            <tr>
              <td><?= __('Purchase Date') ?></td>
              <td><?= (new DateTime($order['created']))->format('d M Y h:i a') ?></td>
            </tr>
          </table>
        </div>
      </div>


      <?php if (!empty($order['order_items'])) : ?>
        <?php
        $required_info = [];
        $transfer_details = [];
        $return_transfer_details = [];
        foreach ($order['order_items'] as $key => $order_item) {
          $order_item_data = !is_array($order_item['order_data']) ? json_decode($order_item['order_data']) : $order_item['order_data'];
          if (!empty($order_item_data)) {
            if (!empty($order_item_data->required_info)) {
              $required_info = array_merge($required_info, $order_item_data->required_info);
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
        <?php if (!empty($required_info)) : ?>
          <div class="card mb-3">
            <div class="card-header">
              <h5><?= __('Travellers Information') ?></h5>
            </div>
            <div class="card-body">
              <table class="table table-borderless">
                <tr>
                  <th><?= __('Passport Number') ?></th>
                  <th><?= __('Passport Expiry') ?></th>
                  <th><?= __('First Name') ?></th>
                  <th><?= __('Last Name') ?></th>
                  <th><?= __('Date of Birth') ?></th>
                  <th><?= __('Nationality') ?></th>
                </tr>
                <?php foreach ($required_info as $key => $td) : ?>
                  <tr>
                    <td><?= $td->passport_number ?></td>
                    <td><?= !empty($td->passport_expiry) ? $td->passport_expiry : '' ?></td>
                    <td><?= $td->passport_first_name ?></td>
                    <td><?= $td->passport_last_name ?></td>
                    <td><?= $td->date_of_birth ?></td>
                    <td><?= $td->nationality ?></td>
                  </tr>
                  <?php if (!empty($td->remarks)) : ?>
                    <tr>
                      <td colspan="6"><i><?= $td->remarks ?></i></td>
                    </tr>
                  <?php endif ?>
                <?php endforeach ?>
              </table>
            </div>
          </div>
        <?php endif ?>
        <?php if (!empty($transfer_details)) : ?>
          <div class="card mb-3">
            <div class="card-header">
              <h5><?= __('Transfer Details') ?></h5>
            </div>
            <div class="card-body">
              <table class="table table-borderless">
                <?php foreach ($transfer_details as $key => $value) : ?>
                  <tr>
                    <td><?= Inflector::humanize($key) ?></td>
                    <td><?= $value ?></td>
                  </tr>
                <?php endforeach ?>
              </table>
            </div>
          </div>
        <?php endif ?>
        <?php if (!empty($return_transfer_details)) : ?>
          <div class="card mb-3">
            <div class="card-header">
              <h5><?= __('Return Transfer Details') ?></h5>
            </div>
            <div class="card-body">
              <table class="table table-borderless">
                <?php foreach ($return_transfer_details as $key => $value) : ?>
                  <tr>
                    <td><?= Inflector::humanize($key) ?></td>
                    <td><?= $value ?></td>
                  </tr>
                <?php endforeach ?>
              </table>
            </div>
          </div>
        <?php endif ?>
      <?php endif ?>
      <?php
      $checkout_info = ['name' => __('Name'), 'email' => __('Email'), 'mobile' => __('Mobile')];
      ?>
      <?php if (!empty($checkout_info)) : ?>
        <div class="card mb-3">
          <div class="card-header">
            <h5><?= __('Customer Information') ?></h5>
          </div>
          <div class="card-body">
            <table class="table table-borderless">
              <?php foreach ($checkout_info as $key => $title) : ?>
                <?php if (!empty($order[$key])) : ?>
                  <tr>
                    <td><?= $key == 'id_num' ? 'NRIC/Passport' : Inflector::humanize($key) ?></td>
                    <td><?= $order[$key] ?></td>
                  </tr>
                <?php endif ?>
              <?php endforeach ?>
            </table>
          </div>
        </div>
      <?php endif ?>
      <?php if (!empty($order['order_items'])) : ?>

        <div class="card mb-3">
          <div class="card-header d-flex justify-content-between">
            <h5 class="align-self-center m-0">
              <?= __('Product(s)') ?>
            </h5>
            <div class="d-flex">
            <?php
                $voucher_merging_done = false;
                $voucher_merging_created = '';
                if ($voucher_merging) {
                  $voucher_merging_created = (new \Cake\I18n\DateTime($voucher_merging->created->format('Y-m-d H:i:s'), 'UTC'))->i18nFormat("d MMMM, HH:mm", Configure::read('App.defaultTimezone'));
                  if ($voucher_merging->status == 'done') {
                    $voucher_merging_done = true;
                  } else {
                    echo '<span id="voucher-merging-msg" class="float-right d-flex voucher-merging-in-progress"><span class="align-self-center mr-2">Download as Single PDF (Processing) ' . $voucher_merging_created . '</span>  <span class="voucher-merging-loading"><span>Please wait</span><i class="fa fa-hourglass-start align-self-center"></i></span></span>';
                  }
                } elseif ($order['status'] == 'Paid') {
                  echo $this->Html->link(__('Download as Single PDF (New Request)') . ' <i class="fa fa-file"></i>', ['controller' => 'Vouchers', 'action' => 'downloadAll', $order['id']], ['class' => $voucher_merging ? 'd-none float-right' : 'float-right', 'id' => 'merge-all', 'escape' => false]);
                } elseif ($order['status'] == 'Waiting' || $order['status'] == 'In Progress') {
                  echo '<span class="float-right d-flex"><span class="align-self-center mr-2">Download as Single PDF </span> <span class="voucher-merging-loading"><span>Order Processing</span></span></span>';
                }
                echo $this->Html->link(__('Download as Single PDF (Ready)') . ($voucher_merging ? $voucher_merging_created : '') . ' <i class="fa fa-download"></i>', ['controller' => 'Vouchers', 'action' => 'downloadAll', $order['id']], ['class' => $voucher_merging_done ? 'float-right' : 'd-none float-right', 'id' => 'download-all', 'escape' => false]);

                ?>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-borderless">
                <tr class="border-bottom">
                  <th><?= __('Name') ?></th>
                  <th><?= __('Vouchers') ?></th>
                  <?php if ($auth_user['agent']['hide_voucher_status'] === false) { ?>
                    <th>Status</th>
                  <?php } ?>
                  <?php if ($auth_user['agent']['allowed_api_cancel'] === true) { ?>
                    <th><?= __('Actions') ?></th>
                  <?php } ?>
                </tr>
                <?php foreach ($order['order_items'] as $key => $order_item) : ?>
                  <tr>
                    <td>
                      <div class=""><?= $order_item['attraction_title'] ?></div>
                      <div class="text-muted"><?= $order_item['ticket_name'] ?>
                        <?php if (!empty($order_item['date_of_visit'])) : ?>
                          <div><?php echo (new \Cake\I18n\Date($order_item['date_of_visit']))->format('d M Y') ?></div>
                        <?php endif ?>
                        <?php if (!empty($order_item['time_of_visit'])) : ?>
                          <div><?php echo $order_item['time_of_visit'] ?></div>
                        <?php endif ?>
                      </div>
                    </td>
                    <?php if (!empty($order_item['single_pdf'])) { ?>
                      <td>
                        <?php $added_group = []; ?>
                        <!-- <?php foreach ($order_item->vouchers as $voucher) : ?>
                        <?php if ($voucher->order_qty_idx !== null && !in_array($voucher->order_qty_idx, $added_group)) : ?>
                          <div>
                            <?= $this->Html->link(__('Download Merged') . ' ' . ($voucher->order_qty_idx + 1), ['controller' => 'Vouchers', 'action' => 'sview', $order_item->id . '_' . $voucher->order_qty_idx], ['class' => 'font-weight-bold']) ?>
                          </div>
                          <?php $added_group[] = $voucher->order_qty_idx ?>
                        <?php endif ?>
                        <div>
                            <?= $this->Html->link($voucher->code_id, ['controller' => 'Vouchers', 'action' => 'view', $voucher->id], ['class' => '']) ?>
                            
                        </div>
                      <?php endforeach; ?> -->
                      </td>
                    <?php } elseif (!empty($order_item['vouchers'])) { ?>

                      <?php foreach ($order_item['vouchers'] as $key => $voucher) { ?>
                        <?php if ($key === 0) { ?>
                          <td>
                            <a href="<?= $voucher['voucher'] ?>" target="_blank"><?= $voucher['code'] ?></a>
                          </td>
                          <?php if ($auth_user['agent']['hide_voucher_status'] === false  && $order_created >= $hide_before_date) { ?>
                            <td>
                              <?php
                              $badge = getBadgeInfo($voucher);
                              $bg_color = $badge['bg_color'];
                              $status_to_show = $badge['status_to_show'];
                              ?>
                              <small class="badge badge-<?= $bg_color ?>"><?= $status_to_show ?></small>
                            </td>
                          <?php } elseif ($order_created < $hide_before_date) { ?>
                            <td>&nbsp;</td>
                          <?php } ?>
                          <?php if ($auth_user['agent']['allowed_api_cancel'] === true) { ?>
                            <td>
                              <?= !empty($voucher['is_cancellable']) ? $this->Form->postLink(__('Cancel'), ['controller' => 'Orders', 'action' => 'cancelVoucher', $voucher['code']], ['class' => 'btn btn-outline-danger btn-sm', 'confirm' => 'Are you sure you want to cancel this voucher?']) : '' ?>
                            </td>
                          <?php } ?>
                        <?php } else { ?>
                  <tr>
                    <td>&nbsp;</td>
                    <td>
                      <a href="<?= $voucher['voucher'] ?>" target="_blank"><?= $voucher['code'] ?></a>
                    </td>
                    <?php if ($auth_user['agent']['hide_voucher_status'] === false && $order_created >= $hide_before_date) { ?>
                      <td>
                        <?php
                            $badge = getBadgeInfo($voucher);
                            $bg_color = $badge['bg_color'];
                            $status_to_show = $badge['status_to_show'];
                        ?>
                        <small class="badge badge-<?= $bg_color ?>"><?= $status_to_show ?></small>
                      </td>
                    <?php } elseif ($order_created < $hide_before_date) { ?>
                      <td>&nbsp;</td>
                    <?php } ?>
                    <?php if ($auth_user['agent']['allowed_api_cancel'] === true) { ?>
                      <td>
                        <?= !empty($voucher['is_cancellable']) ? $this->Form->postLink(__('Cancel'), ['controller' => 'Orders', 'action' => 'cancelVoucher', $voucher['code']], ['class' => 'btn btn-outline-danger btn-sm', 'confirm' => 'Are you sure you want to cancel this voucher?']) : '' ?>
                      </td>
                    <?php } ?>
                  </tr>
                <?php } ?>

              <?php } ?>
            <?php } ?>
            </tr>
          <?php endforeach ?>
              </table>
            </div>
          </div>
        </div>
      <?php endif ?>
    </div>
    <div class="col-md-6 col-lg-4">
      <div id="tickets-sidebar">
        <?php if (!empty($order['order_items'])) : ?>
          <?php foreach ($order['order_items'] as $order_item) : ?>
            <div class="card mb-3">
              <div class="card-body">
                <div><?php echo '<div class="font-weight-bold">' . $order_item['attraction_title'] . '</div> <div class="text-muted">' . $order_item['ticket_name'] . '</div>' ?></div>
                <div><?php echo $this->Number->currency($order_item['price']) ?></div>
                <div class="row py-2">
                  <div class="col-md-6">
                    <?php if (!empty($order_item['date_of_visit'])) : ?>
                      <div><?php echo (new \Cake\I18n\Date($order_item['date_of_visit']))->format('d M Y') ?></div>
                    <?php endif ?>
                    <?php if (!empty($order_item['time_of_visit'])) : ?>
                      <div><?php echo $order_item['time_of_visit'] ?></div>
                    <?php endif ?>
                  </div>
                  <div class="col-md-2 text-right p-0">
                    <span class="py-1"> &times; <?php echo $order_item['quantity'] ?> </span>
                  </div>
                  <div class="col-md-4 text-right"><?php echo $this->Number->currency($order_item['total']) ?></div>
                </div>

              </div>
            </div>
          <?php endforeach; ?>
          <div class="py-4 px-4 footer-total">
            <div class="row">
              <div class="col"><?= __('Subtotal') ?></div>
              <div class="col text-right"><?= $this->Number->currency($order['subtotal']) ?></div>
            </div>
            <div class="row">
              <div class="col"><?= __('Convenience Fee') ?></div>
              <div class="col text-right"><?= $this->Number->currency($order['convenience_fee']) ?></div>
            </div>
            <div class="row grandtotal-row">
              <div class="col"><?= __('Grand Total') ?></div>
              <div class="col text-right"><?= $this->Number->currency($order['total']) ?></div>
            </div>
          </div>
          <div class="py-4">
            <div class="clearfix">
              <?php
              if (Configure::read('Features.invoice')) {
                echo $this->Html->link('<i class="fa fa-download"></i> ' . __('Download Invoice'), ['controller' => 'Orders', 'action' => 'downloadInvoice', $order['order_ref_id']], ['class' => 'float-right', 'escape' => false]);
              }
              ?>
            </div>
          </div>
        <?php endif ?>
      </div>
      <?php $this->Html->scriptStart(['block' => true]) ?>
      $(window).on('scroll', function (event) {
      var scrollValue = $(window).scrollTop();
      if (scrollValue > 120) {
      if (!$('#tickets-sidebar').hasClass('sticky-top')) {
      $('#tickets-sidebar').addClass('sticky-top');
      }
      } else{
      $('#tickets-sidebar').removeClass('sticky-top');
      }
      });

      var isMergingDone = false;
      function checkMergingStatus() {
        if ($('.voucher-merging-in-progress').length == 0) {
          return
        }
        $.ajax({
          url: "<?= $this->Url->build(['controller' => 'VoucherMerging', 'action' => 'getStatus']) ?>",
          data: {order_ref_id: "<?= $order['order_ref_id'] ?>"},
          type: "GET",
          dataType: "JSON"
        }).always(function(res, status, xhr) {
          if (xhr.status == 200) {
            if (res.voucher_merging) {
              if (res.voucher_merging.status === 'done') {
                $('#download-all').removeClass('d-none');
                $('#voucher-merging-msg').addClass('d-none').removeClass('d-flex');
                $('.alert').remove();
                isMergingDone = true;
              }
            }
          }
          if (!isMergingDone) {
            setTimeout(checkMergingStatus, 4000);
          }
        });
      }

      checkMergingStatus();

      <?php $this->Html->scriptEnd() ?>
    </div>
  </div>

<?php } ?>


<?php $this->start('css') ?>
<style type="text/css">
  .grandtotal-row {
    font-size: 18px;
  }
  .fa-hourglass-start {
      animation: clock 5s infinite;
    }
    @keyframes clock {
      50% {transform: rotate(180deg);}
      100% {transform: rotate(360deg);}
    }
  .voucher-merging-loading {
    position:relative;
    width: 30px;
    height: 50px;
    text-align: center;
  }
  .voucher-merging-loading i {
    margin: auto;
  }
  .voucher-merging-loading span {
    font-size: 8px;
    position: absolute;
    bottom: 8px;
    left: 0;
  }
</style>
<?php $this->end() ?>