<html xmlns="http://www.w3.org/1999/xhtml">
   <body >
   <?= $this->Html->script(['https://js.stripe.com/v3/']) ?>
    <script type="text/javascript">
       var stripe = Stripe('<?= \Cake\Core\Configure::read('Payment.Stripe.api_key') ?>');
       
      stripe.redirectToCheckout({
        // Make the id field from the Checkout Session creation API response
        // available to this file, so you can provide it as parameter here
        // instead of the {{CHECKOUT_SESSION_ID}} placeholder.
        sessionId: '<?= $session_id ?>'
      }).then(function (result) {
        // If `redirectToCheckout` fails due to a browser or network
        // error, display the localized error message to your customer
        // using `result.error.message`.
        console.log(result);
        alert(result.error.message);
      });
            

    </script>
   </body>
</html>