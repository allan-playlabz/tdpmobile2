/*
 * Stripe Checkout redirect (templates/Orders/execute_stripe.php). Loaded after https://js.stripe.com/v3/.
 * Errors (browser / network) are shown on the page instead of an alert().
 */
(function () {
  'use strict';
  var el = document.getElementById('stripe-redirect');
  if (!el || typeof window.Stripe !== 'function') return;
  var stripe = window.Stripe(el.getAttribute('data-key'));
  stripe.redirectToCheckout({ sessionId: el.getAttribute('data-session-id') }).then(function (result) {
    console.log(result);
    if (result && result.error) {
      var box = document.getElementById('stripe-error');
      box.textContent = result.error.message;
      box.classList.remove('d-none');
    }
  });
})();
