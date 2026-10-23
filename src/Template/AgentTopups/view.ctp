<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AgentTopup $agentTopup
 */
?>
<div class="agentTopups view large-9 medium-8 columns content">
    <h3><?= __('Top Up') ?></h3>
    <table class="table table-borderless">
        <tr>
            <th scope="row"><?= __('Amount') ?></th>
            <td><big class="font-weight-bold"><?= $this->Number->currency($agentTopup->amount) ?></big></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Top Up Date') ?></th>
            <td><?= $agentTopup->topup_date->format('d M Y') ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Image') ?></th>
            <td><?= ($agentTopup->image)?$this->Html->image('/'.$agentTopup->image,['class'=>'img-fluid']):'' ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Remarks') ?></th>
            <td><?= h($agentTopup->remarks) ?></td>
        </tr>
    </table>
</div>
