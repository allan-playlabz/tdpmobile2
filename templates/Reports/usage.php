<?php
$no = 1;
?>
<div class="row mb-2">
  <div class="col">
    <div class="btn-group" role="group">
      <button id="credit-history" type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      <?= __('Usage History') ?>
      </button>
      <div class="dropdown-menu" aria-labelledby="credit-history">
        <a class="dropdown-item" href="/reports/transactions"><?= __('Transaction History') ?></a>
        <a class="dropdown-item" href="/reports/credits"><?= __('Top-up History') ?></a>
        
      </div>
    </div>
  </div>
</div>
<div class="row">
  <div class="col">
    <h3 class="d-inline-block"><?= __('Usage History') ?></h3><?php echo  $this->Form->postLink('Export to CSV',[],['class'=>'btn btn-default float-right']) ?>
  </div>
</div>
<div class="index large-9 medium-8 columns content">

  <div class="table-responsive">
    <?php foreach ($credit_logs as $credit_log): ?>
      <div class="topup">
        <div class="topup-amount"><?= __('Amount') ?>: <?= $this->Number->currency($credit_log->amount,'SGD') ?></div>
        <div><?= __('Updated Date') ?>: <?= $credit_log->modified->format('d M Y h:i a') ?> </div>
        <div><?= __('Ref ID') ?>: <?= $credit_log->id ?> </div>
        <div><?= __('Remarks') ?>:  N/A </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php $this->start('internal_css'); ?>
  <style type = "text/css">
    .topup {
      background-color:#eee;
      font-size: 12px;
      padding: 10px;
      border-radius: 10px;
      margin-top: 10px;
      margin-bottom: 10px;
    }
    .topup-amount {
      font-size: 14px;
      font-weight: bold;

    }
  </style>
<?php $this->end() ?>