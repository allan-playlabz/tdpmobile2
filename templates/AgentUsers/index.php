<?php echo $this->Html->link('<i class="fa fa-plus-circle"></i> ' . __('Add User'),['controller'=>'AgentUsers','action'=>'add'],['class'=>'btn btn-primary btn-sm float-end mb-3','escape'=>false]) ?>

<?php $no = 1 ?>
<div class="table-responsive">
  <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <?php // index() is not paginated (PaginatorHelper throws without paging data in 5.x); same links Paginator->sort() rendered in 3.x ?>
          <?php
            $current_sort = $this->getRequest()->getQuery('sort');
            $current_direction = strtolower((string)$this->getRequest()->getQuery('direction')) === 'desc' ? 'desc' : 'asc';
            $sort_link = function (string $label, string $field) use ($current_sort, $current_direction) {
              $direction = $current_sort === $field && $current_direction === 'asc' ? 'desc' : 'asc';
              return $this->Html->link($label, ['?' => ['sort' => $field, 'direction' => $direction]], ['class' => $current_sort === $field ? $current_direction : null]);
            };
          ?>
          <th scope="col"><?= $sort_link(__('Name'), 'name') ?></th>
          <th scope="col"><?= $sort_link(__('Email'), 'email') ?></th>
          <th scope="col"><?= $sort_link(__('Role'), 'role') ?></th>
          <th scope="col"><?= $sort_link(__('Status'), 'status') ?></th>
          <th scope="col"><?= __('Actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($agents as $agent_user): ?>
          <tr>
            <td><?= $agent_user->name ?></td>
                <td><?= h($agent_user->email) ?></td>
                <td><span class="badge <?= ($agent_user->role=='viewer')?'text-bg-secondary':'text-bg-info' ?>"><?= __(ucwords($agent_user->role)) ?></span></td>
                <td><span class="badge <?= ($agent_user->status=='active')?'text-bg-primary':'text-bg-dark' ?>"><?= __(ucwords($agent_user->status)) ?></span></td>
                <td>
                    <?php echo $this->Html->link('<i class="fa fa-pencil"></i>',['controller' => 'AgentUsers', 'action' => 'edit', $agent_user->id],['escape'=>false,'class'=>'btn btn-primary']) ?>
                    <a href="#" class="btn btn-primary" data-post-url="<?= $this->Url->build(['controller' => 'AgentUsers', 'action' => 'agentResetPassword', $agent_user->id]) ?>" data-confirm="<?= h(__('Are you sure you want to reset password of {0}', $agent_user->name)) ?>"><?= __('Reset Password') ?></a>
                    <?php if ($agent_user->totp_enabled): ?>
                    <a href="#" class="btn btn-outline-primary" data-post-url="<?= $this->Url->build(['controller' => 'AgentUsers', 'action' => 'resetTwoFactor', $agent_user->id]) ?>" data-confirm="<?= h(__('Reset the authenticator app of {0}? Only do this after confirming with them in person or by phone. They will set it up again at their next login.', $agent_user->name)) ?>"><?= __('Reset Authenticator') ?></a>
                    <?php endif ?>
                </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
</div>
