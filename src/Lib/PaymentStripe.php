<?php

namespace App\Lib;

use Exception;
use Cake\Log\Log;
use Cake\Http\Client;
use Cake\I18n\Number;
use App\Util\CatMailer;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\ORM\TableRegistry;

/**
 * PaymentStripe class
 * Library class for making request to Stripe
 */
class PaymentStripe
{

    private $_secretkey;

    public function __construct()
    {
        $this->_secretkey = Configure::read('Payment.Stripe.secret_key');
    }

    /**
     * Gets a redirect link based on the payment method selected
     *
     * @param string $payment_method
     * @param string $order_ref_id
     * @param array $checkout_info
     * @param double $total
     * @return string
     */
    public function getRedirectLink($payment_method, $order_ref_id, $checkout_info, $total)
    {
        $line_items = [];
        $checkout_info_array = json_decode($checkout_info, true);
        $line_items[] = [
            'name' => Configure::read('Default.site_name') . ' order ' . $order_ref_id,
            'description' => 'Tracking No. ' . $checkout_info_array['tracking_no'],
            'amount' => round($total * 100),
            'currency' => 'sgd',
            'quantity' => 1,
        ];

        $payment_method_types = ['card'];
        $payment_method_options = null;

        if ($payment_method == 'wechat_pay') {
            $payment_method_types = ['wechat_pay'];
            $payment_method_options = [
                'wechat_pay' => [
                    'client' => "web"
                ],
            ];
        }
        \Stripe\Stripe::setApiKey(Configure::read('Payment.Stripe.secret_key'));
        $stripe_session = [
            'payment_method_types' => $payment_method_types,
            'line_items' => $line_items,
            'success_url' => Router::url(['action' => 'thankYou'], ['fullBase' => true]),
            'cancel_url' => Router::url(['action' => 'confirmation'], ['fullBase' => true]),
        ];
        if ($payment_method_options != null) {
            $stripe_session['payment_method_options'] = $payment_method_options;
        }

        $session = \Stripe\Checkout\Session::create($stripe_session);
        if ($session) {
            $this->Orders = TableRegistry::getTableLocator()->get('Orders');
            $order = $this->Orders->find()->where(['order_ref_id' => $order_ref_id])->first();
            // (removed debug output)
            if ($order) {
                $this->Orders->setPaymentId($order, $session->id);
                $session_id = $session->id;
                $_SESSION['session_id'] = $session_id;
                return Router::url(['controller' => 'Orders', 'action' => 'executeStripe']);
            }
        }
        return null;
    }

    /**
     * Gets order_ref_id from the submitted data
     *
     * @param array $data
     * @return string|null
     */
    public function getOrderRefId($data)
    {
        \Stripe\Stripe::setApiKey(Configure::read('Payment.Stripe.secret_key'));

        $endpoint_secret = Configure::read('Payment.Stripe.signing_secret'); // signing secret in dashboard

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;
        // (Payment.Stripe config — incl. secret_key/signing_secret — is no longer logged here.)
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Log::write('debug', $e->getMessage());
            return null;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            Log::write('debug', $e->getMessage());
            return null;
        }
        
        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object; // contains a StripePaymentIntent
                $order = $this->Orders->find()->where(['pi_id'=>$session->id])
                ->contain(['OrderItems'=>['Tickets'=>['Attractions']]]);
                if ($order) {
                    return $order->order_ref_id;
                }
                return null;
                break;
                // ... handle other event types
            default:
            Log::write('debug', $event->type);
                // Unexpected event type
                return null;
        }
        return null;
    }

    /**
     * Verifies payment amount with order
     *
     * @param \App\Model\Entity\Order $order
     * @param array $data
     * @return boolean
     */
    public function verifyPayment($order, $data)
    {
        \Stripe\Stripe::setApiKey(Configure::read('Payment.Stripe.secret_key'));

        $endpoint_secret = Configure::read('Payment.Stripe.signing_secret'); // signing secret in dashboard

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return false;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            return false;
        }
        $this->StripeRecords = TableRegistry::getTableLocator()->get('StripeRecords');
        $stripe_record = $this->StripeRecords->newEntity(['data' => json_encode($event), 'pi_id' => !empty($event->data->object) ? $event->data->object->id : null]);
        $this->StripeRecords->save($stripe_record);
        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object; // contains a StripePaymentIntent
                $total_paid = 0;
                if (!empty($session->display_items)) {
                    foreach ($session->display_items as $key => $display_item) {
                        $total_paid += $display_item->amount * $display_item->quantity;
                    }
                }
                $total_paid = $total_paid / 100;
                if ($order && $total_paid != $order->total) {
                    if ($order) {
                        $cat_mailer = new CatMailer(Configure::read('Default.from_email'));
                        Log::write('debug', 'payment discrepancy');
                        $cat_mailer->mail('verify_payment', 'Invalid Payment Response', Configure::read('Default.company_email'), [
                            'code' => '',
                            'description' => 'Total amount discrepancy',
                            'message' => $order->order_ref_id . ' was not successfully completed.',
                            'data' => [
                                'paid_total' => $total_paid,
                                'order_total' => $order->total
                            ]
                        ], null, Configure::read('Default.exception_emails'));
                    }
                }
                return true;
                break;
                // ... handle other event types
            default:
                // Unexpected event type
                return false;
        }
        return false;

        
    }

    /**
     * Gets markup value
     *
     * @param string $payment_method
     * @return array
     */
    public function getMarkup($payment_method)
    {
        $markups = Configure::read('Payment.Stripe.payment_markups');
        return !empty($markups[$payment_method]) ? $markups[$payment_method] : ['value' => 0, 'type' => 'percentage', 'add' => 0];
    }
}
