<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AgentTopup $agentTopup
 */
?>
<div class="row">
    <div class="col-md-6">
        <?= $this->Form->create($agentTopup,['type'=>'file']) ?>
        <fieldset>
            <legend><?= __('New Top Up') ?></legend>
            <div class="text-info"><?= __('All fields are required') ?></div>
            <?php
                echo $this->Form->control('amount',['class'=>'form-control','label' => __('Amount')]);
                echo $this->Form->control('image',['class'=>'form-control','type'=>'file','accept'=>'image/*','label'=>__('Scanned/Photo as proof of payment (allowed types jpg|png|gif|webp)')]);
                echo $this->Form->control('remarks',['class'=>'form-control','type'=>'textarea','maxlength'=>255,'rows'=>3,'label' => __('Remarks')]);
            ?>
        </fieldset>
        <?= $this->Form->button(__('Submit'),['class'=>'btn btn-primary']) ?>
        <?= $this->Form->end() ?>
    </div>
</div>