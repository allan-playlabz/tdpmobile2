<div class="row">
    <div class="col-md-6">
        <?= $this->Form->create(null) ?>
        <fieldset>
            <legend><?= __('Contact Us') ?></legend>
            <?php
                echo $this->Form->control('subject',['class'=>'form-control','value'=>!empty($sent) ? '' : $this->request->getData('subject'), 'label' => __('Subject')]);
                echo $this->Form->control('message',['class'=>'form-control','type'=>'textarea','rows'=>10,'value'=>!empty($sent) ? '' : $this->request->getData('message'), 'label' => __('Message')]);
            ?>
        </fieldset>
        <?= $this->Form->button(__('Send'),['class'=>'btn btn-primary']) ?>
        <?= $this->Form->end() ?>
    </div>
</div>