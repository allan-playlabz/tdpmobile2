<?php

use Cake\Core\Configure;
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

$cakeDescription = 'CakePHP: the rapid development php framework';
$available_languages = Configure::read('Translation.languages');
?>
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>
    <meta name="robots" content="noindex, nofollow" />
    <?= $this->Html->css(['bootstrap.min.css', 'bootstrap4-compat.css',
                          'font-awesome.min.css',
                          'simple-sidebar.css',
                          'jsgrid.min.css',
                          'jsgrid-theme.min.css',
                          'admin.css?v=20250829']); ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('internal_css') ?>
    <?= $this->fetch('css') ?>
    <style>
    #announcements_container { overflow: hidden; overflow-y: auto; height: 100%; max-height: 400px; }
    .popover { --bs-popover-header-bg: #006bde; --bs-popover-header-color: #fff; --bs-popover-max-width: 800px; width: 100%; }
    .bs-popover-bottom > .popover-arrow::before, .bs-popover-auto[data-popper-placement^="bottom"] > .popover-arrow::before { border-bottom-color: #006bde; }
    .announcements_unread { font-size: 8px; }
    .form-deferred {
      display: none;
    }
    .form-loader {
      margin-left: 50%;
    }
    </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-light bg-light">
   <div class="container-fluid">
    <a class="navbar-brand py-0" href="<?= $this->Url->build(['controller' => 'Attractions', 'action' => 'index']) ?>"><?= $this->Html->image('site-logo.png', ['height' => '40px']) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav ms-auto">
        <?php if (Configure::read('Features.translation') && !empty($available_languages)) { ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <?= __($available_languages[$this->request->getSession()->read('language') ?? ''] ?? 'English') ?>
          </a>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarLanguageDropdown">
            <?php 
            $query_params = $this->request->getQuery();
            if (isset($query_params['l'])) {
              unset($query_params['l']);
            }
            $default_language_code = Configure::read('Default.language_code');
            $default_language_name = Configure::read('Default.language_name');
            $query_params['l'] = $default_language_code;
            ?>
            <a class="dropdown-item" href="<?= $this->Url->build($this->request->getUri()->getPath()) ?><?= !empty($query_params) ? '?'.http_build_query($query_params) : '' ?>"><?= $default_language_name ?></a>
            <?php 
            foreach($available_languages as $available_language_code => $available_language_name) {
              $query_params['l'] = $available_language_code;
            ?>
              <a class="dropdown-item" href="<?= $this->Url->build($this->request->getUri()->getPath()) ?><?= !empty($query_params) ? '?'.http_build_query($query_params) : '' ?>"><?= $available_language_name ?></a>
            <?php
            }
            ?>
          </div>
        </li>
        <?php } ?>
        <?php if ($auth_user['role'] != 'viewer'): ?>
        <li class="nav-item">
          <div class="nav-link">
            <?= __('Credits') ?>: <?= $this->Number->currency($auth_user['credits']) ?>
            <?= bccomp(strval($auth_user['credits']), strval($auth_user['agent']['low_credit_limit']), 2) !== 1 ? ' <span class="badge rounded-pill text-bg-danger low-credit">LOW</span>' : '' ?>
          </div>
        </li>
        <?php endif ?>
        <?php if ($auth_user['role'] != 'viewer'): ?>
        <li class="nav-item">
          <div class="nav-link" id="read-new" data-bs-container="body" data-bs-toggle="popover" data-bs-placement="bottom" title="<?= __('Announcement') ?> <i class='fa fa-times btn-close-popover float-end'></i>" data-bs-html="true" data-bs-content="<div id='announcements_container'><?php foreach ($announcements as $key => $value) { ?><div class='row'><div class='col-4'><?=$value->publish_start_date->format('d M Y H:i') ?></div><div class='col-8 pe-4'><h6><?= h($value->subject) ?></h6><div><?= str_replace('"', '\'', $value->message)?></div></div></div> <hr /><?php } ?></div>">
            <i class="fa fa-bell-o" aria-hidden="true"></i>
            <?=($auth_user['announcements_read']==0)?'<i class="announcements_unread fa fa-circle text-danger float-end"></i>':''?>
          </div>
        </li>
        <?php endif ?>
        <?php if ($auth_user['role'] != 'viewer'): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= $this->Url->build(['controller'=>'Orders','action'=>'cart']) ?>"><i class="fa fa-shopping-cart" aria-hidden="true"></i><?php if($this->getRequest()->getSession()->check('ShoppingCart')) echo '<div class="badge text-bg-warning float-end">'.count($this->getRequest()->getSession()->read('ShoppingCart')).'</div>'; ?></a>
        </li>
        <?php endif ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <?=$auth_user['name']?>
          </a>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
            <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'AgentUsers','action'=>'view']) ?>"><?= __('My Account') ?></a>
            <?php if ($auth_user['role'] != 'viewer'): ?>
            <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'Orders','action'=>'index']) ?>"><?= __('My Orders') ?></a>
            <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'Orders','action'=>'myTickets']) ?>"><?= __('My Tickets') ?></a>
            <?php endif ?>
            <?php if ($auth_user['role'] == 'master') { ?>
              <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'AgentTopups','action'=>'index']) ?>"> <?= __('Top Up') ?></a>
              <?php if (isset($auth_user['agent']['enable_credit_logs_view']) && $auth_user['agent']['enable_credit_logs_view'] === true) { ?>
                <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'CreditLogs','action'=>'index']) ?>"> <?= __('Balance Report') ?></a>
              <?php } ?>
            <?php } ?>
            <?php if ($auth_user['role'] != 'viewer'): ?>
            <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'Reports','action'=>'index']) ?>"><?= __('Sales Report') ?></a>
            <?php endif ?>
            <div class="dropdown-divider"></div>
            <?php if($auth_user['role']=='master') { ?>
              <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'AgentUsers','action'=>'index']) ?>"><?= __('Agent Users') ?></a>
            <?php } ?>
            <?php if ($auth_user['role'] != 'viewer'): ?>
            <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'AgentUsers','action'=>'contact']) ?>"><?= __('Contact Us') ?></a>
            <?php endif ?>
            <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'AgentUsers','action'=>'twoFactor']) ?>"><?= __('Two-factor sign-in') ?></a>
            <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'AgentUsers','action'=>'logout']) ?>"><?= __('Logout') ?></a>
          </div>
        </li>
      </ul>
    </div>
   </div>
  </nav>
  <?php if ($auth_user['agent'] && !$auth_user['agent']['mobile_otp']) { ?>
  <div id="top_line" class="py-2 text-dark">
      <div class="container text-center" >
          For better security, <a href="<?= $this->Url->build(['controller' => 'AgentUsers', 'action' => 'contact']) ?>">contact us</a> to enable Email OTP for your account.
      </div>
  </div>
  <?php } ?>
  <?php 
  $container_class = 'container';
  if ($this->request->getParam('controller') === 'Attractions' && $this->request->getParam('action') === 'index') {
    $container_class = 'container-fluid';
  }
  ?>
  <div class="<?= $container_class ?> py-4">
      <?= $this->Flash->render() ?>
      <?= $this->fetch('content') ?>
  </div>
  <footer>
  </footer>
  <?= $this->Html->script(['jquery.min.js', 'bootstrap.bundle.min.js', '/plugins/slimScroll/jquery.slimscroll.min.js','app.js']); ?>
  <?= $this->fetch('scriptBottom') ?>
  <?= $this->fetch('script') ?>
  <script>
  $('#read-new').on('shown.bs.popover', function () {
    // send ajax event to server to change it to 1;
    var po = $(this);
    $('.btn-close-popover').on('click',function() {
      po.popover('hide');
    });
    $.ajax({
      url:'<?= $this->Url->build(['controller'=>'AgentUsers','action'=>'read',$auth_user['id']]) ?>',
      type:'POST',
      dataType: "JSON",
      headers :{'X-CSRF-Token':'<?= $this->request->getAttribute('csrfToken')?>'},
    })
    .done(function(res) {
      if (res.status == 200) {
        $('.announcements_unread').hide();
      }
    })
    .fail(function(err) {
      console.log(err);
    });
  });
  function createWatermark(username) {
    const canvas = document.createElement('canvas');
    canvas.width = 400;
    canvas.height = 200;
    const ctx = canvas.getContext('2d');

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.save();
    ctx.translate(canvas.width / 2, canvas.height / 2);
    ctx.rotate(-Math.PI / 6);
    ctx.font = '24px Arial';
    ctx.fillStyle = 'rgba(0, 0, 0, 0.12)';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(username, 0, 0);
    ctx.restore();

    document.body.style.backgroundImage = `url(${canvas.toDataURL()})`;
  }
  $(document).ready(function() {
    $('.form-loader').remove();
    $('.form-deferred').slideDown();
    createWatermark('<?= addslashes($auth_user['agent']['first_name']) . ' ' . addslashes($auth_user['agent']['last_name']) ?>');
  });
  </script>
</body>
</html>
