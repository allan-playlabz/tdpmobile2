/*
 * Thank you (templates/Orders/thank_you.php) while the order is still processing: polls
 * /orders/get-status with a slowly growing interval and reloads the page once the order is done.
 * After ~30 checks the message explains the longer supplier confirmation and repeats the ref.
 */
$(function () {
  'use strict';

  var page = sgUi.page;
  var urls = page.urls || {};
  if (!urls.status) return;
  var count = 0;
  var frequency = 1000;
  var checking = false;

  function longWait() {
    var html = sgUi.t('completing', '') + $('<div>').text(page.orderRef || '').html() + sgUi.t('completingEnd', '');
    // Translated server-side copy (contains <p> tags); the ref is escaped above.
    $('.processing-message .processing-text').html(html);
  }

  function checkStatus() {
    if (checking) return;
    checking = true;
    count++;
    frequency += 100;
    $.ajax({ url: urls.status, type: 'GET', dataType: 'JSON' })
      .always(function (res, status, xhr) {
        checking = false;
        xhr = xhr && xhr.status !== undefined ? xhr : res; // .always gets (xhr, status, error) on failure
        var code = xhr && xhr.status;
        if (code === 200 && res && res.status == '1000') {
          if (res.data.order && res.data.order.status === 'done') {
            window.location = urls.thankYou;
            return;
          }
          if (count > 30) longWait();
          setTimeout(checkStatus, frequency);
        } else if (code === 404 || (res && res.status == 404) || code === 403) {
          window.location = urls.home;
        } else {
          setTimeout(checkStatus, frequency);
        }
      });
  }

  checkStatus();
});
