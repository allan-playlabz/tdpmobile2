<?php

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AgentTopup[]|\Cake\Collection\CollectionInterface $agentTopups
 */
$no = 1;
$from = $this->request->getQuery('from') ?: '';
$to = $this->request->getQuery('to') ?: '';
?>
<div class="row">
  <div class="col">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2">
      <h1 class="h2"><?= __('Top Up') ?></h1>
      <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group mr-2">
        <?php echo  $this->Html->link(__('Add'), ['action' => 'add'], ['class' => 'btn btn-primary float-right']) ?>
        <button type="button" class="btn btn-success ml-1" data-toggle="modal" data-target="#expressTopupModal"><?= __('Express Topup') ?></button>
        <?php echo  $this->Form->postLink(__('Download'), ['action'=>'index', '?' => $this->request->getQuery()],['class'=>'btn btn-primary ml-1', 'block' => 'postLink']) ?>
        </div>
      </div>
    </div>
    <div class="mb-2">
      *Only the last 6 months records will be shown 
    </div>
  </div>
</div>

<?= $this->fetch('postLink') ?>
<div class="agentTopups index large-9 medium-8 columns content">

  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th scope="col"><?= __('Top Up Date') ?></th>
          <th scope="col"><?= __('Amount') . '(' . __('SGD') . ')' ?></th>
          <th scope="col"><?= __('Status') ?></th>
          <th scope="col" class="actions"><?= __('Actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($agentTopups as $agentTopup) : ?>
          <tr>
            <td><?= $agentTopup->topup_date->format('d M Y') ?></td>
            <td><?= $this->Number->precision($agentTopup->amount, 2) ?></td>
            <td><?= ucwords($agentTopup->status) ?></td>
            <td class="actions">
              <?= $this->Html->link(__('View'), ['action' => 'view', $agentTopup->id]) ?>
            </td>
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

<!-- Express Topup Modal -->
<div class="modal fade" id="expressTopupModal" tabindex="-1" role="dialog" aria-labelledby="expressTopupModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="expressTopupModalLabel">
          <i class="fa fa-bolt mr-2"></i><?= __('Express Top Up') ?>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="mb-3">
          <?= __('You can do an express top up. Simply make a bank transfer to the account below and include your Bank Reference Code below in the transfer reference so we can match your payment automatically.') ?>
        </p>
        <div class="form-group mb-3">
          <label class="font-weight-bold mb-1"><?= __('Your Bank Reference Code') ?></label>
          <?php if (!empty($bank_reference_code)) : ?>
            <div class="input-group">
              <input type="text" id="bankReferenceCode" class="form-control text-monospace font-weight-bold" value="<?= h($bank_reference_code) ?>" readonly>
              <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" id="copyReferenceBtn" title="<?= __('Copy') ?>">
                  <i class="fa fa-copy"></i> <?= __('Copy') ?>
                </button>
              </div>
            </div>
            <small class="form-text text-muted"><?= __('Include this code as the payment reference for your bank transfer.') ?></small>
          <?php else : ?>
            <div class="alert alert-warning mb-0">
              <?= __('No bank reference code is assigned to your account yet. Please contact support to enable express top up.') ?>
            </div>
          <?php endif; ?>
        </div>
        <?php $bank_account = \Cake\Core\Configure::read('AgentTopups.bank_account'); ?>
        <?php if (!empty($bank_account)) : ?>
          <hr>
          <div>
            <label class="font-weight-bold mb-2"><?= __('Bank Account Details') ?></label>
            <?php
              $bank_fields = [
                'account_name' => __('Account name'),
                'account_number' => __('Bank account number'),
                'bank_code' => __('Bank code'),
                'branch_code' => __('Branch code'),
                'swift_code' => __('SWIFT code'),
                'bank_name' => __('Bank name'),
                'location' => __('Location'),
              ];
            ?>
            <table class="table table-sm table-borderless mb-0">
              <tbody>
                <?php foreach ($bank_fields as $key => $label) : ?>
                  <?php if (!empty($bank_account[$key])) : ?>
                    <tr>
                      <th scope="row" class="text-muted font-weight-normal pl-0" style="width:45%;"><?= $label ?></th>
                      <td class="font-weight-bold"><?= h($bank_account[$key]) ?></td>
                    </tr>
                  <?php endif; ?>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= __('Close') ?></button>
      </div>
    </div>
  </div>
</div>

<?= $this->Html->css(['bootstrap-datepicker3.standalone.min'],['block'=>'internal_css']); ?>
<?= $this->Html->script(['bootstrap-datepicker.min'],['block'=>'scriptBottom']) ?>
<?php $this->Html->scriptStart(['block' => true]) ?>
$(document).ready(function() {

  $('.datepicker').datepicker({format:'d M yyyy',endDate:'today'});

  $('#copyReferenceBtn').on('click', function() {
    var input = document.getElementById('bankReferenceCode');
    input.select();
    input.setSelectionRange(0, 99999); // for mobile
    document.execCommand('copy');
    var $btn = $(this);
    var original = $btn.html();
    $btn.html('<i class="fa fa-check"></i> Copied');
    setTimeout(function() { $btn.html(original); }, 1500);
  });
});
<?php $this->Html->scriptEnd() ?>