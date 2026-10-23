<?php
namespace App\Form;

use Cake\Core\Configure;
use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Http\Client;
use Cake\Log\Log;
use Cake\Utility\Text;
use Cake\Validation\Validator;


/**
 * Form for agent signup
 */
class SignupForm extends Form
{
protected function _buildSchema(Schema $schema): \Cake\Form\Schema
{
return $schema->addField('name', 'string')
->addField('email', ['type' => 'string'])
->addField('body', ['type' => 'text']);
}

public function validationDefault(Validator $validator): Validator
{
    $validator
        ->allowEmptyString('id', null, 'create');

    $validator
        ->scalar('first_name')
        ->maxLength('first_name', 50)
        ->requirePresence('first_name', 'create')
        ->notEmptyString('first_name');

    $validator
        ->scalar('last_name')
        ->maxLength('last_name', 50)
        ->requirePresence('last_name', 'create')
        ->notEmptyString('last_name');

    $validator
        ->email('email')
        ->requirePresence('email', 'create')
        ->notEmptyString('email');

    $validator
        ->scalar('company_name')
        ->maxLength('company_name', 100)
        ->requirePresence('company_name', 'create')
        ->notEmptyString('company_name');

    $validator
        ->scalar('general_category')
        ->maxLength('general_category', 50)
        ->allowEmptyString('general_category');

    $validator
        ->scalar('country')
        ->maxLength('country', 100)
        ->requirePresence('country', 'create')
        ->notEmptyString('country');

    $validator
        ->scalar('contact_no')
        ->maxLength('contact_no', 50)
        ->allowEmptyString('contact_no');

    $validator
        ->scalar('gender')
        ->allowEmptyString('gender');

    $validator
        ->scalar('email_displayname')
        ->maxLength('email_displayname', 255)
        ->allowEmptyString('email_displayname');

    $validator
        ->scalar('status')
        ->allowEmptyString('status');

    return $validator;
}

    protected function process(array $data): bool
    {
        $api_url = Configure::read('Api.url');
        $http = new Client();
        $headers = $this->_buildHeaders($data);
        $response = $http->post($api_url.'/signup',$data,['headers'=>$headers/*'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false*/]);
        $res = $response->getJson();
        if ($res['status'] == '1000') {
            return true;
        } else {
            if (!empty($res['response']['data']['error'])) {
                $this->setErrors($res['response']['data']['error']);
            }
            Log::debug(json_encode($res));
        }
        return false;
    }

    private function _buildHeaders($data) {
        $secret_key = Configure::read('Api.secret');
        $header = [];
        $header['Date'] = date('Y-m-d H:i:s');
        $header['X-Api-Role'] = 'admin';
        $header['X-Ca-Nonce'] = Text::uuid();
        $header['X-Ca-SignatureMethod'] = 'HmacSHA256';
        $textToSign = $header['X-Api-Role']."\n".
            $header['Date']."\n".
            $header['X-Ca-SignatureMethod']."\n".
            $header['X-Ca-Nonce'];
        $signature = base64_encode(hash_hmac('SHA256', $textToSign, $secret_key, true));
        $header['Authorization'] = 'Bearer '.$signature;
        return $header;
    }
}
