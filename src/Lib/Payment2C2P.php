<?php

namespace App\Lib;

use Exception;
use Cake\Log\Log;
use Cake\View\View;
use Cake\Http\Client;
use Cake\I18n\Number;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Cake\Mailer\Email;
use Cake\Utility\Text;
use App\Util\CatMailer;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\ORM\TableRegistry;

/**
 * Payment2C2P class
 * Library class for making request to 2C2P
 */
class Payment2C2P
{

    private $_secretkey;

    public function __construct()
    {
        $this->_secretkey = Configure::read('Payment.2C2P.secret_key');
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
    public function getRedirectLink($payment_method, $order_ref_id, $checkout_info, $total) {
        $payment_token = $this->requestPaymentToken($payment_method, $order_ref_id, $checkout_info, $total);
        if (!$payment_token) {
            return null;
        }
        return $payment_token['webPaymentUrl'];
    }

    /**
     * Encodes JWT and Request payment token to 2C2P
     *
     * @param string $payment_method
     * @param string $order_ref_id
     * @param array $checkout_info
     * @param double $total
     * @return void
     */
    public function requestPaymentToken($payment_method, $order_ref_id, $checkout_info, $total) {
        $codes  = Configure::read('Payment.2C2P.codes');
        $payment_channels = Configure::read('Payment.2C2P.payment_channels');
        
        // Retrieve order and get ticket names from order_items
        $ordersTable = TableRegistry::getTableLocator()->get('Orders');
        $order = $ordersTable->find()
            ->contain(['OrderItems.Tickets'])
            ->where(['Orders.order_ref_id' => $order_ref_id])
            ->first();
        
        $description = "item 1";
        if ($order && !empty($order->order_items)) {
            $ticketDescriptions = [];
            foreach ($order->order_items as $item) {
                if (!empty($item->ticket)) {
                    $ticketDescriptions[] = $item->qty . 'x ' . $item->ticket->name;
                }
            }
            if (!empty($ticketDescriptions)) {
                $description = implode(" | ", $ticketDescriptions);
                // Limit description length if needed
                if (strlen($description) > 200) {
                    $description = substr($description, 0, 197) . '...';
                }
            }
        }
        
        $payload = array(
            "merchantID" => Configure::read('Payment.2C2P.merchant_id'),
            "invoiceNo" => $order_ref_id,
            "description" => $description,
            "amount" => Number::precision($total, 2),
            "currencyCode" => "SGD",
            "paymentChannel" => $payment_channels[$payment_method],
            'frontendReturnUrl' => Router::url(['_scheme' => 'https', 'controller'=> 'Orders', 'action' => 'frontendNotificationReceiver', '2C2P', $order_ref_id], ['fullBase'=>true]),
            'backendReturnUrl' => Router::url(['_scheme' => 'https', 'controller'=> 'Orders', 'action' => 'notificationReceiver', '2C2P'], ['fullBase'=>true])
        );

        $jwt = JWT::encode($payload, $this->_secretkey, 'HS256');

        $data = ['payload' => $jwt];

        $http = new Client();
        $api_endpoint = Configure::read('Payment.2C2P.base_url') . Configure::read('Payment.2C2P.payment_token_endpoint');
        $response = $http->post($api_endpoint, json_encode($data),['type' => 'json']);
        
        if (!$response->isOk()) {
            return null;
        }
        $response_json = $response->getJson();

        if (empty($response_json['payload'])) {
            Log::write('debug', [
                'code' => '',
                'description' => '',
                'message' => $order_ref_id .' request payment token is invalid.',
                'data' => $response_json
            ]);
            return null;
        }
        $decoded_payload = $this->_decodeTokenResponse($response_json);
        if (empty($decoded_payload)) {
            Log::write('debug', [
                'code' => '',
                'description' => '',
                'message' => $order_ref_id .' decoded payload is empty.',
                'data' => $response_json
            ]);
            return null;
        }
        if ($decoded_payload['respCode'] !== '0000') {
            Log::write('debug', [
                'code' => $decoded_payload['respCode'],
                'description' => $decoded_payload['respDesc'],
                'message' => $order_ref_id .' decoded payload is invalid.',
                'data' => $decoded_payload
            ]);
            return null;
        }
        
        return $decoded_payload;
    }

    /**
     * Checks if payment is successful
     *
     * @param \App\Model\Entity\Order $order
     * @param array $data
     * @return array|null
     */
    public function isPaymentSuccess($order, $data) {
        if (empty($order)) {
            Log::write('debug', [
                'code' => '',
                'description' => '',
                'message' => 'Order not found',
                'data' => $data
            ]);
            return false;
        }
        $decoded_payload = base64_decode($data['paymentResponse']);
        $decoded_json = json_decode($decoded_payload, true);
        $order_ref_id = $order->order_ref_id;
        if (empty($decoded_json)) {
            Log::write('debug', [
                'code' => '',
                'description' => '',
                'message' => $order_ref_id .' decoded payload is empty.',
                'data' => $data
            ]);
            return false;
        }

        $payload = [
            "merchantID" => Configure::read('Payment.2C2P.merchant_id'),
            "invoiceNo" => $decoded_json['invoiceNo'],
        ];

        $jwt = JWT::encode($payload, $this->_secretkey, 'HS256');

        $data = ['payload' => $jwt];

        $http = new Client();
        $api_endpoint = Configure::read('Payment.2C2P.base_url') . Configure::read('Payment.2C2P.payment_inquiry_endpoint');
        $response = $http->post($api_endpoint, json_encode($data),['type' => 'json']);
        
        if (!$response->isOk()) {
            return false;
        }
        $response_json = $response->getJson();

        if (empty($response_json['payload'])) {
            Log::write('debug', [
                'code' => '',
                'description' => '',
                'message' => $order_ref_id .' request payment inquiry is invalid.',
                'data' => $response_json
            ]);
            return false;
        }
        $decoded_payload = $this->_decodeTokenResponse($response_json);
        if (empty($decoded_payload)) {
            Log::write('debug', [
                'code' => '',
                'description' => '',
                'message' => $order_ref_id .' decoded payload is empty.',
                'data' => $response_json
            ]);
            return false;
        }
        if ($decoded_payload['respCode'] !== '0000') {
            Log::write('debug', [
                'code' => $decoded_payload['respCode'],
                'description' => $decoded_payload['respDesc'],
                'message' => $order_ref_id .' decoded payload is invalid.',
                'data' => $decoded_payload
            ]);
            return false;
        }
        
        return true;
    }

    /**
     * Gets order_ref_id from the decoded data
     *
     * @param array $data
     * @return string|null
     */
    public function getOrderRefId($data) {
        $decoded_payload = $this->_decodeTokenResponse($data);
        if ($decoded_payload['respCode'] === '0000') {
            if (!empty($decoded_payload['invoiceNo'])) {
                return $decoded_payload['invoiceNo'];
            }
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
    public function verifyPayment($order, $data) {
        $decoded_payload = $this->_decodeTokenResponse($data);
        if ($decoded_payload['respCode'] !== '0000') {
            $cat_mailer = new CatMailer(Configure::read('Default.from_email'));
            Log::write('debug', 'invalid respCode when verifying payment');
            $cat_mailer->mail('verify_payment', 'Invalid Payment Response', Configure::read('Default.company_email'), [
                'code' => $decoded_payload['respCode'],
                'description' => $decoded_payload['respDesc'],
                'message' => $order->order_ref_id .' decoded payload is empty.',
                'data' => $decoded_payload 
            ], null, Configure::read('Default.exception_emails'));
            return false;
        }
        $this->Orders = TableRegistry::getTableLocator()->get('Orders');
        $this->Orders->setPaymentId($order, $decoded_payload['tranRef']);
        if ($decoded_payload['amount'] != $order->total) {
            $cat_mailer = new CatMailer(Configure::read('Default.from_email'));
            Log::write('debug', 'payment discrepancy');
            $cat_mailer->mail('verify_payment', 'Invalid Payment Response', Configure::read('Default.company_email'), [
                'code' => '',
                'description' => 'Total amount discrepancy',
                'message' => $order->order_ref_id .' was not successfully completed.',
                'data' => [
                    'paid_total' => $decoded_payload['amount'],
                    'order_total' => $order->total
                ] 
            ], null, Configure::read('Default.exception_emails'));
            return false;
        }
        return true;
    }

    /**
     * Gets markup value
     *
     * @param string $payment_method
     * @return array
     */
    public function getMarkup($payment_method) {
        $markups = Configure::read('Payment.2C2P.payment_markups');
        return !empty($markups[$payment_method]) ? $markups[$payment_method] : ['value' => 0, 'type' => 'percentage', 'add' => 0];
    }

    /**
     * Decodes payload from JWT 
     *
     * @param string $response_json
     * @return array
     */
    private function _decodeTokenResponse($response_json) {
        $payloadResponse = $response_json['payload'];
        $decodedPayload = JWT::decode($payloadResponse, new Key($this->_secretkey, 'HS256'));        
        $decoded_array = (array) $decodedPayload;

        return $decoded_array;
    }

    
}
