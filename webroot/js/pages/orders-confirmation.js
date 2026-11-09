/*
 * Confirmation (templates/Orders/confirmation.php): payment method tiles, convenience fee,
 * Grand Total and Pay Now. Values come from #page-data (sgUi.page).
 */
(function (window, $) {
  'use strict';

  var page = window.sgUi ? sgUi.page : {};
  // e2e (ConfirmationPage.providers) reads window.payment_providers.
  window.payment_providers = page.providers || {};

  $(function () {
    var t = sgUi.t;
    var subtotal = Number(page.subtotal) || 0;
    var markups = page.markups || {};
    var providers = page.providers || {};
    var others = page.otherPaymentMethods || [];
    var $form = $('#confirmation-form');
    var $pay = $('#btn-pay-now');
    var $status = $('.pay-status');
    var submitting = false;
    var restorePay = null;

    function roundUp(value, precision) {
      var pow = Math.pow(10, precision);
      return Math.ceil(pow * value) / pow;
    }

    /** Convenience fee of a method for the current subtotal (same rules as before). */
    function feeFor(method) {
      var markup = markups[method];
      if (!markup) return 0;
      var value = Number(markup.value) || 0;
      var add = Number(markup.add) || 0;
      if (markup.type === 'percentage') {
        var provider = providers[method] || null;
        if (provider === '2C2P' || provider === 'Paypal') {
          var total = roundUp((subtotal + add) * (100 / (100 - value)), 2);
          return roundUp(total - subtotal, 2);
        }
        return Math.ceil(subtotal * value) / 100 + add;
      }
      return value + add;
    }

    // Show each method's fee up front.
    $('.pay-method-fee').each(function () {
      var fee = feeFor($(this).data('method'));
      $(this).text(fee > 0 ? t('fee', '+ {fee} fee').replace('{fee}', sgUi.money(fee)) : t('noFee', 'No fee'))
        .toggleClass('has-fee', fee > 0);
    });

    function selected() {
      return $form.find('input[name="pay_by"]:checked').val();
    }

    function refresh() {
      var method = selected();
      $('.pay-method').removeClass('is-selected');
      $('.convenience-fee-row').remove();
      var $grand = $('.grandtotal-row .col.text-end');
      if (!method) {
        $grand.text(sgUi.money(subtotal));
        $pay.prop('disabled', true);
        $('.pay-now-hint').removeClass('d-none');
        return;
      }
      $form.find('input[name="pay_by"]:checked').closest('.pay-method').addClass('is-selected');
      $pay.prop('disabled', false);
      $('.pay-now-hint').addClass('d-none');
      var fee = markups[method] ? feeFor(method) : 0;
      if (markups[method]) {
        var $row = $('<div class="row convenience-fee-row"><div class="col"></div><div class="col convenience-fee text-end"></div></div>');
        $row.find('.col').first().text(t('convenienceFee', 'Convenience Fee'));
        $row.find('.convenience-fee').text(sgUi.money(fee));
        $('.grandtotal-row').before($row);
      }
      $grand.text(sgUi.money(subtotal + fee));
    }

    $form.on('change', 'input[name="pay_by"]', refresh);

    function showStatus(type, message, spinner) {
      $status.empty();
      var $alert = $('<div class="alert my-2 proccessing-message"></div>').addClass('alert-' + type);
      if (spinner) $alert.append('<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>');
      $alert.append(document.createTextNode(message));
      $status.append($alert);
    }

    // Pay Now: guarded against double clicks, but can be retried after a client-side problem.
    $pay.on('click', function () {
      if (submitting) return;
      var method = selected();
      if (method === 'credits') {
        submitting = true;
        restorePay = sgUi.busy($pay);
        showStatus('info', t('processing', 'We are processing your order. Please do not navigate away from this page'), true);
        $form.trigger('submit');
      } else if (method && others.indexOf(method) !== -1) {
        submitting = true;
        restorePay = sgUi.busy($pay);
        showStatus('info', t('redirecting', 'Taking you to the payment page…'), true);
        $form.trigger('submit');
      } else {
        showStatus('warning', t('chooseMethod', 'Please choose a payment method!'));
        $form.find('.pay-methods').get(0).scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });

    // Coming back from a gateway (bfcache): allow paying again.
    window.addEventListener('pageshow', function (e) {
      if (e.persisted) {
        submitting = false;
        if (restorePay) { restorePay(); restorePay = null; }
        $pay.prop('disabled', !selected());
        $status.empty();
      }
    });

    refresh();
  });
})(window, jQuery);
