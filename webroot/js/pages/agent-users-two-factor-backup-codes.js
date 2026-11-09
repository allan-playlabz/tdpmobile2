/*
 * Backup codes page (templates/AgentUsers/two_factor_backup_codes.php): copies all codes,
 * one per line. Print is the data-print button (app.js).
 */
$(function () {
  'use strict';
  var btn = document.getElementById('tf-copy');
  if (!btn) return;
  btn.addEventListener('click', function () {
    var text = Array.prototype.map.call(document.querySelectorAll('#tf-codes code'), function (c) { return c.textContent; }).join('\n');
    var label = btn.querySelector('span');
    var done = function () { label.textContent = btn.getAttribute('data-copied') || 'Copied'; };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done);
    } else {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.className = 'position-fixed top-0 start-0 opacity-0';
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); done(); } catch (e) { /* ignore */ }
      ta.remove();
    }
  });
});
