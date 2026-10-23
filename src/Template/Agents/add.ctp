<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Agent $agent
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('List Agents'), ['action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('List Master Agents'), ['controller' => 'Agents', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New Master Agent'), ['controller' => 'Agents', 'action' => 'add']) ?></li>
        <li><?= $this->Html->link(__('List Agent Topups'), ['controller' => 'AgentTopups', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New Agent Topup'), ['controller' => 'AgentTopups', 'action' => 'add']) ?></li>
        <li><?= $this->Html->link(__('List Credit Logs'), ['controller' => 'CreditLogs', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New Credit Log'), ['controller' => 'CreditLogs', 'action' => 'add']) ?></li>
        <li><?= $this->Html->link(__('List Settlement Agents'), ['controller' => 'SettlementAgents', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New Settlement Agent'), ['controller' => 'SettlementAgents', 'action' => 'add']) ?></li>
        <li><?= $this->Html->link(__('List Orders'), ['controller' => 'Orders', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New Order'), ['controller' => 'Orders', 'action' => 'add']) ?></li>
        <li><?= $this->Html->link(__('List Tickets'), ['controller' => 'Tickets', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New Ticket'), ['controller' => 'Tickets', 'action' => 'add']) ?></li>
    </ul>
</nav>
<div class="agents form large-9 medium-8 columns content">
    <?= $this->Form->create($agent) ?>
    <fieldset>
        <legend><?= __('Add Agent') ?></legend>
        <?php
            echo $this->Form->control('first_name');
            echo $this->Form->control('last_name');
            echo $this->Form->control('email');
            echo $this->Form->control('password');
            echo $this->Form->control('verification_code');
            echo $this->Form->control('otp');
            echo $this->Form->control('otp_created', ['empty' => true]);
            echo $this->Form->control('company_name');
            echo $this->Form->control('image');
            echo $this->Form->control('general_category');
            echo $this->Form->control('country');
            echo $this->Form->control('issued_bank');
            echo $this->Form->control('account_no');
            echo $this->Form->control('contact_no');
            echo $this->Form->control('gender');
            echo $this->Form->control('credits');
            echo $this->Form->control('rebates');
            echo $this->Form->control('role');
            echo $this->Form->control('master_agent_id', ['options' => $masterAgents, 'empty' => true]);
            echo $this->Form->control('enable_generation_confirmation');
            echo $this->Form->control('enable_api');
            echo $this->Form->control('api_key');
            echo $this->Form->control('secret_key');
            echo $this->Form->control('allowed_api_cancel');
            echo $this->Form->control('email_displayname');
            echo $this->Form->control('status');
            echo $this->Form->control('can_add_sub');
            echo $this->Form->control('last_activity', ['empty' => true]);
            echo $this->Form->control('orders._ids', ['options' => $orders]);
            echo $this->Form->control('tickets._ids', ['options' => $tickets]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
