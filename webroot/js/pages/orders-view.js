/*
 * Order detail (templates/Orders/view.php): while a "Download as Single PDF" merge is in
 * progress, polls VoucherMerging::getStatus and reveals the download link once it is done.
 * The sidebar is sticky via CSS (.sg-sticky-summary).
 */
$(function () {
  'use strict';
  var page = sgUi.page;
  var url = page.urls && page.urls.mergingStatus;
  var done = false;

  function checkMergingStatus() {
    if (!url || $('.voucher-merging-in-progress').length === 0) return;
    $.ajax({
      url: url,
      data: { order_ref_id: page.orderRef },
      type: 'GET',
      dataType: 'JSON'
    }).always(function (res, status, xhr) {
      if (xhr && xhr.status === 200 && res.voucher_merging && res.voucher_merging.status === 'done') {
        $('#download-all').removeClass('d-none');
        $('#voucher-merging-msg').addClass('d-none').removeClass('d-flex');
        $('.alert').remove();
        done = true;
      }
      if (!done) setTimeout(checkMergingStatus, 4000);
    });
  }

  checkMergingStatus();
});
