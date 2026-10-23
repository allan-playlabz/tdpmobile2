<li class="nav-item">
  <a class="nav-link" href="<?php $this->Url->build(['controller'=>'CreditLogs','action'=>'index']) ?>">Credits: <?= $this->Number->currency($auth_user['credits']) ?></a>
</li>
<li class="nav-item">
  <div class="nav-link" id="read-new" data-container="body" data-toggle="popover" data-placement="bottom" title="Announcement <i class='fa fa-times btn-close-popover float-right'></i>" data-html="true" data-content="<div id='announcements_container'><?php foreach ($announcements as $key => $value) { ?><div class='row'><div class='col-4'><?=$value->publish_start_date->format('d M Y H:i') ?></div><div class='col-8 pr-4'><h6><?= h($value->subject) ?></h6><div><?= str_replace('"', '\'', $value->message)?></div></div></div> <hr /><?php } ?></div>">
    <i class="fa fa-bell-o" aria-hidden="true"></i>
    <?=($auth_user['announcements_read']==0)?'<i class="announcements_unread fa fa-circle text-danger float-right"></i>':''?>
  </div>
</li>
