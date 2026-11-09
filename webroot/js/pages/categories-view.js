/*
 * Category page (templates/Categories/view.php): a parent category ticks all its children;
 * unticking a child unticks the parent.
 */
$(function () {
  'use strict';
  $('.headclass').on('click', function () {
    $('.subclass' + $(this).data('headclass-id')).prop('checked', $(this).prop('checked'));
  });
  $('.headclass').each(function () {
    var id = $(this).data('headclass-id');
    var total = $('.subclass' + id).length;
    var checked = $('.subclass' + id + ':checked').length;
    if (total === checked) {
      $(this).prop('checked', true);
    }
  });
  $('.subclass').on('click', function () {
    if (!$(this).prop('checked')) {
      $('.headclass' + $(this).data('headclass-id')).prop('checked', false);
    }
  });
});
