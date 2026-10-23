<div class="text-center box-wrapper">
  <?= $this->Flash->render() ?>
  <?php echo $this->Form->create(null,['class'=>'form-signin form']) ?>
    <h1 class="h3 mb-3 font-weight-normal"><?= __('Agent Login') ?></h1>
    <?php echo $this->Form->control('email',['class'=>'form-control','placeholder'=>__('Email'),'label'=>['class'=>'sr-only']]) ?>
    <?php echo $this->Form->control('password',['class'=>'form-control','placeholder'=>__('Password'),'label'=>['class'=>'sr-only']]) ?>
    <button class="btn btn-lg btn-primary btn-block" type="submit"><?= __('Sign in') ?></button>
    <br>
    <p><?= __('Forgot password') ?>? <?= __('Click') ?> <a href="<?= $this->Url->build(['controller'=>'AgentUsers','action'=>'forgotPassword','prefix'=>false]) ?>"><?= __('here') ?></a> <?= __('to reset') ?>.</p>
    <br>
    <p><?= __('No account yet') ?>? <a href="<?= $this->Url->build(['controller'=>'AgentsSignups','action'=>'add','prefix'=>false]) ?>"><?= __('Signup') ?></a> <?= __('for free') ?>.</p>
  <?php echo $this->Form->end() ?>
</div>
