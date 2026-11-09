/*
 * Shared front-end helpers (loaded on every page of the default layout, after jQuery + Bootstrap).
 *
 * No inline scripts: server values reach page scripts through
 *  - <body data-currency data-locale data-watermark>,
 *  - <meta name="csrf-token">,
 *  - <script type="application/json" id="page-data"> (read with sgUi.page),
 *  - data-* attributes on the elements themselves.
 * Page scripts live in webroot/js/pages/<controller>-<action>.js.
 */
(function (window, $) {
  'use strict';

  var body = document.body;

  function readPageData() {
    var el = document.getElementById('page-data');
    if (!el) return {};
    try {
      return JSON.parse(el.textContent || '{}');
    } catch (e) {
      return {};
    }
  }

  var currency = (body && body.getAttribute('data-currency')) || 'SGD';
  var locale = ((body && body.getAttribute('data-locale')) || 'en-US').replace('_', '-');
  var formatter;
  try {
    formatter = new Intl.NumberFormat(locale, { style: 'currency', currency: currency });
  } catch (e) {
    formatter = null;
  }

  var sgUi = {
    page: readPageData(),

    /** One money format for every page (was 'SGD' in the cart JS and '$' on confirmation). */
    money: function (value) {
      var n = Number(value) || 0;
      return formatter ? formatter.format(n) : currency + ' ' + n.toFixed(2);
    },

    csrfToken: function () {
      var meta = document.querySelector('meta[name="csrf-token"]');
      return meta ? meta.getAttribute('content') : '';
    },

    /** Translated string from page data (pageData.i18n), with a fallback. */
    t: function (key, fallback) {
      var i18n = sgUi.page.i18n || {};
      return i18n[key] !== undefined ? i18n[key] : (fallback !== undefined ? fallback : key);
    },

    /** Non-blocking message (replaces alert()). type: info | success | warning | danger. */
    toast: function (message, type) {
      type = type || 'info';
      var container = document.getElementById('sg-toasts');
      if (!container) {
        container = document.createElement('div');
        container.id = 'sg-toasts';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.setAttribute('aria-live', 'polite');
        body.appendChild(container);
      }
      var icons = { info: 'fa-info-circle', success: 'fa-check-circle', warning: 'fa-exclamation-triangle', danger: 'fa-exclamation-circle' };
      var toast = document.createElement('div');
      toast.className = 'toast align-items-center border-0 text-bg-' + (type === 'info' ? 'primary' : type);
      toast.setAttribute('role', type === 'danger' || type === 'warning' ? 'alert' : 'status');
      var wrap = document.createElement('div');
      wrap.className = 'd-flex';
      var msg = document.createElement('div');
      msg.className = 'toast-body d-flex gap-2 align-items-start';
      var icon = document.createElement('i');
      icon.className = 'fa ' + (icons[type] || icons.info) + ' mt-1';
      icon.setAttribute('aria-hidden', 'true');
      var text = document.createElement('span');
      text.textContent = message;
      msg.appendChild(icon);
      msg.appendChild(text);
      var close = document.createElement('button');
      close.type = 'button';
      close.className = 'btn-close me-2 m-auto' + (type === 'warning' ? '' : ' btn-close-white');
      close.setAttribute('data-bs-dismiss', 'toast');
      close.setAttribute('aria-label', 'Close');
      wrap.appendChild(msg);
      wrap.appendChild(close);
      toast.appendChild(wrap);
      container.appendChild(toast);
      var instance = window.bootstrap ? new window.bootstrap.Toast(toast, { delay: 6000 }) : null;
      toast.addEventListener('hidden.bs.toast', function () { toast.remove(); });
      if (instance) instance.show(); else toast.classList.add('show');
      return toast;
    },

    /** Spinner + label on a button while a request runs; returns a restore function. */
    busy: function (button, label) {
      var $b = $(button);
      var original = $b.html();
      $b.prop('disabled', true).attr('aria-busy', 'true');
      $b.html('<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>');
      $b.append(document.createTextNode(label || original.replace(/<[^>]*>/g, '').trim()));
      return function () {
        $b.prop('disabled', false).removeAttr('aria-busy').html(original);
      };
    },

    /** Diagonal agent-name watermark as the page background (identifies screenshots). */
    watermark: function (text) {
      if (!text) return;
      var canvas = document.createElement('canvas');
      canvas.width = 400;
      canvas.height = 200;
      var ctx = canvas.getContext('2d');
      if (!ctx) return;
      ctx.translate(canvas.width / 2, canvas.height / 2);
      ctx.rotate(-Math.PI / 6);
      ctx.font = '22px Arial';
      ctx.fillStyle = 'rgba(30, 58, 138, 0.06)';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(text, 0, 0);
      body.style.backgroundImage = 'url(' + canvas.toDataURL() + ')';
    }
  };

  window.sgUi = sgUi;

  // Every jQuery AJAX POST carries the CSRF token.
  $.ajaxSetup({
    beforeSend: function (xhr, settings) {
      if (settings.type && settings.type.toUpperCase() !== 'GET') {
        var token = sgUi.csrfToken();
        if (token) xhr.setRequestHeader('X-CSRF-Token', token);
      }
    }
  });

  $(function () {
    // Popovers (announcements) and tooltips (price labels).
    $('[data-bs-toggle="popover"]').popover();
    if (window.bootstrap) {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new window.bootstrap.Tooltip(el);
      });
    }

    // Announcements: mark as read when opened; the × in the popover title closes it.
    var $bell = $('#read-new');
    $bell.on('shown.bs.popover', function () {
      var po = $(this);
      $('.btn-close-popover').off('click.sg').on('click.sg', function () {
        po.popover('hide');
      });
      var url = po.data('read-url');
      if (!url) return;
      $.ajax({ url: url, type: 'POST', dataType: 'JSON' })
        .done(function (res) {
          if (res.status == 200) {
            $('.announcements_unread').hide();
          }
        })
        .fail(function (err) {
          console.log(err);
        });
    });
    // Keyboard access to the bell (it is a div for the popover).
    $bell.on('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        $(this).popover('toggle');
      }
    });

    // Forms are revealed once jQuery and the page script are ready.
    $('.form-loader').remove();
    $('.form-deferred').slideDown();

    sgUi.watermark(body.getAttribute('data-watermark'));

    // Pages with a fixed bottom bar reserve space for it.
    if (document.querySelector('.sg-mobile-bar')) {
      body.classList.add('has-mobile-bar');
    }

    // Copy-to-clipboard buttons: <button data-copy="text"> or data-copy-target="#selector".
    $(document).on('click', '[data-copy], [data-copy-target]', function () {
      var $btn = $(this);
      var text = $btn.attr('data-copy');
      if (text === undefined) {
        var target = document.querySelector($btn.attr('data-copy-target'));
        text = target ? (target.value !== undefined && target.tagName !== 'DIV' ? target.value : target.innerText) : '';
      }
      var done = function () {
        sgUi.toast($btn.attr('data-copied') || 'Copied', 'success');
      };
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(done);
      } else {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.className = 'position-fixed top-0 start-0 opacity-0';
        body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); done(); } catch (e) { /* ignore */ }
        ta.remove();
      }
    });

    // POST links without inline JS (replaces FormHelper::postLink, whose output has onclick):
    // <a href="#" data-post-url="/orders/remove-ticket/…" data-confirm="Remove …?" data-post-data='{"k":"v"}'>
    $(document).on('click', '[data-post-url]', function (e) {
      e.preventDefault();
      var $link = $(this);
      var question = $link.attr('data-confirm');
      if (question && !window.confirm(question)) return;
      var form = document.createElement('form');
      form.method = 'post';
      form.action = $link.attr('data-post-url');
      form.className = 'd-none';
      var fields = { _csrfToken: sgUi.csrfToken() };
      var extra = $link.attr('data-post-data');
      if (extra) {
        try { $.extend(fields, JSON.parse(extra)); } catch (err) { /* ignore */ }
      }
      Object.keys(fields).forEach(function (name) {
        var values = Array.isArray(fields[name]) ? fields[name] : [fields[name]];
        values.forEach(function (value) {
          var input = document.createElement('input');
          input.type = 'hidden';
          input.name = Array.isArray(fields[name]) ? name + '[]' : name;
          input.value = value;
          form.appendChild(input);
        });
      });
      body.appendChild(form);
      form.submit();
    });

    // Print buttons: <button data-print>.
    $(document).on('click', '[data-print]', function () {
      window.print();
    });
  });
})(window, jQuery);
