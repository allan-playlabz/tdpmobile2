
<div style="margin:20px auto;" class="card">
  <div class="card-body">
  <?= $this->Flash->render('flash', ['element'=>'Flash/default']) ?>
  <?= $this->Form->create() ?>
    <!-- <h2 class="form-signin-heading"><img src="/img/winner_logo_header.png"/></h2> -->
    <h3 class="subtitle text-center"><?= __('OTP') ?></h3>
    <p class="form-description" ><?= __('Please enter your One Time Password (OTP) sent to your email.') ?></p>
    <div style="margin-bottom: 24px;">
      <?= $this->Form->control('otp', ['label'=>'', 'class'=>'form-control', 'placeholder'=>__('One Time Password')]) ?>
    </div>
    <?= $this->Form->button(__('LOG IN'),['class'=>'btn btn-primary btn-raised']); ?>
  <?= $this->Form->end() ?>
  <br>
  <?php echo $this->Form->postLink(__('Resend OTP'),['action'=>'resendOtp'],['class'=>'btn btn-primary']) ?>
  </div>
</div>
<div class="timestamp">
  <?php
    $date = new \DateTime();
    $current = $date->format('Y-m-d H:i:s');
    echo $current;
  ?>
</div>
<style>
html, body {
  display: block;
  height: 100%;
}
body {padding-top: 0; padding-bottom:30px;}
.form-description {text-align: center;}
.container {height: 100%;}
.form-signin {height: 100%;}
.timestamp { position: absolute; bottom: 10px; right: 10px; }
</style>