/*
 * Attraction booking panel (templates/Attractions/view.php): settlement / fdate / fdatetime.
 * Server values come from #page-data (sgUi.page); rows are rendered by sgTickets (ticket-row.js).
 */
$(function () {
  'use strict';

  var page = sgUi.page;
  var urls = page.urls || {};
  var t = sgUi.t;
  var $tickets = $('.tickets');
  var $date = $('#date');
  var $time = $('#time');
  var ticketsRequest = null;

  function resetTickets(message) {
    $tickets.empty();
    if (message) {
      $('<p class="tickets-placeholder"></p>').text(message).appendTo($tickets);
    }
    sgTickets.refreshTotal();
  }

  // ---- Ticket list for a date (and slot) over AJAX. ----
  function loadTickets() {
    if (ticketsRequest) ticketsRequest.abort();
    $tickets.empty().append(sgTickets.skeleton(3));
    ticketsRequest = $.ajax({
      url: urls.tickets,
      type: 'GET',
      data: { date: $date.val(), time: $time.val() },
      dataType: 'JSON'
    }).done(function (res) {
      $tickets.empty();
      if (res.data && res.data.tickets && res.data.tickets.length > 0) {
        var maxQuantities = res.data.tickets_max_quantity || {};
        var slots = res.data.tickets_slots || {};
        res.data.tickets.forEach(function (ticket) {
          var maxQty = maxQuantities[ticket.id];
          if (ticket.max_quantity !== undefined && ticket.max_quantity !== null && maxQty > 0 && maxQty > ticket.max_quantity) {
            maxQty = ticket.max_quantity;
          }
          $tickets.append(sgTickets.renderRow(ticket, {
            maxQty: maxQty,
            remaining: maxQuantities[ticket.id],
            slot: slots[ticket.id],
            canAdd: page.canAdd
          }));
        });
        sgTickets.refreshAllSharedSlots();
      } else {
        $tickets.append(sgTickets.noTickets());
      }
      sgTickets.refreshTotal();
    }).fail(function (xhr, status) {
      if (status === 'abort') return;
      $tickets.empty().append(sgTickets.errorBox(loadTickets));
    }).always(function () {
      ticketsRequest = null;
    });
  }

  // ---- Time slots for a date (fdatetime). ----
  function loadTimes() {
    $time.prop('disabled', true);
    $.ajax({
      url: urls.times,
      type: 'GET',
      data: { date: $date.val() },
      dataType: 'JSON'
    }).done(function (res) {
      $time.empty().append($('<option value=""></option>').text(t('selectTime', 'Select time')));
      resetTickets();
      if (res.data && res.data.length > 0) {
        res.data.forEach(function (slot) {
          $time.append($('<option></option>').attr('value', slot.time_value).text(slot.time_display));
        });
        resetTickets(t('pickTimeFirst', 'Pick a time to see the tickets.'));
      } else {
        $tickets.append(sgTickets.noTickets());
      }
    }).fail(function () {
      $tickets.empty().append(sgTickets.errorBox(loadTimes));
    }).always(function () {
      $time.prop('disabled', false);
    });
  }

  // Supplier availability ping (logged only, as before).
  function checkAvailability() {
    $.ajax({ url: urls.availability, type: 'GET', data: { date: $date.val() }, dataType: 'JSON' })
      .done(function (res) { console.log(res); });
  }

  if (page.pricingType === 'fdatetime') {
    $date.on('change', function () {
      if ($(this).val() !== '') {
        loadTimes();
      } else {
        resetTickets(t('pickDateFirst', 'Pick a date to see the tickets.'));
      }
    });
    $time.on('change', function () {
      if ($(this).val() !== '') {
        loadTickets();
      } else {
        resetTickets(t('pickTimeFirst', 'Pick a time to see the tickets.'));
      }
    });
  }

  if (page.hasApiSku) {
    $date.on('change', function () {
      if ($(this).val() !== '') {
        checkAvailability();
      } else if (page.pricingType !== 'fdatetime') {
        resetTickets();
      }
    });
    if (page.pricingType !== 'fdatetime') {
      $time.on('change', function () {
        if ($(this).val() !== '') loadTickets(); else resetTickets();
      });
    }
  }

  if (page.pricingType === 'fdate') {
    $date.on('change', function () {
      if ($(this).val() !== '') loadTickets();
    });
  }

  // ---- Date picker: only the agent's fixed dates are enabled. ----
  function formatDate(d) {
    var day = String(d.getDate()).padStart(2, '0');
    var month = String(d.getMonth() + 1).padStart(2, '0');
    return day + '-' + month + '-' + d.getFullYear();
  }
  var availableDates = page.availableDates || [];
  if ($date.length && $.fn.datepicker) {
    $date.datepicker({
      format: 'd M yyyy',
      startDate: 'today',
      autoclose: true,
      beforeShowDay: function (date) {
        if (availableDates.length > 0 && availableDates.indexOf(formatDate(date)) < 0) {
          return { enabled: false };
        }
        if (page.noDates) {
          return { enabled: false };
        }
        return { enabled: true };
      }
    });
  }

  // ---- Submit guards. ----
  $('form.application-form').on('click', 'button[type="submit"]', function (e) {
    if (sgTickets.totalQty() <= 0) {
      e.preventDefault();
      sgUi.toast(t('selectAtLeastOne', 'Please select at least 1 to continue.'), 'warning');
      return false;
    }
    if (page.cartHasTwoWay && !window.confirm(t('cartWillBeCleared', 'Cart will be cleared to make this purchase'))) {
      e.preventDefault();
      return false;
    }
  });

  sgTickets.refreshTotal();
  sgTickets.refreshAllSharedSlots();
});
