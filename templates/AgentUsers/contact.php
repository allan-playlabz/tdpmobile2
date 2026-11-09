<?php
/**
 * Contact Us: message form (mailed to Default.contact_us_email) + support details.
 * CSS: theme.css (.page-agent-users-contact). No page JS.
 *
 * @var \App\View\AppView $this
 * @var array $auth_user
 * @var bool|null $sent
 */
use Cake\Core\Configure;

$phone = Configure::read('Site.company_contact_no') ?: Configure::read('Default.company_contact_no');
$email = Configure::read('Default.contact_us_email');
$value = fn(string $field) => !empty($sent) ? '' : (string)$this->request->getData($field);
?>
<div class="contact-head mb-4">
  <h1 class="mb-1"><?= __('Contact Us') ?></h1>
  <p class="text-muted mb-0"><?= __('Questions about a booking, your account or credits? Send us a message and our team will reply to your email.') ?></p>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card sg-card">
      <div class="card-header"><i class="fa fa-envelope-o text-navy" aria-hidden="true"></i><?= __('Send us a message') ?></div>
      <div class="card-body p-4">
        <?= $this->Form->create(null, ['id' => 'contact-form']) ?>
          <div class="mb-3">
            <?= $this->Form->control('subject', [
                'class' => 'form-control',
                'label' => ['text' => __('Subject') . ' <span class="text-danger">*</span>', 'escape' => false, 'class' => 'form-label fw-semibold'],
                'value' => $value('subject'),
                'maxlength' => 150,
                'required' => true,
                'templates' => ['inputContainer' => '{{content}}'],
            ]) ?>
          </div>

          <div class="mb-2">
            <?= $this->Form->control('message', [
                'type' => 'textarea',
                'rows' => 10,
                'class' => 'form-control',
                'label' => ['text' => __('Message') . ' <span class="text-danger">*</span>', 'escape' => false, 'class' => 'form-label fw-semibold'],
                'value' => $value('message'),
                'aria-describedby' => 'message-help',
                'required' => true,
                'templates' => ['inputContainer' => '{{content}}'],
            ]) ?>
            <div id="message-help" class="form-text"><i class="fa fa-lightbulb-o me-1" aria-hidden="true"></i><?= __('Including your order reference helps us respond faster.') ?></div>
          </div>

          <div class="contact-actions">
            <span class="small text-muted"><span class="text-danger">*</span> <?= __('Required fields') ?></span>
            <?= $this->Form->button('<i class="fa fa-paper-plane me-2" aria-hidden="true"></i>' . __('Send message'), [
                'class' => 'btn btn-cta btn-lg',
                'escapeTitle' => false,
            ]) ?>
          </div>
        <?= $this->Form->end() ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <aside class="sg-panel contact-aside p-4">
      <h2 class="h5 mb-3"><?= __('Other ways to reach us') ?></h2>
      <ul class="list-unstyled contact-channels mb-0">
        <?php if ($phone): ?>
        <li>
          <span class="contact-icon"><i class="fa fa-phone" aria-hidden="true"></i></span>
          <div>
            <div class="small text-muted"><?= __('Phone') ?></div>
            <a href="tel:<?= h(preg_replace('/[^\d+]/', '', (string)$phone)) ?>" class="fw-semibold"><?= h($phone) ?></a>
          </div>
        </li>
        <?php endif ?>
        <?php if ($email): ?>
        <li>
          <span class="contact-icon"><i class="fa fa-envelope" aria-hidden="true"></i></span>
          <div class="min-w-0">
            <div class="small text-muted"><?= __('Email') ?></div>
            <a href="mailto:<?= h($email) ?>" class="fw-semibold text-break"><?= h($email) ?></a>
          </div>
        </li>
        <?php endif ?>
        <li>
          <span class="contact-icon"><i class="fa fa-ticket" aria-hidden="true"></i></span>
          <div>
            <div class="small text-muted"><?= __('Looking for a booking?') ?></div>
            <?= $this->Html->link(__('View my orders'), ['controller' => 'Orders', 'action' => 'index'], ['class' => 'fw-semibold']) ?>
          </div>
        </li>
      </ul>

      <hr class="my-4">
      <h3 class="h6 fw-bold mb-2"><?= __('Help us help you') ?></h3>
      <ul class="contact-tips small text-muted mb-0">
        <li><?= __('Order reference and ticket name') ?></li>
        <li><?= __('Visit date and number of guests') ?></li>
        <li><?= __('Screenshots of any error message') ?></li>
      </ul>
    </aside>
  </div>
</div>
