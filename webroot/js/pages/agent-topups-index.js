/*
 * Top Up list (templates/AgentTopups/index.php). Copying the bank reference is done by app.js
 * (data-copy-target); this flashes the button label as before.
 */
$(function () {
  'use strict';
  if ($.fn.datepicker) {
    $('.datepicker').datepicker({ format: 'd M yyyy', endDate: 'today' });
  }
  $('#copyReferenceBtn').on('click', function () {
    var $btn = $(this);
    var original = $btn.html();
    $btn.html('<i class="fa fa-check"></i> ').append(document.createTextNode($btn.attr('data-copied') || 'Copied'));
    setTimeout(function () { $btn.html(original); }, 1500);
  });
});
