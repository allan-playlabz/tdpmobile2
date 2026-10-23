<?php 
use Cake\Core\Configure;
?>
<?php $subtotal = 0; ?>
<div id="tickets-sidebar">
<?php if (!empty($tickets)): ?>
  <?php foreach ($tickets as $ticket): ?>
    <div class="card">
      <div class="card-body">
        <div><?php echo $this->Html->link('<div class="font-weight-bold">'.$ticket->attraction->title .'</div> <div class="text-muted">'. $ticket->name.'<br><del>RP '.$this->Number->currency($ticket->retail_price) .'</del></div>',['controller'=>'Attractions','action'=>'view',$ticket->attraction_id],['escape'=>false]) ?></div>
        <div><?php echo $this->Number->currency($ticket->price) ?></div>
        <?php if ($ticket_list[$ticket->id]): ?>
        <?php foreach ($ticket_list[$ticket->id] as $date => $qty): ?>
          <?php $subtotal += ($ticket->price * $qty) ?>
          <div class="row py-2">
            <div class="col-md-6">
              <?php if ($ticket->attraction->pricing_type !='settlement'): ?>
                  <div><?php echo $date ?></div>
                <?php endif ?>
            </div>
            <div class="col-md-2 text-right p-0">
              <span class="py-1"> &times; <?php echo $qty ?> </span>
            </div>
            <div class="col-md-4 text-right"><?php echo $this->Number->currency($ticket->price * $qty) ?></div>
          </div>
        <?php endforeach ?>
        <?php endif ?>
      </div>
    </div>
  <?php endforeach; ?>
  <div class="py-4 px-4 footer-total">
    <div class="row">
      <div class="col"><?= __('Subtotal') ?></div><div class="col text-right"><?= $this->Number->currency($subtotal) ?></div>
    </div>
    <div class="row grandtotal-row">
      <div class="col"><?= __('Grand Total') ?></div><div class="col text-right"><?= $this->Number->currency($subtotal + $other_fees) ?></div>
    </div>
  </div>
<?php endif ?>
</div>
<?php $this->Html->scriptStart(['block' => true]) ?>
    $(window).on('scroll', function (event) {
      var scrollValue = $(window).scrollTop();
      if (scrollValue > 120) {
          if (!$('#tickets-sidebar').hasClass('sticky-top')) {
            $('#tickets-sidebar').addClass('sticky-top');
          } 
      } else{
          $('#tickets-sidebar').removeClass('sticky-top');
      }
    });
<?php $this->Html->scriptEnd() ?>