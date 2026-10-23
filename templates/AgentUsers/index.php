<?php echo $this->Html->link('<i class="fa fa-plus-circle"></i> ' . __('Add User'),['controller'=>'AgentUsers','action'=>'add'],['class'=>'btn btn-primary btn-sm float-right mb-3','escape'=>false]) ?>

<?php $no = 1 ?>
<div class="table-responsive">
  <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th scope="col"><?= $this->Paginator->sort('name', __('Name')) ?></th>
          <th scope="col"><?= $this->Paginator->sort('email', __('Email')) ?></th>
          <th scope="col"><?= $this->Paginator->sort('role', __('Role')) ?></th>
          <th scope="col"><?= $this->Paginator->sort('status', __('Status')) ?></th>
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
