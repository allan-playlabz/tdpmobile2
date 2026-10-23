<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Agent[]|\Cake\Collection\CollectionInterface $agents
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('New Agent'), ['action' => 'add']) ?></li>
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
<div class="agents index large-9 medium-8 columns content">
    <h3><?= __('Agents') ?></h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id') ?></th>
                <th scope="col"><?= $this->Paginator->sort('first_name') ?></th>
                <th scope="col"><?= $this->Paginator->sort('last_name') ?></th>
                <th scope="col"><?= $this->Paginator->sort('email') ?></th>
                <th scope="col"><?= $this->Paginator->sort('password') ?></th>
                <th scope="col"><?= $this->Paginator->sort('verification_code') ?></th>
                <th scope="col"><?= $this->Paginator->sort('otp') ?></th>
                <th scope="col"><?= $this->Paginator->sort('otp_created') ?></th>
                <th scope="col"><?= $this->Paginator->sort('company_name') ?></th>
                <th scope="col"><?= $this->Paginator->sort('image') ?></th>
                <th scope="col"><?= $this->Paginator->sort('general_category') ?></th>
                <th scope="col"><?= $this->Paginator->sort('country') ?></th>
                <th scope="col"><?= $this->Paginator->sort('issued_bank') ?></th>
                <th scope="col"><?= $this->Paginator->sort('account_no') ?></th>
                <th scope="col"><?= $this->Paginator->sort('contact_no') ?></th>
                <th scope="col"><?= $this->Paginator->sort('gender') ?></th>
                <th scope="col"><?= $this->Paginator->sort('credits') ?></th>
                <th scope="col"><?= $this->Paginator->sort('rebates') ?></th>
                <th scope="col"><?= $this->Paginator->sort('role') ?></th>
                <th scope="col"><?= $this->Paginator->sort('master_agent_id') ?></th>
                <th scope="col"><?= $this->Paginator->sort('enable_generation_confirmation') ?></th>
                <th scope="col"><?= $this->Paginator->sort('enable_api') ?></th>
                <th scope="col"><?= $this->Paginator->sort('api_key') ?></th>
                <th scope="col"><?= $this->Paginator->sort('secret_key') ?></th>
                <th scope="col"><?= $this->Paginator->sort('allowed_api_cancel') ?></th>
                <th scope="col"><?= $this->Paginator->sort('email_displayname') ?></th>
                <th scope="col"><?= $this->Paginator->sort('status') ?></th>
                <th scope="col"><?= $this->Paginator->sort('can_add_sub') ?></th>
                <th scope="col"><?= $this->Paginator->sort('last_activity') ?></th>
                <th scope="col"><?= $this->Paginator->sort('created') ?></th>
                <th scope="col"><?= $this->Paginator->sort('modified') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($agents as $agent): ?>
            <tr>
                <td><?= h($agent->id) ?></td>
                <td><?= h($agent->first_name) ?></td>
                <td><?= h($agent->last_name) ?></td>
                <td><?= h($agent->email) ?></td>
                <td><?= h($agent->password) ?></td>
                <td><?= h($agent->verification_code) ?></td>
                <td><?= $this->Number->format($agent->otp) ?></td>
                <td><?= h($agent->otp_created) ?></td>
                <td><?= h($agent->company_name) ?></td>
                <td><?= h($agent->image) ?></td>
                <td><?= h($agent->general_category) ?></td>
                <td><?= h($agent->country) ?></td>
                <td><?= h($agent->issued_bank) ?></td>
                <td><?= h($agent->account_no) ?></td>
                <td><?= h($agent->contact_no) ?></td>
                <td><?= h($agent->gender) ?></td>
                <td><?= $this->Number->format($agent->credits) ?></td>
                <td><?= $this->Number->format($agent->rebates) ?></td>
                <td><?= h($agent->role) ?></td>
                <td><?= $agent->has('master_agent') ? $this->Html->link($agent->master_agent->id, ['controller' => 'Agents', 'action' => 'view', $agent->master_agent->id]) : '' ?></td>
                <td><?= h($agent->enable_generation_confirmation) ?></td>
                <td><?= h($agent->enable_api) ?></td>
                <td><?= h($agent->api_key) ?></td>
                <td><?= h($agent->secret_key) ?></td>
                <td><?= h($agent->allowed_api_cancel) ?></td>
                <td><?= h($agent->email_displayname) ?></td>
                <td><?= h($agent->status) ?></td>
                <td><?= h($agent->can_add_sub) ?></td>
                <td><?= h($agent->last_activity) ?></td>
                <td><?= h($agent->created) ?></td>
                <td><?= h($agent->modified) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $agent->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $agent->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $agent->id], ['confirm' => __('Are you sure you want to delete # {0}?', $agent->id)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
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
