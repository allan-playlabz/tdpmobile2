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
<?php $pageClass = 'page-' . \Cake\Utility\Inflector::dasherize((string)$this->request->getParam('controller')) . '-' . \Cake\Utility\Inflector::dasherize((string)$this->request->getParam('action')); ?>
<body class="auth-body <?= h($pageClass) ?>">
<div class="container py-4">
    <div class="row">
        <div class="col-md-10 col-lg-8 offset-md-1 offset-lg-2">
            <?= $this->Html->image('site-logo.png', ['alt' => 'SG Attractions', 'class' => 'auth-logo']) ?>
            <?= $this->Flash->render('flash') ?>
            <?= $this->fetch('content') ?>
        </div>
    </div>
</div>
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
