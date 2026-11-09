/*
 * Attraction booking panel helpers, shared by Attractions/view and Attractions/view_two_way.
 *
 *  - sgTickets.renderRow(ticket, opts): the same markup as templates/element/ticket_row.php,
 *    for tickets loaded over AJAX (keep both in sync).
 *  - Quantity steppers (+ / − / typed value) via delegated handlers — one binding for
 *    server-rendered and AJAX rows. Two-way depart inputs mirror into their return leg
 *    (data-return-ticket-id). Shared-slot pools cap sibling tickets (data-slot-id/-pool).
 *  - Total + call-to-action state: the buttons are always shown, disabled until a quantity is picked.
 */
(function (window, $) {
  'use strict';

  var t = function (key, fallback) { return window.sgUi ? sgUi.t(key, fallback) : fallback; };
  var money = function (n) { return window.sgUi ? sgUi.money(n) : Number(n).toFixed(2); };
  var esc = function (s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&(?!(#\d+|#x[0-9a-f]+|[a-z]+);)/gi, '&amp;')
      .replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  };
  var num = function (v, fallback) { var n = parseInt(v, 10); return isNaN(n) ? fallback : n; };

  var sgTickets = {
    /** Called after any quantity change (set by the page script). */
    onChange: function () {},

    /**
     * ticket: JSON row from get-tickets / get-two-way-tickets.
     * opts: { maxQty, slot, canAdd, readonly (return leg), returnTicketId, inputId, details }
     */
    renderRow: function (ticket, opts) {
      opts = opts || {};
      var details = opts.details !== false;
      var maxQty = opts.maxQty;
      var soldOut = maxQty === undefined || maxQty === null || Number(maxQty) === 0;
      var html = '<div class="ticket-option">';
      html += '<div class="ticket-info"><b class="ticket-name">' + esc(ticket.name) + '</b>';
      if (details && ticket.sku_id) {
        html += '<div class="ticket-sku text-10">' + esc(ticket.sku_id) + '</div>';
      }
      html += '</div>';
      html += '<div class="ticket-buy"><div class="ticket-price">';
      html += '<div class="price-net price-net-sm">' + money(ticket.price) + '</div>';
      if (details) {
        var refs = [];
        if (ticket.retail_price !== undefined && ticket.retail_price !== null) {
          refs.push('<del><abbr title="' + esc(t('retailPrice', 'Retail price')) + '">RP</abbr> ' + money(ticket.retail_price) + '</del>');
        }
        if (ticket.minimum_selling_price) {
          refs.push('<abbr title="' + esc(t('minimumSellingPrice', 'Minimum selling price')) + '">MSP</abbr> ' + money(ticket.minimum_selling_price));
        }
        if (refs.length) html += '<div class="price-ref">' + refs.join('<br>') + '</div>';
      }
      html += '</div><div class="ticket-qty">';
      if (opts.canAdd !== false) {
        var disabled = opts.readonly ? ' disabled' : '';
        var attrs = ' name="quantity[' + esc(ticket.id) + ']" value="0" inputmode="numeric"' +
          ' aria-label="' + esc(t('quantity', 'Quantity') + ' ' + String(ticket.name).replace(/<[^>]*>/g, '')) + '"' +
          ' data-price="' + esc(ticket.price) + '"' +
          ' max="' + esc(soldOut ? 0 : maxQty) + '"' +
          ' min="' + esc(ticket.min_quantity === null || ticket.min_quantity === undefined ? 0 : ticket.min_quantity) + '"';
        if (opts.inputId) attrs += ' id="' + esc(opts.inputId) + '"';
        if (opts.returnTicketId !== undefined) attrs += ' data-return-ticket-id="' + esc(opts.returnTicketId) + '"';
        if (opts.readonly) attrs += ' readonly';
        if (opts.slot) {
          attrs += ' data-slot-id="' + esc(opts.slot.slot_id) + '" data-slot-pool="' + esc(opts.slot.pool) + '"';
          if (ticket.max_quantity !== undefined && ticket.max_quantity !== null) {
            attrs += ' data-max-qty="' + esc(ticket.max_quantity) + '"';
          }
        }
        html += '<div class="input-group sg-stepper">' +
          '<button class="btn btn-outline-secondary btn-subtract" type="button" aria-label="' + esc(t('less', 'Less')) + '"' + disabled + '><i class="fa fa-minus" aria-hidden="true"></i></button>' +
          '<input type="text" class="form-control"' + attrs + '>' +
          '<button class="btn btn-outline-secondary btn-add" type="button" aria-label="' + esc(t('more', 'More')) + '"' + disabled + '><i class="fa fa-plus" aria-hidden="true"></i></button>' +
          '</div>';
        if (soldOut) {
          html += '<div class="sold-out text-danger fw-bold">' + esc(t('soldOut', 'Sold out')) + '</div>';
        } else if (ticket.show_stock) {
          html += '<div class="remaining-count">Remaining: ' + esc(opts.remaining !== undefined ? opts.remaining : maxQty) + '</div>';
        }
        if (opts.readonly) {
          html += '<div class="small text-muted mt-1">' + esc(t('matchesDeparture', 'Matches departure quantity')) + '</div>';
        }
      }
      html += '</div></div>';
      if (ticket.agent_notification) {
        html += '<div class="ticket-note">' + ticket.agent_notification + '</div>';
      }
      html += '</div>';
      return $(html);
    },

    skeleton: function (rows) {
      var html = '<div class="ticket-skeleton" aria-hidden="true">';
      for (var i = 0; i < (rows || 3); i++) html += '<span class="skeleton skeleton-row"></span>';
      return $(html + '</div>');
    },

    /** Inline error with a retry button (AJAX failures used to fail silently). */
    errorBox: function (retry) {
      var $box = $('<div class="alert alert-warning d-flex align-items-center gap-2 ticket-load-error" role="alert">' +
        '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i><div class="flex-grow-1"></div></div>');
      $box.find('.flex-grow-1').text(t('loadFailed', 'Tickets could not be loaded.'));
      if (retry) {
        $('<button type="button" class="btn btn-sm btn-outline-primary"></button>')
          .text(t('retry', 'Retry')).on('click', retry).appendTo($box);
      }
      return $box;
    },

    noTickets: function () {
      return $('<div class="alert alert-danger"></div>').text(t('noTickets', 'No tickets found!'));
    },

    inputs: function () {
      return $('form.application-form input[name^="quantity"]');
    },

    totalQty: function () {
      var total = 0;
      sgTickets.inputs().each(function () { total += num($(this).val(), 0); });
      return total;
    },

    totalPrice: function () {
      var total = 0;
      sgTickets.inputs().each(function () {
        var qty = num($(this).val(), 0);
        if (qty > 0) total += parseFloat($(this).data('price')) * qty;
      });
      return total;
    },

    /** Writes the total and enables / disables the call-to-action buttons. */
    refreshTotal: function () {
      var total = sgTickets.totalPrice();
      var qty = sgTickets.totalQty();
      var $total = $('.total');
      $total.empty()
        .append($('<strong></strong>').text(t('total', 'Total') + ':'))
        .append(' ')
        .append($('<span class="total-amount"></span>').text(money(total)));
      $('.sg-mobile-bar-amount').text(money(total));
      var none = qty <= 0;
      $('#btn-add-to-cart, #btn-buy-now').prop('disabled', none);
      $('.cta-hint').toggleClass('d-none', !none);
      return total;
    },

    /** Sets a quantity; a two-way depart leg mirrors into its return leg. */
    setQty: function ($input, qty) {
      $input.val(qty);
      var returnId = $input.attr('data-return-ticket-id');
      if (returnId && returnId !== 'undefined') {
        $('#qty-' + returnId).val(qty);
      }
    },

    /** Shared-slot pool: siblings with the same data-slot-id draw from one pool. */
    refreshSharedSlot: function (slotId) {
      if (!slotId) return;
      var $group = $('input[data-slot-id="' + slotId + '"]');
      if (!$group.length) return;
      var pool = num($group.first().attr('data-slot-pool'), 0);
      var sum = 0;
      $group.each(function () { sum += num($(this).val(), 0); });
      $group.each(function () {
        var $input = $(this);
        var allowed = pool - (sum - num($input.val(), 0));
        var maxQty = parseInt($input.attr('data-max-qty'), 10);
        if (!isNaN(maxQty)) allowed = Math.min(allowed, maxQty);
        if (allowed < 0) allowed = 0;
        $input.attr('max', allowed);
        $input.closest('.ticket-qty').find('.remaining-count').text('Remaining: ' + (pool - sum));
      });
    },

    refreshAllSharedSlots: function () {
      var seen = {};
      $('input[data-slot-id]').each(function () {
        var id = $(this).attr('data-slot-id');
        if (id && !seen[id]) { seen[id] = true; sgTickets.refreshSharedSlot(id); }
      });
    },

    changed: function ($input) {
      sgTickets.refreshSharedSlot($input.attr('data-slot-id'));
      sgTickets.refreshTotal();
      sgTickets.onChange($input);
    }
  };

  // ---- Delegated stepper handlers (bound once for every row, present or future). ----
  $(document).on('click', 'form.application-form .btn-add', function () {
    var $input = $(this).closest('.input-group').find('input');
    if ($input.prop('readonly')) return;
    var qty = num($input.val(), 0);
    var max = parseInt($input.attr('max'), 10);
    if (!isNaN(max) && qty >= max) return;
    var min = num($input.attr('min'), 0) > 1 ? num($input.attr('min'), 0) : 1;
    sgTickets.setQty($input, qty + 1 >= min ? qty + 1 : min);
    sgTickets.changed($input);
  });

  $(document).on('click', 'form.application-form .btn-subtract', function () {
    var $input = $(this).closest('.input-group').find('input');
    if ($input.prop('readonly')) return;
    var qty = num($input.val(), 0);
    var min = num($input.attr('min'), 0) > 1 ? num($input.attr('min'), 0) : 0;
    if (qty > 0) {
      var next = qty - 1;
      if (min > 0 && next < min) next = 0;
      sgTickets.setQty($input, next);
    }
    sgTickets.changed($input);
  });

  $(document).on('change', 'form.application-form input[name^="quantity"]', function () {
    var $input = $(this);
    var qty = num($input.val(), 0);
    var min = num($input.attr('min'), 0) > 1 ? num($input.attr('min'), 0) : 0;
    if (qty > 0 && qty < min) qty = min;
    var max = parseInt($input.attr('max'), 10);
    if (!isNaN(max) && qty > max) qty = max;
    if (qty < 0) qty = 0;
    sgTickets.setQty($input, qty);
    sgTickets.changed($input);
  });

  // Mobile bar button: scroll to the panel until something is selected, then submit Buy Now.
  $(document).on('click', '.sg-mobile-bar [data-mobile-cta]', function () {
    var $buy = $('#btn-buy-now');
    if ($buy.length && !$buy.prop('disabled')) {
      $buy.trigger('click');
    } else {
      var panel = document.getElementById('options');
      if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });

  // Photo gallery (element/attraction_detail.php): Lightbox2 binds [data-lightbox] links itself;
  // hidden "+N" thumbnails stay in the album. Only the options are set here.
  $(function () {
    if (!window.lightbox || typeof window.lightbox.option !== 'function') return;
    window.lightbox.option({
      albumLabel: $('.attraction-gallery').attr('data-album-label') || 'Photo %1 of %2',
      wrapAround: true,
      disableScrolling: true,
      fadeDuration: 200,
      imageFadeDuration: 200,
      sanitizeTitle: true,
      resizeDuration: 200
    });
  });

  // Gallery thumbnails: show only the ones that fit one row; the rest are hidden (still reachable in the
  // modal carousel) and the last visible one gets a "+N" overlay. Classes/data attributes only (CSP).
  function layoutThumbs(row) {
    var thumbs = Array.prototype.slice.call(row.querySelectorAll('.gallery-thumb-link'));
    if (!thumbs.length) return;
    thumbs.forEach(function (t) {
      t.classList.remove('gallery-thumb-hidden', 'gallery-thumb-more');
      t.removeAttribute('data-more');
      if (!t.hasAttribute('data-label')) t.setAttribute('data-label', t.getAttribute('aria-label') || '');
      t.setAttribute('aria-label', t.getAttribute('data-label'));
    });
    var style = window.getComputedStyle(row);
    var gap = parseFloat(style.columnGap || style.gap) || 8;
    var available = row.clientWidth;
    var used = 0, fit = 0;
    for (var i = 0; i < thumbs.length; i++) {
      var add = (i === 0 ? 0 : gap) + thumbs[i].offsetWidth;
      if (used + add > available) break;
      used += add;
      fit++;
    }
    if (fit >= thumbs.length) return;
    fit = Math.max(fit, 1);
    for (var j = fit; j < thumbs.length; j++) thumbs[j].classList.add('gallery-thumb-hidden');
    var last = thumbs[fit - 1];
    last.classList.add('gallery-thumb-more');
    last.setAttribute('data-more', String(thumbs.length - fit));
    last.setAttribute('aria-label', last.getAttribute('data-label') + ' (+' + (thumbs.length - fit) + ')');
  }
  $(function () {
    var rows = document.querySelectorAll('.gallery-thumbnails-row');
    if (!rows.length) return;
    var run = function () { Array.prototype.forEach.call(rows, layoutThumbs); };
    run();
    var timer;
    window.addEventListener('resize', function () {
      clearTimeout(timer);
      timer = setTimeout(run, 150);
    });
  });

  window.sgTickets = sgTickets;
})(window, jQuery);
