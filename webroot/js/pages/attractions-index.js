/*
 * Attractions listing (templates/Attractions/index.php).
 * Category / country checkbox syncing (applied with the Apply filters button), recommended carousel.
 */
$(function () {
  'use strict';

  var $form = $('#attraction-filters');

  // ---- Categories: parent ticks all children; unticking a child unticks the parent. ----
  $('.headclass').on('click', function () {
    $('.subclass' + $(this).data('headclass-id')).prop('checked', $(this).prop('checked'));
  });
  $('.headclass').each(function () {
    var id = $(this).data('headclass-id');
    var total = $('.subclass' + id).length;
    var checked = $('.subclass' + id + ':checked').length;
    if (total === checked && total > 0) {
      $(this).prop('checked', true);
    }
  });
  $('.subclass').on('click', function () {
    if (!$(this).prop('checked')) {
      $('.headclass' + $(this).data('headclass-id')).prop('checked', false);
    }
  });

  // ---- Countries <-> cities (with partial / indeterminate state). ----
  function syncCountry(id) {
    var $cities = $('.city-check-' + id);
    var total = $cities.length;
    if (total === 0) return;
    var checked = $cities.filter(':checked').length;
    var $country = $('.country-check[data-country-id="' + id + '"]');
    if (checked === 0) {
      $country.prop('checked', false).prop('indeterminate', false);
    } else if (checked === total) {
      $country.prop('checked', true).prop('indeterminate', false);
    } else {
      $country.prop('checked', false).prop('indeterminate', true);
    }
  }
  $('.country-check').on('click', function () {
    $('.city-check-' + $(this).data('country-id')).prop('checked', $(this).prop('checked'));
    $(this).prop('indeterminate', false);
  });
  $('.city-check').on('click', function () {
    syncCountry($(this).data('country-id'));
  });
  $('.country-check').each(function () {
    syncCountry($(this).data('country-id'));
  });

  // ---- Filters apply only via the "Apply filters" button; highlight it once something changed. ----
  $form.on('change', '.headclass, .subclass, .country-check, .city-check', function () {
    $('#btn-apply-filters').addClass('is-pending');
  });

  // ---- Recommended carousel: auto-advance + prev/next, looping seamlessly. ----
  (function () {
    var $wrap = $('.recommended-carousel-wrap');
    if (!$wrap.length) return;
    var $track = $wrap.find('.recommended-track');
    var $originals = $track.children('.recommended-item');
    var origCount = $originals.length;
    if (origCount <= 1) return;
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    // Clone enough items so the forward loop-back and the prev-wrap stay seamless (up to 6 visible).
    while ($track.children('.recommended-item').length < origCount + 6) {
      $originals.clone(true).attr('aria-hidden', 'true').appendTo($track);
    }
    $track.children('.recommended-item[aria-hidden="true"]').find('a').attr('tabindex', '-1');
    var idx = 0, animating = false, timer = null;
    function itemWidth() { return $track.children('.recommended-item').first().outerWidth(true); }
    function go(animate) {
      $track.css('transition', animate && !reduceMotion ? 'transform 0.6s ease' : 'none');
      $track.css('transform', 'translateX(' + (-idx * itemWidth()) + 'px)');
    }
    function move(delta) {
      if (animating) return;
      animating = true;
      if (delta < 0 && idx <= 0) {
        idx = origCount;
        go(false);
        $track[0].offsetHeight; // force reflow so the next transition applies
      }
      idx += delta;
      go(true);
      setTimeout(function () {
        if (idx >= origCount) { idx -= origCount; go(false); }
        animating = false;
      }, reduceMotion ? 20 : 620);
    }
    function startAuto() { stopAuto(); if (!reduceMotion) timer = setInterval(function () { move(1); }, 3500); }
    function stopAuto() { if (timer) { clearInterval(timer); timer = null; } }
    $wrap.find('.rec-next').on('click', function (e) { e.preventDefault(); move(1); });
    $wrap.find('.rec-prev').on('click', function (e) { e.preventDefault(); move(-1); });
    $wrap.on('mouseenter focusin', stopAuto).on('mouseleave focusout', startAuto);
    $(window).on('resize', function () { go(false); });
    startAuto();
  })();

  // ---- Country page: "Top destinations" tile row; arrows scroll by one tile, hidden at the ends. ----
  $('.sg-dest-wrap').each(function () {
    var $wrap = $(this);
    var row = $wrap.find('.sg-dest-row')[0];
    var $prev = $wrap.find('.sg-dest-prev');
    var $next = $wrap.find('.sg-dest-next');
    if (!row) return;
    function step() {
      var tile = row.querySelector('.sg-dest-tile');
      return tile ? tile.getBoundingClientRect().width + 16 : row.clientWidth * 0.8;
    }
    function update() {
      var max = row.scrollWidth - row.clientWidth - 2;
      $prev.toggleClass('d-none', row.scrollLeft <= 2);
      $next.toggleClass('d-none', row.scrollLeft >= max);
    }
    $prev.on('click', function () { row.scrollBy({ left: -step() }); });
    $next.on('click', function () { row.scrollBy({ left: step() }); });
    row.addEventListener('scroll', update, { passive: true });
    $(window).on('resize', update);
    // Bring a selected city into view.
    var selected = row.querySelector('.sg-dest-tile.is-selected');
    if (selected) row.scrollLeft = selected.offsetLeft - row.offsetLeft - 8;
    update();
  });

  // Unread announcements open on the listing (was an inline script).
  if (window.sgUi && sgUi.page.openAnnouncements) {
    $('#read-new').trigger('click');
  }
});
