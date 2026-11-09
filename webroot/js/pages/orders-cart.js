/*
 * Cart (templates/Orders/cart.php): quantity steppers, line totals and the order total.
 * − stops at 1 (removing is the trash button); a typed value only has to respect the ticket minimum.
 * Quantities are POSTed with Checkout.
 */
$(function () {
  'use strict';

  var $form = $('#cart-form');
  if (!$form.length) return;
  var t = sgUi.t;
  var num = function (v, d) { var n = parseInt(v, 10); return isNaN(n) ? d : n; };

  function refresh() {
    var total = 0;
    var count = 0;
    $form.find('input[name^="quantity"]').each(function () {
      var $input = $(this);
      var qty = num($input.val(), 0);
      var lineTotal = qty > 0 ? parseFloat($input.data('price')) * qty : 0;
      $input.closest('.cart-line').find('.row-total').text(sgUi.money(lineTotal));
      total += lineTotal;
      count += Math.max(qty, 0);
    });
    $form.find('.total').empty()
      .append($('<strong></strong>').text(t('total', 'Total')))
      .append(': ')
      .append($('<span class="total-amount"></span>').text(sgUi.money(total)));
    $form.find('.cart-item-count').text(count);
    $('.sg-mobile-bar-amount').text(sgUi.money(total));
  }

  $form.on('click', '.btn-add', function () {
    var $input = $(this).closest('.input-group').find('input');
    var qty = num($input.val(), 0);
    var max = parseInt($input.attr('max'), 10);
    if (!isNaN(max) && qty >= max) return;
    var min = num($input.attr('min'), 0) > 1 ? num($input.attr('min'), 0) : 1;
    $input.val(qty + 1 >= min ? qty + 1 : min);
    refresh();
  });

  $form.on('click', '.btn-subtract', function () {
    var $input = $(this).closest('.input-group').find('input');
    var qty = num($input.val(), 0);
    var min = num($input.attr('min'), 0) > 1 ? num($input.attr('min'), 0) : 0;
    if (qty > 1) {
      var next = qty - 1;
      if (min > 0 && next < min) next = min;
      $input.val(next);
      refresh();
    }
  });

  $form.on('change', 'input[name^="quantity"]', function () {
    var $input = $(this);
    var qty = Math.max(num($input.val(), 0), 0);
    var min = num($input.attr('min'), 0) > 1 ? num($input.attr('min'), 0) : 0;
    if (min > 0 && qty < min) qty = min;
    var max = parseInt($input.attr('max'), 10);
    if (!isNaN(max) && qty > max) qty = max;
    $input.val(qty);
    refresh();
  });

  // Prevent double submits of Checkout.
  $form.on('submit', function () {
    var $btn = $('#btn-checkout');
    setTimeout(function () { sgUi.busy($btn); }, 0);
  });

  refresh();
});
