<?php

namespace App\Util;

use Cake\Log\Log;
use Cake\View\View;
use Cake\Http\Client;
use Cake\Mailer\Mailer;
use Cake\Utility\Text;
use Cake\Core\Configure;
use Exception;

/**
 * CatMailer class
 * utility class for sending mail
 */
class CatMailer
{

    private $from_email;

    public function __construct(string $from_email)
    {
        $trimmed_email = trim($from_email);
        if (!filter_var($trimmed_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid from email!');
        }
        $this->from_email = $trimmed_email;
    }

    /**
     * Mail function
     *
     * @param string $template
     * @param string $subject
     * @param string $to
     * @param array $view_vars
     * @param array|null $cc
     * @param array|null $bcc
     * @param array|null $attachments
     * @return void
     */
    public function mail($template, $subject, $to, $data, $cc = null,$bcc = null, $attachments = null) {
        $Email = new Mailer();
        
        $Email->addHeaders(['Content-Type: text/html; charset=UTF-8']);
        $Email->viewBuilder()->setTemplate($template);
        $Email->setViewVars(array('data' => $data));
        $Email->setEmailFormat('html');

        $Email->setTo($to);
        if (!empty($cc)) {
            $Email->setCc($cc);
        }
        if (!empty($bcc)) {
            $Email->addBcc($bcc);
        }
        if (Configure::read('Default.admin_email')) {
            $Email->addBcc(Configure::read('Default.admin_email'));
        }
        $Email->setSubject($subject);


        /* set attachments if available */
        
        if (!empty($attachments)) {
          $Email->setAttachments($attachments);
        }
        /* end set attachments */


        try {
          $Email->deliver();
        } catch (\Cake\Network\Exception\SocketException $e) {
          $Email->setTransport('default');
          $Email->setSubject('[TDP Email Error] '.$Email->getSubject());
          if (Configure::read('Default.admin_email')) {
            $Email->setTo(Configure::read('Default.admin_email'));
          }
          $Email->deliver();
        } catch (Exception $e) {
          $Email->setTransport('default');
          $Email->setSubject('[TDP Email Error] '.$Email->getSubject());
          if (Configure::read('Default.admin_email')) {
            $Email->setTo(Configure::read('Default.admin_email'));
          }
          $Email->deliver();
        }
        
    }
    // public function mail($template, $subject, $to, $view_vars, $cc = null, $bcc = null, $attachments = null)
    // {
    //     $Email = new Mailer();

    //     $view = new View();
    //     $view->set(array('data' => $view_vars));
    //     $view->setTemplate('/Email/html/' . $template);
    //     $body = $view->render('/Email/html/' . $template, '/Email/html/default');


    //     $http = new Client();
    //     $email_data = [
    //         'subject' => $subject,
    //         'body' => $body,
    //         'from_email' => $this->from_email,
    //         'to_email' => $to,
    //         'bcc_email' => $bcc,
    //         'cc_email' => $cc,
    //         'from_name' => 'CAT',
    //         'to_name' => $to,
    //     ];
    //     $date = date('Y-m-d H:i:s');
    //     $url = '/email-queues/add';
    //     $method = 'post';
    //     $md5 = '';
    //     if (!empty($email_data)) { // if data is not empty create md5 hash
    //         $md5 = base64_encode(md5(json_encode($email_data, JSON_PRETTY_PRINT), true));
    //     }
    //     $headers = $this->_buildHeaders($method, $url, $date, $md5, $email_data);
    //     $sent_to_mailer = false;
    //     try {
    //         $response = $http->post(Configure::read('Mailer.url') . $url, $email_data, ['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false]);
    //         if ($response->isOk()) {
    //             $sent_to_mailer = true;
    //         } else {
    //             /* if cannot send to mailer log */
    //             Log::write('debug', $response, ['scope' => ['api']]);
    //         }
    //     } catch (\Exception $e) {
    //         //has exception so try to send from this server
    //         Log::write('debug', $e->getMessage(), ['bootstrap' => true]);
    //     }
    //     // if ($sent_to_mailer) {
    //     //   return;
    //     // }

    //     return;
    // }

    /**
     * Build headers for needed for api
     * 
     */
    private function _buildHeaders($method, $url, $date, $md5, $data)
    {
        $nonce = Text::uuid();
        $app_key = Configure::read('Auth.key');
        $textToSign = $method . "\n*/*\n" . $md5 .
            "\napplication/json\n" .
            $date .
            "\nx-ca-key:" . $app_key .
            "\nx-ca-nonce:" . $nonce .
            "\nx-ca-signaturemethod:" . Configure::read('Auth.signature_method') .
            "\n" . $url;
        $signature = base64_encode(hash_hmac('SHA256', $textToSign, Configure::read('Auth.secret'), true));
        $headers = [
            'Date' => $date,
            'Content-MD5' => $md5,
            'X-Ca-Nonce' => $nonce,
            'X-Ca-Key' => $app_key,
            'X-Ca-Signature' => $signature,
            'X-Ca-SignatureMethod' => Configure::read('Auth.signature_method'),
            'X-Ca-Signature-Headers' => Configure::read('Auth.signature_headers'),
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
            'Accept-Encoding' => "gzip, deflate, br",
            'Connection' => "keep-alive"
        ];

        return $headers;
    }
}
