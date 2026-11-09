/*
 * Date filter inputs (.datepicker, format d M yyyy) on the list / report pages
 * (CreditLogs/index, Orders/index, Orders/my_tickets, Reports/index).
 * Extra bootstrap-datepicker options come from pageData.datepicker (e.g. {endDate: 'today'}).
 */
$(function () {
  'use strict';
  if (!$.fn.datepicker) return;
  var extra = (window.sgUi && sgUi.page.datepicker) || {};
  $('.datepicker').datepicker($.extend({ format: 'd M yyyy' }, extra));
});
