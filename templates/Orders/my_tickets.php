<?php

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Voucher[]|\Cake\Collection\CollectionInterface $vouchers
 */

use Cake\Utility\Text;
$from = $this->request->getQuery('from') ?: '';
$to = $this->request->getQuery('to') ?: '';
$status_map = [
    'used' => 'Redeemed', 
    'purchased' => 'Valid', 
    'cancelled' => 'Cancelled',
    'expired' => 'Expired',
    'partially_redeemed_expired'=>'Partially Redeemed Expired',
    'partially_redeemed_cancelled'=>'Partially Redeemed Cancelled',
];
$color_map = [
    'used' => 'success', 
    'purchased' => 'primary', 
    'cancelled' => 'danger',
    'expired' => 'warning',
    'partially_redeemed_expired'=>'warning',
    'partially_redeemed_cancelled'=>'danger',
];
$hide_before_date = new \Cake\I18n\DateTime('-6 months');
?>
<h3><?= __('My Tickets') ?></h3>
<div class="row">
    <div class="col">
        <?php echo $this->Form->create(null, ['type' => 'get', 'class' => 'form-inline mb-4 card-header order-search']) ?>
        <div class="card-left-header d-flex my-2">
            <?php echo $this->Form->control('code', ['type' => 'text', 'class' => 'form-control', 'default' => $this->request->getQuery('code'), 'label' => ['class' => 'pe-1', 'text' => __('Code'), 'autocomplete' => 'off']]) ?>
            <!-- <?php echo $this->Form->control('status', ['class' => 'form-control', 'options' => ['all' => 'All','done' => 'Completed', 
    'cancelled' => 'Cancelled', 
    'waiting' => 'Waiting',
    'refunded'=>'Refunded',
    'rejected'=>'Failed'], 'default' => $this->request->getQuery('status'), 'label' => ['class' => 'ms-4 pe-1', 'text' => __('Status')]]) ?> -->
        </div>
        <div class="card-right-header d-flex align-items-end my-2">
            <?php echo $this->Form->control('from', ['class' => 'datepicker form-control', 'value' => $from, 'label' => ['class' => 'pe-1', 'text' => __('From')]]) ?>
            <?php echo $this->Form->control('to', ['class' => 'datepicker form-control', 'value' => $to, 'label' => ['class' => 'ms-4 pe-1', 'text' => __('To')]]) ?>
            <div class="form-group"><?php echo $this->Form->submit(__('Filter'), ['class' => 'btn btn-primary ms-1']) ?></div>
            <div class="form-group"><?php echo $this->Html->link(__('Show All'), ['action' => 'myTickets'], ['class' => 'btn btn-primary ms-1','style'=>'min-width:80px']) ?></div>
        </div>
        <?php echo $this->Form->end() ?>
    </div>
</div>
<div class="row">
    <div class="col">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        
                        <th scope="col" class="col-w-20p"><?= __('Order Ref') ?></th>
                        <th scope="col" class="col-w-20p"><?= __('Code') ?></th>
                        <th scope="col"><?= __('Ticket') ?></th>
                        <th scope="col" class="col-w-140"><?= __('Date') ?></th>
                        <?php if ($auth_user['agent']['hide_voucher_status'] === false) { ?>
                        <th scope="col" class="col-w-70"><?= __('Status') ?></th>
                        <?php } ?>
                        <?php if ($auth_user['agent']['allowed_api_cancel'] === true) { ?>
                        <th scope="col" class="col-w-90"><?= __('Actions') ?></th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vouchers as $voucher) : ?>
                        <tr>
                            
                            <td class="text-truncate"><?= $this->Html->link($voucher->order_item->order->order_ref_id, ['action' => 'view', $voucher->order_item->order->order_ref_id]) ?></td>
                            <td class="text-truncate"><?= $voucher->code_id ?></td>
                            <td>
                                <?php
                                    $ticket_info = json_decode($voucher->order_item->ticket_info);
                                    if (!empty($ticket_info)) {
                                        echo $ticket_info->attraction_name .'<br>';
                                        echo '<i>' . $ticket_info->ticket_name . '</i>';
                                    }
                                ?>
                            </td>
                            <td><?= h($voucher->created->format('d M Y H:ia')) ?></td>
                            <?php if ($auth_user['agent']['hide_voucher_status'] === false && $voucher->created >= $hide_before_date) { ?>
                            <td>
                                <span class="badge text-bg-<?= $color_map[$voucher->code_status] ?>"><?= $voucher->voucher_type != 'generated' && $voucher->code_status == 'purchased' ? 'Purchased' : $status_map[$voucher->code_status] ?></span>
                            </td>
                            <?php } elseif ($voucher->created < $hide_before_date) { ?>
                            <td>&nbsp;</td>
                            <?php } ?>
                            <?php if ($auth_user['agent']['allowed_api_cancel'] === true) { ?>
                            <td>
                            <?= !empty($voucher->is_cancellable) ? '<a href="#" class="btn btn-outline-danger btn-sm" data-post-url="' . $this->Url->build(['controller' => 'Orders', 'action' => 'cancelVoucher', $voucher->code_id]) . '" data-confirm="Are you sure you want to cancel this voucher?">' . __('Cancel') . '</a>' : '' ?>
                            </td>
                            <?php } ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="paginator">
            <ul class="pagination">
                <?= $this->Paginator->first('<< ' . __('first')) ?>
                <?= $this->Paginator->prev('< ' . __('previous')) ?>
                <?= $this->Paginator->numbers() ?>
                <?= $this->Paginator->next(__('next') . ' >') ?>
                <?= $this->Paginator->last(__('last') . ' >>') ?>
            </ul>
            <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
        </div>
    </div>
</div>

<?= $this->Html->css(['bootstrap-datepicker3.standalone.min'], ['block' => 'internal_css']); ?>
<?= $this->Html->script(['bootstrap-datepicker.min'], ['block' => 'scriptBottom']) ?>
<?php $this->Html->script('pages/date-filters.js', ['block' => 'script']) ?>
