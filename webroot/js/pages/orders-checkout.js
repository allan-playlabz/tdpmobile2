/*
 * Checkout (templates/Orders/checkout.php): date pickers, inline validation styling,
 * "copy name from customer details" for the first traveller, and a double-submit guard.
 * Browser validation (required / pattern) still decides; this only makes the errors visible.
 */
$(function () {
  'use strict';

  var $form = $('#checkout-form');
  if (!$form.length) return;

  // ---- Date pickers (format d M yyyy, as the server expects). ----
  if ($.fn.datepicker) {
    var dobEnd = new Date();
    dobEnd.setMonth(dobEnd.getMonth() - 1);
    var expiryStart = new Date();
    expiryStart.setMonth(expiryStart.getMonth() + 1);
    $('.dob').datepicker({ format: 'd M yyyy', endDate: dobEnd, autoclose: true });
    $('.date-picker').datepicker({ format: 'd M yyyy', autoclose: true });
    $('.passport-expiry').datepicker({ format: 'd M yyyy', startDate: expiryStart, autoclose: true });
    $('.datepicker').datepicker({ format: 'd M yyyy', startDate: 'today', autoclose: true });
    $('.timepicker').datepicker({ format: 'hh mm', autoclose: true });
  }

  // ---- Validation UI: highlight invalid fields once the browser rejects the submit. ----
  var warned = false;
  $form[0].addEventListener('invalid', function (e) {
    $form.addClass('was-validated');
    if (!warned) {
      warned = true;
      sgUi.toast(sgUi.t('fixErrors', 'Please check the highlighted fields.'), 'warning');
      setTimeout(function () { warned = false; }, 4000);
    }
    var field = e.target;
    var $group = $(field).closest('.form-group, .input, [class*="col-"]');
    if ($group.length && !$group.find('.invalid-feedback').length && field.validationMessage) {
      $('<div class="invalid-feedback"></div>').text(field.validationMessage).insertAfter(field);
    }
  }, true);
  $form.on('input change', 'input, select, textarea', function () {
    var $fb = $(this).siblings('.invalid-feedback');
    if (this.validity && this.validity.valid) {
      $fb.remove();
    } else if ($fb.length && this.validationMessage) {
      $fb.text(this.validationMessage);
    }
  });

  // ---- First traveller: copy the customer's name. ----
  $form.on('click', '.copy-customer', function () {
    var $traveller = $(this).closest('.traveller');
    $traveller.find('.traveller-first-name').val($form.find('input[name="first_name"]').val()).trigger('change');
    $traveller.find('.traveller-last-name').val($form.find('input[name="last_name"]').val()).trigger('change');
    sgUi.toast(sgUi.t('copied', 'Copied from customer details'), 'success');
  });

  // ---- Double-submit guard (only once the browser accepted the form). ----
  $form.on('submit', function () {
    var $btn = $('#btn-continue');
    setTimeout(function () { sgUi.busy($btn); }, 0);
  });
});
