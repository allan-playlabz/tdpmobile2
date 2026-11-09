<?php
/**
 * Main layout for logged-in agents.
 *
 * No inline <script>/<style> (CSP): page CSS is in webroot/css/theme.css scoped by the body class
 * `page-{controller}-{action}`; page JS is in webroot/js/pages/ and gets server values from
 * $pageData (rendered as a JSON island below), data-* attributes and the csrf-token meta tag.
 *
 * @var \App\View\AppView $this
 * @var array $auth_user
 * @var iterable $announcements
 */

use Cake\Core\Configure;
use Cake\I18n\I18n;
use Cake\I18n\Number;
use Cake\Utility\Inflector;

$available_languages = Configure::read('Translation.languages');
$controller = (string)$this->request->getParam('controller');
$action = (string)$this->request->getParam('action');
$pageClass = 'page-' . Inflector::dasherize($controller) . '-' . Inflector::dasherize($action);
$isViewer = ($auth_user['role'] ?? null) === 'viewer';
$watermark = trim(($auth_user['agent']['first_name'] ?? '') . ' ' . ($auth_user['agent']['last_name'] ?? ''));
$cartCount = $this->getRequest()->getSession()->check('ShoppingCart') ? count((array)$this->getRequest()->getSession()->read('ShoppingCart')) : 0;
?>
<!DOCTYPE html>
<html lang="<?= h(str_replace('_', '-', I18n::getLocale())) ?>">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>
    <meta name="robots" content="noindex, nofollow" />
    <meta name="csrf-token" content="<?= h((string)$this->request->getAttribute('csrfToken')) ?>">
    <?= $this->Html->css(['bootstrap.min.css', 'bootstrap4-compat.css',
                          'font-awesome.min.css',
                          'simple-sidebar.css',
                          'jsgrid.min.css',
                          'jsgrid-theme.min.css',
                          'admin.css',
                          'theme.css']); ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('internal_css') ?>
    <?= $this->fetch('css') ?>
</head>
<body class="<?= h($pageClass) ?>"
      data-currency="<?= h(Number::getDefaultCurrency()) ?>"
      data-locale="<?= h(str_replace('_', '-', I18n::getLocale())) ?>"
      data-watermark="<?= h($watermark) ?>">
  <nav class="navbar navbar-expand-lg sg-navbar">
   <div class="container-fluid">
    <a class="navbar-brand py-0" href="<?= $this->Url->build(['controller' => 'Attractions', 'action' => 'index']) ?>"><?= $this->Html->image('site-logo.png', ['alt' => 'SG Attractions']) ?></a>
    <?php if (!$isViewer): ?>
    <?php /* One cart link (e2e reads its badge): next to the toggler on phones, last item on desktop. */ ?>
    <a class="nav-link sg-cart-link order-lg-last ms-auto ms-lg-3 me-3 me-lg-1" href="<?= $this->Url->build(['controller' => 'Orders', 'action' => 'cart']) ?>" aria-label="<?= __('Cart') ?>">
      <i class="fa fa-shopping-cart fa-lg" aria-hidden="true"></i>
      <?php if ($cartCount): ?><span class="badge text-bg-warning sg-cart-count"><?= $cartCount ?></span><?php endif ?>
    </a>
    <?php endif ?>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="<?= __('Toggle navigation') ?>">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <?php if (Configure::read('Features.translation') && !empty($available_languages)) { ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarLanguageDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-globe" aria-hidden="true"></i>
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
        <?php if (!$isViewer): ?>
        <li class="nav-item">
          <div class="nav-link">
            <span class="sg-credits">
              <i class="fa fa-credit-card text-navy" aria-hidden="true"></i>
              <?php if (bccomp(strval($auth_user['credits']), strval($auth_user['agent']['low_credit_limit']), 2) !== 1): ?><span class="badge rounded-pill text-bg-danger low-credit">LOW</span><?php endif ?>
              <span><?= __('Credits') ?>: <span class="sg-credits-amount"><?= $this->Number->currency($auth_user['credits']) ?></span></span>
            </span>
          </div>
        </li>
        <li class="nav-item">
          <div class="nav-link sg-bell" id="read-new" role="button" tabindex="0" aria-label="<?= __('Announcement') ?>"
               data-read-url="<?= $this->Url->build(['controller' => 'AgentUsers', 'action' => 'read', $auth_user['id']]) ?>"
               data-bs-container="body" data-bs-toggle="popover" data-bs-placement="bottom"
               title="<?= __('Announcement') ?> <i class='fa fa-times btn-close-popover float-end'></i>" data-bs-html="true"
               data-bs-content="<div id='announcements_container'><?php foreach ($announcements as $key => $value) { ?><div class='row'><div class='col-4 small text-muted'><?=$value->publish_start_date->format('d M Y H:i') ?></div><div class='col-8 pe-4'><h6><?= h($value->subject) ?></h6><div><?= str_replace('"', '\'', $value->message)?></div></div></div> <hr /><?php } ?></div>">
            <i class="fa fa-bell-o fa-lg" aria-hidden="true"></i>
            <?= ($auth_user['announcements_read'] == 0) ? '<i class="announcements_unread fa fa-circle text-danger" aria-hidden="true"></i>' : '' ?>
          </div>
        </li>
        <?php endif ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-user-circle-o" aria-hidden="true"></i>
            <?= h($auth_user['name']) ?>
          </a>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
            <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'AgentUsers','action'=>'view']) ?>"><?= __('My Account') ?></a>
            <?php if (!$isViewer): ?>
            <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'Orders','action'=>'index']) ?>"><?= __('My Orders') ?></a>
            <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'Orders','action'=>'myTickets']) ?>"><?= __('My Tickets') ?></a>
            <?php endif ?>
            <?php if ($auth_user['role'] == 'master') { ?>
              <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'AgentTopups','action'=>'index']) ?>"> <?= __('Top Up') ?></a>
              <?php if (isset($auth_user['agent']['enable_credit_logs_view']) && $auth_user['agent']['enable_credit_logs_view'] === true) { ?>
                <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'CreditLogs','action'=>'index']) ?>"> <?= __('Balance Report') ?></a>
              <?php } ?>
            <?php } ?>
            <?php if (!$isViewer): ?>
            <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'Reports','action'=>'index']) ?>"><?= __('Sales Report') ?></a>
            <?php endif ?>
            <div class="dropdown-divider"></div>
            <?php if($auth_user['role']=='master') { ?>
              <a class="dropdown-item" href="<?= $this->Url->build(['controller'=>'AgentUsers','action'=>'index']) ?>"><?= __('Agent Users') ?></a>
            <?php } ?>
            <?php if (!$isViewer): ?>
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
      <div class="container text-center">
          <i class="fa fa-shield text-navy me-1" aria-hidden="true"></i>
          For better security, <a href="<?= $this->Url->build(['controller' => 'AgentUsers', 'action' => 'contact']) ?>">contact us</a> to enable Email OTP for your account.
      </div>
  </div>
  <?php } ?>
  <?php
  $container_class = 'container';
  if ($controller === 'Attractions' && $action === 'index') {
    $container_class = 'container-fluid px-lg-4';
  }
  ?>
  <main class="<?= $container_class ?> py-4">
      <?= $this->Flash->render() ?>
      <?= $this->fetch('content') ?>
  </main>
  <footer>
  </footer>
  <?php if (!empty($pageData)): ?>
  <script type="application/json" id="page-data"><?= json_encode($pageData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
  <?php endif ?>
  <?= $this->Html->script(['jquery.min.js', 'bootstrap.bundle.min.js', '/plugins/slimScroll/jquery.slimscroll.min.js', 'app.js']); ?>
  <?= $this->fetch('scriptBottom') ?>
  <?= $this->fetch('script') ?>
</body>
</html>
