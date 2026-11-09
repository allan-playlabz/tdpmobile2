/*
 * Two-way attraction booking panel (templates/Attractions/view_two_way.php).
 * Departure + return date/slot → depart and return tickets; the return leg quantity follows
 * its depart ticket (data-return-ticket-id, handled by sgTickets.setQty).
 */
$(function () {
  'use strict';

  var page = sgUi.page;
  var urls = page.urls || {};
  var t = sgUi.t;
  var $depart = $('.tickets .depart-tickets');
  var $return = $('.tickets .return-tickets');
  var request = null;

  function clearTickets() {
    $depart.empty();
    $return.empty();
    sgTickets.refreshTotal();
  }

  function placeholder() {
    clearTickets();
    $('<p class="tickets-placeholder"></p>').text(t('pickTripFirst', 'Pick the departure and return times to see the tickets.')).appendTo($depart);
  }

  function showNoTickets() {
    $depart.find('.alert-danger').remove();
    $return.find('.alert-danger').remove();
    $depart.append(sgTickets.noTickets());
    $return.append(sgTickets.noTickets());
  }

  function loadTickets() {
    if (request) request.abort();
    clearTickets();
    $depart.append(sgTickets.skeleton(2));
    $return.append(sgTickets.skeleton(2));
    request = $.ajax({
      url: urls.tickets,
      type: 'GET',
      data: {
        departure_date: $('#departure-date').val(),
        departure_time: $('#departure-time').val(),
        return_date: $('#return-date').val(),
        return_time: $('#return-time').val()
      },
      dataType: 'JSON'
    }).done(function (res) {
      clearTickets();
      if (res.data && res.data.tickets && res.data.tickets.length > 0) {
        var maxQuantities = res.data.tickets_max_quantity || {};
        var returnIds = res.data.return_ticket_ids || {};
        res.data.tickets.forEach(function (ticket) {
          var maxQty = maxQuantities[ticket.id];
          if (ticket.max_quantity !== undefined && ticket.max_quantity !== null && maxQty > 0 && maxQty > ticket.max_quantity) {
            maxQty = ticket.max_quantity;
          }
          // Depart tickets are the keys of return_ticket_ids; return legs are read-only and follow them.
          var isDepartLeg = Object.prototype.hasOwnProperty.call(returnIds, ticket.id);
          var $row = sgTickets.renderRow(ticket, {
            maxQty: maxQty,
            canAdd: page.canAdd,
            readonly: !isDepartLeg,
            inputId: 'qty-' + ticket.id,
            returnTicketId: returnIds[ticket.id]
          });
          var trip = ticket.ticket_trip_type && ticket.ticket_trip_type.type;
          (trip === 'depart' ? $depart : $return).append($row);
        });
      } else {
        showNoTickets();
      }
      sgTickets.refreshTotal();
    }).fail(function (xhr, status) {
      if (status === 'abort') return;
      clearTickets();
      $depart.append(sgTickets.errorBox(loadTickets));
    }).always(function () {
      request = null;
    });
  }

  function populateTimings(type, res) {
    var $select = $('#' + type + '-time');
    $select.empty().append($('<option value=""></option>').text(t('selectTime', 'Select time')));
    clearTickets();
    if (res.data && res.data.length > 0) {
      res.data.forEach(function (slot) {
        $select.append($('<option></option>').attr('value', slot.time_value).text(slot.time_display));
      });
      placeholder();
    } else {
      showNoTickets();
    }
  }

  $('#departure-date, #return-date').on('change', function () {
    var $input = $(this);
    var type = $input.data('type');
    if ($input.val() === '') {
      placeholder();
      return;
    }
    $.ajax({
      url: urls.times,
      type: 'GET',
      data: { date: $input.val(), type: type },
      dataType: 'JSON'
    }).done(function (res) {
      populateTimings(type, res);
    }).fail(function () {
      clearTickets();
      $depart.append(sgTickets.errorBox(function () { $input.trigger('change'); }));
    });
  });

  $('#departure-time, #return-time').on('change', function () {
    if ($('#departure-time').val() !== '' && $('#return-time').val() !== '') {
      loadTickets();
    } else {
      placeholder();
    }
  });

  // ---- Date pickers: only the agent's fixed dates are enabled. ----
  function formatDate(d) {
    return String(d.getDate()).padStart(2, '0') + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + d.getFullYear();
  }
  var availableDates = page.availableDates || [];
  if ($.fn.datepicker) {
    $('#departure-date, #return-date').datepicker({
      format: 'd M yyyy',
      startDate: 'today',
      autoclose: true,
      beforeShowDay: function (date) {
        if (availableDates.length > 0 && availableDates.indexOf(formatDate(date)) < 0) return { enabled: false };
        if (page.noDates) return { enabled: false };
        return { enabled: true };
      }
    });
  }

  // Each depart quantity must equal its return leg (they are mirrored, this is a safety net).
  function equalQuantities() {
    var depart = 0;
    var ret = 0;
    $depart.find('input[name^="quantity"]').each(function () { depart += parseInt($(this).val(), 10) || 0; });
    $return.find('input[name^="quantity"]').each(function () { ret += parseInt($(this).val(), 10) || 0; });
    return depart === ret;
  }

  $('form.application-form').on('click', 'button[type="submit"]', function (e) {
    if (sgTickets.totalQty() <= 0) {
      e.preventDefault();
      sgUi.toast(t('selectAtLeastOne', 'Please select at least 1 to continue.'), 'warning');
      return false;
    }
    if (!equalQuantities()) {
      e.preventDefault();
      sgUi.toast(t('quantitiesEqual', 'Depart ticket quantity and return ticket quantity should be equal.'), 'warning');
      return false;
    }
    if (page.cartCount > 0 && !window.confirm(t('cartWillBeCleared', 'Cart will be cleared to make this purchase'))) {
      e.preventDefault();
      return false;
    }
  });

  sgTickets.refreshTotal();
});
