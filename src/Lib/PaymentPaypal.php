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
 * PaymentPaypal class
 * Library class for making request to Paypal
 */
class PaymentPaypal
{

    private $_secretkey;

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
        $http = new Client();
        $paypal_url = Configure::read('Payment.Paypal.api_url');
        $error = null;
        $token_response = $http->post($paypal_url . '/v1/oauth2/token', ['grant_type' => 'client_credentials'], [/*'headers' => $headers,*/'auth' => ['username' => Configure::read('Payment.Paypal.client_id'), 'password' => Configure::read('Payment.Paypal.client_secret')], 'timeout' => Configure::read('Api.timeout')]);
        if (!$token_response->isOk()) {
            $error = '9001'; // cannot get access token
            Log::debug(json_encode(['reason' => 'Cannot get access token', 'error_loc' => 'executePaypal', 'error_handle' => 'Order pending', 'error_action' => 'Try again', 'error_code' => $error, 'order_id' => $order_ref_id, 'token_response' => $token_response]));

            return null;
        }
        $token = $token_response->getJson();

        if (empty($token['access_token'])) {
            $error = '9001'; // cannot get access token
            Log::debug(json_encode(['reason' => 'Cannot get access token', 'error_loc' => 'executePaypal', 'error_handle' => 'Order pending', 'error_action' => 'Try again', 'error_code' => $error, 'order_id' => $order_ref_id]));

            return null;
        }

        $data = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'SGD',
                        'value' => Number::precision($total, 2),
                    ],
                    'description' => $order_ref_id,
                ]
            ],
            'processing_instruction' => 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL',
            'application_context' => [
                'return_url' => Router::url(['action' => 'thankYou', '_full' => true]),
                'cancel_url' => Router::url(['action' => 'confirmation', '_full' => true]),
            ],
            'payee' => [
                'email_address' => Configure::read('Payment.Paypal.email'),
            ]
        ];
        
        $headers = ['Content-Type' => 'application/json', 'Authorization' => $token['token_type'] . ' ' . $token['access_token']];
        $checkout_response = $http->post($paypal_url . '/v2/checkout/orders', json_encode($data), ['headers' => $headers, 'timeout' => Configure::read('Api.timeout')]);
        if ($checkout_response->isOk()) {
            $checkout = $checkout_response->getJson();
            if (!empty($checkout['id'])) {
                $Orders = TableRegistry::getTableLocator()->get('Orders');
                $order = $Orders->find()->where(['order_ref_id' => $order_ref_id])->first();
                if ($order) {
                    $Orders->setPaymentId($order, $checkout['id']);
                    if (!empty($checkout['links'])) {
                        foreach ($checkout['links'] as $key => $link) {
                            if ($link['rel'] == 'approve') {
                                $this->_sendToSTC($checkout['id']);

                                return $link['href'];
                            }
                        }
                    } else {
                        $error = '9003'; //no links
                        Log::debug(json_encode(['reason' => 'No links in paypal response', 'error_loc' => 'executePaypal', 'error_handle' => 'Order in progress', 'error_action' => 'Try again', 'error_code' => $error, 'order_id' => $order_ref_id]));
                    }
                }
            }
        } else {
            $error = '9002'; //no access token in result
            Log::debug(json_encode(['reason' => 'Cannot checkout paypal', 'error_loc' => 'executePaypal', 'error_handle' => 'Order pending', 'error_action' => 'Try again', 'error_code' => $error, 'order_id' => $order_ref_id]));
        }
        return null;
    }

    /**
     * createOrder
     *
     * @param string $order_ref_id
     * @param double $total
     * @return string
     */
    public function createOrder($order_ref_id, $total)
    {
        $http = new \Cake\Http\Client();
        $paypal_url = Configure::read('Payment.Paypal.api_url');
        $error = null;
        $token_response = $http->post($paypal_url . '/v1/oauth2/token', ['grant_type' => 'client_credentials'], [/*'headers' => $headers,*/'auth' => ['username' => Configure::read('Payment.Paypal.client_id'), 'password' => Configure::read('Payment.Paypal.client_secret')], 'timeout' => Configure::read('Api.timeout')]);
        if (!$token_response->isOk()) {
            $error = '9001'; // cannot get access token
            Log::debug(json_encode(['reason' => 'Cannot get access token', 'error_loc' => 'executePaypal', 'error_handle' => 'Order pending', 'error_action' => 'Try again', 'error_code' => $error, 'order_id' => $order_ref_id, 'token_response' => $token_response]));

            return null;
        }
        $token = $token_response->getJson();

        if (empty($token['access_token'])) {
            $error = '9001'; // cannot get access token
            Log::debug(json_encode(['reason' => 'Cannot get access token', 'error_loc' => 'executePaypal', 'error_handle' => 'Order pending', 'error_action' => 'Try again', 'error_code' => $error, 'order_id' => $order_ref_id]));

            return null;
        }

        $data = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'SGD',
                        'value' => Number::precision($total, 2),
                    ],
                    'description' => $order_ref_id,
                ]
            ],
            'processing_instruction' => 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL',
            'application_context' => [
                'return_url' => Router::url(['action' => 'complete', '_full' => true]),
                'cancel_url' => Router::url(['action' => 'confirmation', $order_ref_id, '_full' => true]),
            ],
            'payee' => [
                'email_address' => Configure::read('Payment.Paypal.email'),
            ]
        ];
        
        $headers = ['Content-Type' => 'application/json', 'Authorization' => $token['token_type'] . ' ' . $token['access_token']];
        $checkout_response = $http->post($paypal_url . '/v2/checkout/orders', json_encode($data), ['headers' => $headers, 'timeout' => Configure::read('Api.timeout')]);
        if ($checkout_response->isOk()) {
            $checkout = $checkout_response->getJson();
            if (!empty($checkout['id'])) {
                $Orders = TableRegistry::getTableLocator()->get('Orders');
                if ($Orders->setPid($order_ref_id, $checkout['id'])) {
                    $this->_sendToSTC($checkout['id']);
                    return $checkout;
                } else {
                    $error = '9005'; //cannot save order
                    Log::debug(json_encode(['reason' => 'Cannot save order', 'error_loc' => 'executePaypal', 'error_handle' => 'Order pending', 'error_action' => 'Try again', 'error_code' => $error, 'order_id' => $order_ref_id]));
                }
            }
        } else {
            $error = '9002'; //no access token in result
            Log::debug(json_encode(['reason' => 'Cannot checkout paypal', 'error_loc' => 'executePaypal', 'error_handle' => 'Order pending', 'error_action' => 'Try again', 'error_code' => $error, 'order_id' => $order_ref_id]));
        }
        return null;
    }

    /**
     * Sends data to paypal before redirecting
     *
     * @param string $tracking_id
     * @return bool
     */
    private function _sendToSTC($tracking_id) {
        $http = new \Cake\Http\Client();
        $paypal_url = Configure::read('Payment.Paypal.api_url');
        $error = null;
        $token_response = $http->put($paypal_url . '/v1/risk/transaction-contexts/' . Configure::read('Payment.Paypal.merchant_id') . '/' . $tracking_id, ['grant_type' => 'client_credentials'], [/*'headers' => $headers,*/'auth' => ['username' => Configure::read('Payment.Paypal.client_id'), 'password' => Configure::read('Payment.Paypal.client_secret')], 'timeout' => Configure::read('Api.timeout')]);
        if ($token_response->isOk()) {
            return true;
        }

        return false;
    }

    /**
     * Gets order_id from the submitted data
     *
     * @param array $data
     * @return string|null
     */
    public function getOrderRefId($data)
    {
        $Orders = TableRegistry::getTableLocator()->get('Orders');
        $order = $Orders->find()->where(['pi_id'=>$data['resource']['id']])->first();
        if ($order) {
            return $order->order_ref_id;
        }
        Log::debug('Cannot get order ref id: '.json_encode($data));
        return null;
    }

    /**
     * Capture payment
     *
     * @param \App\Model\Entity\Order $order
     * @param array $data
     * @return boolean
     */
    public function capturePayment($order, $data, &$order_data)
    {
        $paypal_url = Configure::read('Payment.Paypal.api_url');
        $http = new \Cake\Http\Client();
        $paypal_order_id = $data['orderID'];
        $access_token = '';
        $token_type = '';
        if (empty($access_token)) {
            $token_response = $http->post($paypal_url . '/v1/oauth2/token', ['grant_type' => 'client_credentials'], ['auth' => ['username' => Configure::read('Payment.Paypal.client_id'), 'password' => Configure::read('Payment.Paypal.client_secret')], 'timeout' => Configure::read('Api.timeout')]);
            if ($token_response->isOk()) {
                $token = $token_response->getJson();
                if ($token['access_token']) {
                    $access_token = $token['access_token'];
                    $token_type = $token['token_type'];
                }
            }
        }
        if (!empty($access_token)) {
            $headers = ['Content-Type' => 'application/json', 'Authorization' => $token_type . ' ' . $access_token];
            $capture_response = $http->post($paypal_url . '/v2/checkout/orders/' . $paypal_order_id . '/capture', '', ['headers' => $headers, 'timeout' => Configure::read('Api.timeout')]);
            
            if ($capture_response->isOk()) {
                $capture = $capture_response->getJson();
                if ($capture['id'] != $order->pi_id) {
                    $error = '9003';
                    Log::debug(json_encode(['reason' => 'Invalid transaction ID', 'error_loc' => 'capturePaypal', 'error_handle' => 'Booking in pending_payment', 'error_action' => 'Check paypal order status', 'result' => $capture, 'error_code' => $error, 'order_id' => $order->order_ref_id]));
                    return false;
                }
                Log::debug(json_encode($capture));
                $PaypalRecords = TableRegistry::getTableLocator()->get('EventPaypalRecords');
                $paypal_record = $PaypalRecords->newEntity(['data' => json_encode($capture),'event_id' => $order->event_id]);
                if (!$PaypalRecords->save($paypal_record)) {
                    Log::debug(json_encode($paypal_record->getErrors()));
                }
                if ($capture['id'] == $order->pi_id && $capture['status'] == 'COMPLETED') {
                    $amount_paid = 0;
                    foreach ($capture['purchase_units'] as $purchase_unit) {
                        $amount_paid += $purchase_unit['payments']['captures'][0]['amount']['value'];
                    }
                    if ($order->total == $amount_paid) {
                        $order_data = $capture;
                        return true;
                    }
                    $error = '8004';
                    Log::debug(json_encode(['reason' => 'Total amount discrepancy', 'error_loc' => 'capturePaypal', 'error_handle' => 'Booking in pending_payment', 'error_action' => 'Check paypal order status', 'result' => $capture, 'error_code' => $error, 'order_id' => $order->order_ref_id]));
                } else {
                    Log::debug($capture, 'debug');
                    $error = '9004'; // capture order status is not completed
                    Log::debug(json_encode(['reason' => 'Capture status from paypal is not completed', 'error_loc' => 'capturePaypal', 'error_handle' => 'Booking in pending_payment', 'error_action' => 'Check paypal order status', 'result' => $capture, 'error_code' => $error, 'order_id' => $order->order_ref_id]));
                }
            } else {
                $error = '9006 (Error calling paypal)'; // calling capture order error
                Log::debug(json_encode(['reason' => 'Error calling capture order on paypal', 'error_loc' => 'capturePaypal', 'error_handle' => 'Booking in pending_payment', 'error_action' => 'Try again', 'error_code' => $error, 'order_id' => $order->order_ref_id]));
            }
        }
                
        return false;

        
    }

    /**
     * Verifies payment amount with order
     *
     * @param \App\Model\Entity\Order $order
     * @param array $data
     * @return boolean
     */
    public function verifyPayment($order, $data, $headers)
    {
        $paypal_url = Configure::read('Payment.Paypal.api_url');
        $http = new \Cake\Http\Client();
        $paypal_order_id = $order->pi_id;
        $access_token = '';
        $token_type = '';
        if (empty($access_token)) {
            $token_response = $http->post($paypal_url . '/v1/oauth2/token', ['grant_type' => 'client_credentials'], ['auth' => ['username' => Configure::read('Payment.Paypal.client_id'), 'password' => Configure::read('Payment.Paypal.client_secret')], 'timeout' => Configure::read('Api.timeout')]);
            if ($token_response->isOk()) {
                $token = $token_response->getJson();
                if ($token['access_token']) {
                    $access_token = $token['access_token'];
                    $token_type = $token['token_type'];
                }
            }
        }
        if (!empty($access_token)) {

            if (!$this->verifyWebhook($token_type, $access_token, $headers, $data)) {
                return false;
            }

            $headers = ['Content-Type' => 'application/json', 'Authorization' => $token_type . ' ' . $access_token];
            $capture_response = $http->post($paypal_url . '/v2/checkout/orders/' . $paypal_order_id . '/capture', '', ['headers' => $headers, 'timeout' => Configure::read('Api.timeout')]);
            
            if ($capture_response->isOk()) {
                $capture = $capture_response->getJson();
                if ($capture['id'] != $order->pi_id) {
                    $error = '9003';
                    Log::debug(json_encode(['reason' => 'Invalid transaction ID', 'error_loc' => 'capturePaypal', 'error_handle' => 'Booking in pending_payment', 'error_action' => 'Check paypal order status', 'result' => $capture, 'error_code' => $error, 'order_id' => $order->order_ref_id]));
                    return false;
                }
                Log::debug(json_encode($capture));
                $PaymentResponses = TableRegistry::getTableLocator()->get('PaymentResponses');
                $payment_response = $PaymentResponses->newEntity(['provider' => 'Paypal', 'data' => json_encode($capture),'order_ref_id' => $order->order_ref_id]);
                if (!$PaymentResponses->save($payment_response)) {
                    Log::debug(json_encode($payment_response->getErrors()));
                }
                if ($capture['id'] == $order->pi_id && $capture['status'] == 'COMPLETED') {
                    $amount_paid = 0;
                    foreach ($capture['purchase_units'] as $purchase_unit) {
                        $amount_paid += $purchase_unit['payments']['captures'][0]['amount']['value'];
                    }
                    if ($order->total == $amount_paid) {
                        return true;
                    }
                    $error = '8004';
                    Log::debug(json_encode(['reason' => 'Total amount discrepancy', 'error_loc' => 'capturePaypal', 'error_handle' => 'Booking in pending_payment', 'error_action' => 'Check paypal order status', 'result' => $capture, 'error_code' => $error, 'order_id' => $order->order_ref_id]));
                } else {
                    Log::debug($capture, 'debug');
                    $error = '9004'; // capture order status is not completed
                    Log::debug(json_encode(['reason' => 'Capture status from paypal is not completed', 'error_loc' => 'capturePaypal', 'error_handle' => 'Booking in pending_payment', 'error_action' => 'Check paypal order status', 'result' => $capture, 'error_code' => $error, 'order_id' => $order->order_ref_id]));
                }
            } else {
                $error = '9006 (Error calling paypal)'; // calling capture order error
                Log::debug(json_encode(['reason' => 'Error calling capture order on paypal', 'error_loc' => 'capturePaypal', 'error_handle' => 'Booking in pending_payment', 'error_action' => 'Try again', 'error_code' => $error, 'order_id' => $order->order_ref_id, 'response' => $capture_response->getStringBody()]));
            }
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
        $markups = Configure::read('Payment.Paypal.payment_markups');
        return !empty($markups[$payment_method]) ? $markups[$payment_method] : ['value' => 0, 'type' => 'percentage', 'add' => 0];
    }

    /**
     * Verify if the webhook if from paypal
     *
     * @param string $access_token
     * @param array $header
     * @param array $payload
     * @return boolean
     */
    public function verifyWebhook($token_type, $access_token, $header, $payload): bool {
        $paypal_url = Configure::read('Payment.Paypal.api_url');
        $http = new \Cake\Http\Client();
        
        Log::debug('headers: '.json_encode($header));
        
        $headers = ['Content-Type' => 'application/json', 'Authorization' => $token_type . ' ' . $access_token];
        $data = [
            'transmission_id' => $header['Paypal-Transmission-Id'][0],
            'transmission_time' => $header['Paypal-Transmission-Time'][0],
            'cert_url' => $header['Paypal-Cert-Url'][0],
            'auth_algo' => $header['Paypal-Auth-Algo'][0],
            'transmission_sig' => $header['Paypal-Transmission-Sig'][0],
            'webhook_id' => Configure::read('Payment.Paypal.webhook_id'),
            'webhook_event' => '',
        ];
        $json_data = json_encode($data);
        $json_data = substr($json_data,0, strlen($json_data)-3) . file_get_contents('php://input') . '}';
        $verify_response = $http->post($paypal_url . '/v1/notifications/verify-webhook-signature', $json_data, ['type' => 'json', 'headers' => $headers, 'timeout' => Configure::read('Api.timeout')]);
        
        if ($verify_response->isOk()) {
            $verify = $verify_response->getJson();
            if (!empty($verify['verification_status']) && $verify['verification_status'] === 'SUCCESS') {
                return true;
            }
            Log::debug('Invalid wehbook: '.$verify);
        }
        Log::debug('Cannot verify webhook: '.$verify_response->getStringBody());
        return false;
    }
}
