<?php 
$from = $this->request->getQuery('from') ?: '';
$to = $this->request->getQuery('to') ?: '';
?>
<div class="row">
  <div class="col">
    <h3 class="d-inline-block"><?= __('Balance Report') ?></h3>
    <div class="mb-2">
      *Only the last 100 days records will be shown 
    </div>
    <?php echo $this->Form->create(null, ['type' => 'get', 'class' => 'form-inline mb-4 card-header order-search']) ?>
        <?php echo $this->Form->control('from', ['class' => 'datepicker form-control', 'value' => $from, 'label' => ['class' => 'pe-1', 'text' => __('From')]]) ?>
        <?php echo $this->Form->control('to', ['class' => 'datepicker form-control', 'value' => $to, 'label' => ['class' => 'ms-4 pe-1', 'text' => __('To')]]) ?>
        <div class="btn-toolbar mb-2 mb-md-0">
          <div class="btn-group me-2">
          <?php echo $this->Form->button(__('Filter'), ['class' => 'btn btn-primary ms-1']) ?>
          <?php echo $this->Html->link(__('Show All'), ['action' => 'index'], ['class' => 'btn btn-primary ms-1 min-w-80']) ?>
          <a href="#" class="btn btn-primary ms-1" data-post-url="<?= $this->Url->build(['action' => 'index', '?' => $this->request->getQuery()]) ?>"><?= __('Download') ?></a>
          </div>
        </div>
    <?php echo $this->Form->end() ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
          <thead>
              <tr>
                <th scope="col"><?= __('Date') ?></th>
                <th scope="col"><?= __('Type') ?></th>
                <th scope="col" class="text-end pe-4">SGD <?= __('Amount') . '(' . __('SGD') . ')' ?></th>
                <th scope="col" class="text-end pe-4">SGD <?= __('Balance') . '(' . __('SGD') . ')' ?></th>
              </tr>
          </thead>
          <tbody>
              <?php foreach ($creditLogs as $creditLog): ?>
              <tr>
                <?php 
                $amount = $creditLog->debit_credit == 'debit' ? $this->Number->precision($creditLog->amount, 2) : '(' . $this->Number->precision($creditLog->amount, 2) . ')';
                $credits = $creditLog->credits > 0 ? $this->Number->precision($creditLog->credits, 2) : '(' . $this->Number->precision($creditLog->credits, 2) . ')';
                $type = $creditLog->order_id === null && $creditLog->debit_credit == 'debit' ? 'Top Up' : $this->Html->link($creditLog->order->order_ref_id, ['controller' => 'Orders', 'action' => 'view', $creditLog->order->order_ref_id]);
                if ($creditLog->order_id !== null && bccomp(strval($creditLog->amount), strval($creditLog->order->total), 2) !== 0 && $creditLog->debit_credit == 'debit') {
                  $type .= ' (Rebate)';
                }
                if ($creditLog->order_id !== null && bccomp(strval($creditLog->amount), strval($creditLog->order->total), 2) === 0 && $creditLog->debit_credit == 'debit') {
                  $type .= ' (Cancel)';
                }
                if ($creditLog->order_id !== null && bccomp(strval($creditLog->amount), strval($creditLog->order->total), 2) !== 0 && $creditLog->debit_credit == 'credit') {
                  $type .= ' (Cancel Rebate)';
                }
                ?>
                <td><?= $creditLog->created->format('d M Y h:ia') ?></td>
                <td><?= $type ?></td>
                <td class="text-end pe-4"><?= $amount ?></td>
                <td class="text-end pe-4"><?= $credits ?></td>
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
<?= $this->Html->css(['bootstrap-datepicker3.standalone.min'],['block'=>'internal_css']); ?>
<?= $this->Html->script(['bootstrap-datepicker.min'],['block'=>'scriptBottom']) ?>
<?php $this->set('pageData', ['datepicker' => ['endDate' => 'today']]) ?>
<?php $this->Html->script('pages/date-filters.js?v=20261106', ['block' => 'script']) ?>