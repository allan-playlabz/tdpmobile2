<?php

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Order[]|\Cake\Collection\CollectionInterface $orders
 */
use Cake\Utility\Text;
$orders = $orders->toArray();
$from = $this->request->getQuery('from') ?: '';
$to = $this->request->getQuery('to') ?: '';
$status_map = [
    'done' => __('Completed'), 
    'cancelled' => __('Cancelled'), 
    'waiting' => __('Waiting'),
    'in_progress' => __('Waiting'),
    'refunded'=>__('Refunded'),
    'rejected'=>__('Failed')
];
$color_map = [
    'done' => 'success', 
    'cancelled' => 'warning', 
    'waiting' => 'primary',
    'in_progress' => 'info',
    'refunded'=>'dark',
    'rejected'=>'danger'
];
?>
<h3><?= __('My Orders') ?></h3>
<div class="row">
    <div class="col">
        <?php echo $this->Form->create(null, ['type' => 'get', 'class' => 'mb-4 card-header order-search']) ?>
        <div class="row">
            <div class="col-lg-3 col-md-6 col-12 mb-2">
                <?php echo $this->Form->control('order_ref_id', ['type' => 'text', 'class' => 'form-control', 'default' => $this->request->getQuery('order_ref_id'), 'label' => ['text' => __('Order Ref')], 'autocomplete' => 'off']) ?>
            </div>
            <div class="col-lg-3 col-md-6 col-12 mb-2">
                <?php echo $this->Form->control('tracking_no', ['type' => 'text', 'class' => 'form-control', 'default' => $this->request->getQuery('tracking_no'), 'label' => ['text' => __('Tracking No')], 'autocomplete' => 'off']) ?>
            </div>
            <div class="col-lg-3 col-md-6 col-12 mb-2">
                <?php echo $this->Form->control('status', ['class' => 'form-control', 'options' => ['all' => 'All','done' => __('Completed'), 
                'cancelled' => __('Cancelled'), 
                'waiting' => __('Waiting'),
                'refunded'=> __('Refunded'),
                'rejected'=> __('Failed')], 'default' => $this->request->getQuery('status'), 'label' => ['text' => __('Status')]]) ?>
            </div>
            <div class="col-lg-3 col-md-6 col-12 mb-2">
                <?php echo $this->Form->control('date_type',['options'=> ['created' => 'Booking Date', 'visit_date' => 'Visit Date'],'class'=>'form-control', 'label'=>['text' => 'Date Type'],'default'=>$this->request->getQuery('date_type')]);   ?>
            </div>
            <div class="col-lg-3 col-md-6 col-12 mb-2">
                <?php echo $this->Form->control('from', ['class' => 'datepicker form-control', 'value' => $from, 'label' => ['text' => __('From')]]) ?>
            </div>
            <div class="col-lg-3 col-md-6 col-12 mb-2">
                <?php echo $this->Form->control('to', ['class' => 'datepicker form-control', 'value' => $to, 'label' => ['text' => __('To')]]) ?>
            </div>
            <div class="col-lg-6 col-12 d-flex justify-content-end align-items-end mb-2">
                <div class="form-group mb-0"><?php echo $this->Form->submit(__('Filter'), ['class' => 'btn btn-primary mr-2']) ?></div>
                <div class="form-group mb-0"><?php echo $this->Html->link(__('Show All'), ['action' => 'index'], ['class' => 'btn btn-primary']) ?></div>
            </div>
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
                        <th scope="col" style="width: 140px"><?= __('Date') ?></th>
                        <th scope="col"><?= __('Order Ref') ?></th>
                        <th scope="col"><?= __('Tracking No') ?></th>
                        <th scope="col"><?= __('Items') ?></th>
                        <th scope="col" style="width: 70px"><?= __('Status') ?></th>
                        <th scope="col" style="width: 90px"><?= __('Total') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order) : ?>
                        <tr>
                            <td><?= h($order->created->format('d M Y H:ia')) ?></td>
                            <td style="white-space: nowrap; overflow: hidden; text-overflow:ellipsis"><?= $this->Html->link($order->order_ref_id, ['action' => 'view', $order->order_ref_id]) ?></td>
                            <td><div style="max-width:140px; white-space: nowrap; overflow: hidden; text-overflow:ellipsis"><?= h($order->tracking_no) ?></div></td>
                            <td>
                                <?php foreach ($order->order_items as $order_item) : ?>
                                    <div><?= $order_item->ticket->name ?> <span class="badge badge-info"><?= $order_item->qty ?></span></div>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $color_map[$order->status] ?>"><?= $status_map[$order->status] ?></span>
                            </td>
                            <td><?= $this->Number->currency($order->total) ?></td>

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
            <p><?= $this->Paginator->counter(['format' => __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')]) ?></p>
        </div>
    </div>
</div>

<?= $this->Html->css(['bootstrap-datepicker3.standalone.min'], ['block' => 'internal_css']); ?>
<?= $this->Html->script(['bootstrap-datepicker.min'], ['block' => 'scriptBottom']) ?>
<?php $this->Html->scriptStart(['block' => true]) ?>
$('.datepicker').datepicker({format:'d M yyyy'});
<?php $this->Html->scriptEnd() ?>
