<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Http\Client;
use Cake\Utility\Text;
use Cake\Core\Configure;
use Cake\Http\Client\Response;

/**
 * ApiCaller class
 */
class ApiCaller
{
    /**
     * Do Request function
     * reusable function to call post or get
     *
     * @param string $url
     * @param string $type get or post
     * @param array $data
     *
     * @return array | int
     */
    private function _doRequest($url, $type, $data, $email, $user_id)
    {
        $http = new Client();
        $api_base_url = Configure::read('Api.url');

        try {
            $headers = $this->_buildHeaders($email, $user_id);
            $response = null;
            // debug($headers);
            if ($type == 'get') {
                $response = $http->get($api_base_url . $url, $data, ['headers' => $headers]);
            } elseif ($type == 'post') {
                $response = $http->post($api_base_url . $url, $data, ['headers' => $headers]);
            }
            $log_data = [
                'url' => $url,
                'request' => [
                    // Signed auth headers are replayable; never log them in clear.
                    'headers' => array_merge($headers, array_intersect_key(
                        array_fill_keys(['Authorization', 'X-CA-UserSecret', 'X-Ca-Nonce'], '[redacted]'),
                        $headers
                    )),
                    'data' => $data
                ],
                'response' => [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getStringBody()
                ]
            ];
            \Cake\Log\Log::debug(json_encode($log_data));
            return $response;
        } catch (\Exception $e) {
            return (new Response())->withStatus(500);
        }
        
    }

    /**
     * Get function
     *
     * @param string $url
     * @param array $data
     * @param string $email
     * @param string $user_id
     *
     * @return Response
     */
    public function get($url, $data, $email, $user_id) {
        return $this->_doRequest($url, 'get', $data, $email, $user_id);
    }

    /**
     * Post function
     *
     * @param string $url
     * @param array $data
     * @param string $email
     * @param string $user_id
     *
     * @return Response
     */
    public function post($url, $data, $email, $user_id) {
        return $this->_doRequest($url, 'post', $data, $email, $user_id);
    }

    /**
     * Gets list of attractions
     *
     * @param $ $sku_id
     * @return void
     */
    public function getAttractions($sku_id) {

    }

    /**
     * Builds header for api request
     *
     * @param string $email
     * @param string $user_id
     * @return array
     */
    private function _buildHeaders($email,$user_id) {
        $secret_key = Configure::read('Api.secret');
        $header = [];
        $header['X-API-Version'] = 'v1.10';
        $header['Date'] = date('Y-m-d H:i:s');
        $header['X-CA-User'] = $email;
        $header['X-Ca-Nonce'] = Text::uuid();
        $header['X-Ca-SignatureMethod'] = 'HmacSHA256';
        $header['X-CA-UserSecret'] = hash('SHA256', $email.$user_id);
        $textToSign = $header['X-CA-User']."\n".
              $header['Date']."\n".
              $header['X-Ca-SignatureMethod']."\n".
              $header['X-Ca-Nonce'];
        $signature = base64_encode(hash_hmac('SHA256', $textToSign, $secret_key, true));
        $header['Authorization'] = 'Bearer '.$signature;

        return $header;
    }
}
