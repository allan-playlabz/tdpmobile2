<html>
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>
    <meta name="robots" content="noindex, nofollow" />
    <?= $this->Html->css(['bootstrap.min.css',
                          'font-awesome.min.css',
                          'simple-sidebar.css',
                          'jsgrid.min.css',
                          'jsgrid-theme.min.css',
                          'admin.css']); ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('internal_css') ?>
    <?= $this->fetch('css') ?>
</head>
<body>
    <div class="container">
      <div class="row">
        <div class="col-md-8 col-lg-6 offset-md-2 offset-lg-3">
          <?= $this->Flash->render('flash') ?>
          <?= $this->fetch('content') ?>
        </div>
      </div>
    </div>
    <footer>
    </footer>
    <?= $this->Html->script(['jquery.min.js', 'bootstrap.min.js', '/plugins/slimScroll/jquery.slimscroll.min.js']); ?>
  <?= $this->fetch('scriptBottom') ?>
  <?= $this->fetch('script') ?>
</body>
</html>
