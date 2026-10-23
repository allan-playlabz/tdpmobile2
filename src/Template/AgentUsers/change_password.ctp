<div class="row">
    <div class="col">
        <h3><?= __('Change Password') ?></h3>
    </div>
</div>
<?= $this->Form->create(null) ?>
<div class="row">
    <div class="col-md-8">
        <fieldset>
            <?php
                echo $this->Form->control('old_password',['type'=>'password','class'=>'form-control']);
                echo $this->Form->control('password',['class'=>'form-control']);
                echo $this->Form->control('confirm_password',['type'=>'password','class'=>'form-control']);
            ?>
        </fieldset>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <?= __('Actions') ?>
            </div>
            <div class="card-body">
                <?= $this->Form->button(__('Save'),['class'=>'btn btn-primary']) ?>
            </div>
        </div>
    </div>
</div>
<?= $this->Form->end() ?>