/* "Try another way" (templates/AgentUsers/otp_another_way.php): the Turnstile widget calls
 * these (data-callback / data-expired-callback); the send button stays disabled until it passes. */
(function () {
  'use strict';
  function button() { return document.getElementById('another-way-submit'); }
  window.sgTurnstileOk = function () {
    var b = button();
    if (b) b.disabled = false;
  };
  window.sgTurnstileExpired = function () {
    var b = button();
    if (b) b.disabled = true;
  };
  document.addEventListener('submit', function (e) {
    if (e.target && e.target.id === 'another-way-form') {
      var b = button();
      if (b) b.disabled = true; // no double send
    }
  });
})();
