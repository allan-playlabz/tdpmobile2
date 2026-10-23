<?php echo $this->Html->link('<i class="fa fa-plus-circle"></i> ' . __('Add User'),['controller'=>'AgentUsers','action'=>'add'],['class'=>'btn btn-primary btn-sm float-right mb-3','escape'=>false]) ?>

<?php $no = 1 ?>
<div class="table-responsive">
  <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <?php // index() is not paginated (PaginatorHelper throws without paging data in 5.x); same links Paginator->sort() rendered in 3.x ?>
          <th scope="col"><?= $this->Html->link(__('Name'), ['?' => ['sort' => 'name', 'direction' => 'asc']]) ?></th>
          <th scope="col"><?= $this->Html->link(__('Email'), ['?' => ['sort' => 'email', 'direction' => 'asc']]) ?></th>
          <th scope="col"><?= $this->Html->link(__('Role'), ['?' => ['sort' => 'role', 'direction' => 'asc']]) ?></th>
          <th scope="col"><?= $this->Html->link(__('Status'), ['?' => ['sort' => 'status', 'direction' => 'asc']]) ?></th>
          <th scope="col"><?= __('Actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($agents as $agent_user): ?>
          <tr>
            <td><?= $agent_user->name ?></td>
                <td><?= h($agent_user->email) ?></td>
                <td><span class="badge <?= ($agent_user->role=='viewer')?'badge-secondary':'badge-info' ?>"><?= __(ucwords($agent_user->role)) ?></span></td>
                <td><span class="badge <?= ($agent_user->status=='active')?'badge-primary':'badge-dark' ?>"><?= __(ucwords($agent_user->status)) ?></span></td>
                <td>
                    <?php echo $this->Html->link('<i class="fa fa-pencil"></i>',['controller' => 'AgentUsers', 'action' => 'edit', $agent_user->id],['escape'=>false,'class'=>'btn btn-primary']) ?>
                    <?php echo $this->Form->postLink(__('Reset Password'),['controller' => 'AgentUsers', 'action' => 'agentResetPassword', $agent_user->id],['escape'=>false,'class'=>'btn btn-primary','confirm'=>__('Are you sure you want to reset password of {0}', $agent_user->name)]) ?>
                </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
</div>
