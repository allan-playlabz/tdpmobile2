<?php

namespace App\Lib;

use Cake\Log\Log;
use Cake\Http\Client;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Api Sender class
 * Library class for sending request to Queue Server
 */
class QueueSender
{

    public function __construct()
    {
    }

    /**
     * sends data to queue server
     *
     * @param string $url
     * @param array $data
     * @return \Cake\Http\Client\Response|null
     */
    public static function send($url, $type, $data)
    {
        $http = new Client();
        $textToSign = $type === 'post' ? json_encode($data) : [];
        $secret_key = Configure::read('Mailer.secret');
        $headers = [
            'X-CA-Secret' => $secret_key,
            'X-CA-Signature' => base64_encode(hash_hmac('SHA256', $textToSign, $secret_key, true))
        ];
        try {
            $response = null;
            if ($type === 'get') {
                $response = $http->get(Configure::read('Mailer.api_url') . $url, $data, ['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false,'timeout' => Configure::read('Mailer.timeout')]);
            } else {
                $response = $http->post(Configure::read('Mailer.api_url') . $url, $data, ['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false,'timeout' => Configure::read('Mailer.timeout')]);
            }
            
            if (!$response->isOk()) {
                Log::debug(json_encode(['response' => $response->getStringBody(), 'data' => $data]));
                return null;
            }
            return $response;
        } catch (\Exception $e) {
            Log::debug(json_encode($data));
        }
        return null;
    }
}
