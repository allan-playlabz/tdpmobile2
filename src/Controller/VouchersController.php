<?php

namespace App\Controller;

use App\Lib\QueueSender;
use Cake\Core\Configure;
use App\Controller\AppController;

/**
 * Vouchers Controller
 *
 * @property \App\Model\Table\VouchersTable $Vouchers
 *
 * @method \App\Model\Entity\Voucher[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class VouchersController extends AppController
{

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('S3');
        $this->loadModel('Orders');
        $this->loadModel('VoucherMerging');
        $this->Auth->allow(['download', 'sdownload']);
    }

    public function view($id = null)
    {
        $this->autoRender = false;
        $this->loadModel('AgentUsers');
        $voucher = $this->Vouchers->get($id, [
            'contain' => ['OrderItems' => ['Orders' => ['AgentUsers']]]
        ]);
        $is_agent_allowed = $voucher->order_item->order->agent_user->id == $this->Auth->user('id');
        if ($this->Auth->user('role') == 'master') {
            $agent_user_ids = $this->AgentUsers->find('list', ['valueField' => 'id'])->where(['agent_id' => $this->Auth->user('agent_id')])->toList();
            if (!empty($agent_user_ids)) {
                $is_agent_allowed = in_array($voucher->order_item->order->agent_user_id, $agent_user_ids);
            }
        }
        if ($voucher->order_item && $voucher->order_item->order && $voucher->order_item->order->agent_user && $is_agent_allowed) {

            // $response = $this->response->withFile(WWW_ROOT.'order_files'.DS.$voucher->order_item->order->id.DS.$voucher->code_id.'.pdf',['download' => true,]);
            $filename = $voucher->code_id . '.pdf';
            if ($voucher->voucher_type == 'pdf') {
                $pdf = $this->Vouchers->Pdfs->find()->where(['Pdfs.id' => $voucher->code_id])->first();
                if ($pdf) {
                    $filename = basename($pdf->pdf_filename);
                }
            }
            $file = $this->S3->getObject('vouchers/' . $voucher->created->format('Y/md') . '/' . $voucher->code_id . '.pdf');
            $response = $this->response;
            if ($file) {
                $response = $this->response->withStringBody($file->__toString());

                $response = $response->withType('pdf');

                $response = $response->withDownload($filename);
            } else {
                $data = [];
                $http = new \Cake\Http\Client();
                $headers = $this->_buildHeaders($data, $this->Auth->user('email'), $this->Auth->user('id'));
                $api_url = Configure::read('Api.url');
                $response = $http->get($api_url . '/voucher/download', ['voucher_id' => $voucher->id], ['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false]);
                if ($response->isOk()) {
                    if (in_array($voucher->order_item->order->agent_id, Configure::read('Vouchers.display_in_browser.agent_ids'))) {
                        return $this->response->withStringBody($response->getStringBody())->withType('pdf');
                    }
                    return $this->response->withStringBody($response->getStringBody())->withDownload($filename)->withType('pdf');
                }
                $response = $response->withStatus(404);
            }
            return $response;
        } else {
            if (!$is_agent_allowed) {
                return $this->response->withStatus(403);
            }
            return $this->response->withStatus(404);
        }

        $this->set('voucher', $voucher);
    }

    public function apiview($year, $monthday, $code)
    {
        $this->autoRender = false;

        $filename = $year . '/' . $monthday . '/' . $code . '.pdf';
        $file = $this->S3->getObject('vouchers/' . $filename);
        $response = $this->response;
        if ($file) {
            $response = $this->response->withStringBody($file->__toString());
            $response = $response->withType('pdf');
            $response = $response->withDownload($code . '.pdf');
        } else {
            $response = $response->withStatus(404);
        }
        return $response;
    }

    public function sview($id = null)
    {
        $this->autoRender = false;
        $id_parts = explode('_', $id);
        if (count($id_parts) < 2) {
            return $this->response->withStatus(404);
        }
        $order_item = $this->Vouchers->OrderItems->get($id_parts[0], ['contain' => ['Orders']]);
        if ($order_item && $order_item->order->agent_user_id == $this->Auth->user('id')) {
            $filename = $order_item->id . '_' . $id_parts[1] . '.pdf';
            $file = $this->S3->getObject('vouchers/' . $order_item->created->format('Y/md') . '/' . $filename);
            $response = $this->response;
            if ($file) {
                $response = $this->response->withStringBody($file->__toString());
                $response = $response->withType('pdf');
                $response = $response->withDownload($filename);
            } else {
                $response = $response->withStatus(404);
            }
            return $response;
        } else {
            return $this->response->withStatus(404);
        }
    }

    public function sdownload($id = null, $check = null)
    {
        $this->autoRender = false;

        if (!$id || !$check) {
            return $this->response->withStatus(404);
        }
        if ($check != md5($id . Configure::read('Api.secret'))) {
            return $this->response->withStatus(404);
        }
        $id_parts = explode('_', $id);
        if (count($id_parts) < 2) {
            return $this->response->withStatus(404);
        }
        $order_item = $this->Vouchers->OrderItems->get($id_parts[0], ['contain' => ['Orders']]);
        if (!$order_item) {
            return $this->response->withStatus(404);
        }
        $is_agent_allowed = $order_item->order->agent_user_id == $this->Auth->user('id');
        if ($this->Auth->user('role') == 'master') {
            $agent_user_ids = $this->AgentUsers->find('list', ['valueField' => 'id'])->where(['agent_id' => $this->Auth->user('agent_id')])->toList();
            if (!empty($agent_user_ids)) {
                $is_agent_allowed = in_array($order_item->order->agent_user_id, $agent_user_ids);
            }
        }
        if (!$is_agent_allowed) {
            return $this->response->withStatus(404);
        }

        $filename = $order_item->id . '_' . trim($id_parts[1], '.pdf') . '.pdf';
        $file = $this->S3->getObject('vouchers/' . $order_item->created->format('Y/md') . '/' . $filename);
        $response = $this->response;
        if ($file) {
            $response = $this->response->withStringBody($file->__toString());
            $response = $response->withType('pdf');
            $response = $response->withDownload($filename);
            return $response;
        } else { //recreate merged vouchers
            $merged_pdf = QueueSender::send('/download-merged-order-item', 'post', ['order_item_id' => $order_item->id, 'order_item_idx' => $id_parts[1]]);
            if ($merged_pdf !== null) {
                preg_match_all("/\w+\.\w+/", $merged_pdf->getHeaderLine('Content-Disposition'), $cd_filename);
                return $this->response->withStringBody($merged_pdf->getStringBody())->withDownload($cd_filename[0][0]);
            } else {
                return $this->response->withStatus(404);
            }
        }
        return $response;
    }

    public function apidownload($year, $monthday, $code, $check = null)
    {
        $this->autoRender = false;
        if (!$id || !$check) {
            return $this->response->withStatus(404);
        }
        if ($check != md5($id . Configure::read('Api.secret'))) {
            return $this->response->withStatus(404);
        }
        $filename = $year . '/' . $monthday . '/' . $code . '.pdf';
        $file = $this->S3->getObject('vouchers/' . $filename);
        $response = $this->response;
        if ($file) {
            $response = $this->response->withStringBody($file->__toString());
            $response = $response->withType('pdf');
            $response = $response->withDownload($code . '.pdf');
        } else {
            $response = $response->withStatus(404);
        }
        return $response;
    }

    public function downloadAll($id = null)
    {
        $this->autoRender = false;
        $this->loadModel('Orders');
        $this->loadModel('AgentUsers');
        $order = $this->Orders->find()->where(['id' => $id, 'status' => 'done'])->first();

        if (!$order) {
            return $this->response->withStatus(404);
        }

        $is_agent_allowed = $order->agent_user_id == $this->Auth->user('id');
        if ($this->Auth->user('role') == 'master') {
            $agent_user_ids = $this->AgentUsers->find('list', ['valueField' => 'id'])->where(['agent_id' => $this->Auth->user('agent_id')])->toList();
            if (!empty($agent_user_ids)) {
                $is_agent_allowed = in_array($order->agent_user_id, $agent_user_ids);
            }
        }
        if (!$is_agent_allowed) {
            return $this->response->withStatus(404);
        }
        $voucher_merging = $this->VoucherMerging->find()->where(['order_ref_id' => $order->order_ref_id, 'agent_id' => $this->Auth->user('agent_id')])->first();
        if ($voucher_merging) {
            if ($voucher_merging->status == 'done') {
                $data = [];
                $http = new \Cake\Http\Client();
                $auth_user_email = $this->Auth->user('email');
                $auth_user_id = $this->Auth->user('id');
                if (empty($auth_user_email)) {
                    $this->loadModel('Agents');
                    $agent = $this->Agents->find()->where(['id' => $order->agent_user->agent_id])->first();
                    if ($agent) {
                        $auth_user_email = $agent->email;
                        $auth_user_id = $agent->id;
                    }
                }
                $headers = $this->_buildHeaders($data, $auth_user_email, $auth_user_id);
                $api_url = Configure::read('Api.url');
                $response = $http->get($api_url . '/voucher/download-order', ['order_ref_id' => $order->order_ref_id], ['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false]);
                if ($response->isOk()) {
                    if (in_array($order->agent_id, Configure::read('Vouchers.display_in_browser.agent_ids'))) {
                        return $this->response->withStringBody($response->getStringBody())->withType('pdf');
                    }
                    return $this->response->withStringBody($response->getStringBody())->withDownload($order->order_ref_id . '.pdf')->withType('pdf');
                }
            } else {
                $this->Flash->error('Please wait for merge to complete.');
                return $this->redirect($this->referer());
            }
        } else {
            $merged_pdf = QueueSender::send('/merged-pdf', 'post', [
                'order_ref_id' => $order->order_ref_id,
                'agent_id' => $this->Auth->user('agent_id'),
                'type' => 'singlepdf'
            ]);
            if ($merged_pdf !== null) {
                $this->Flash->info('Your request has been queued. Please wait for merge to complete.');
                return $this->redirect($this->referer());
            } else {
                return $this->response->withStatus(404);
            }
        }        
    }

    public function download($id = null, $check = null)
    {
        $this->autoRender = false;
        if (!$id || !$check) {
            return $this->response->withStatus(404);
        }
        if ($check != md5($id . Configure::read('Api.secret')) && $check != md5($id . Configure::read('App.secret'))) {
            return $this->response->withStatus(404);
        }
        $voucher = $this->Vouchers->get($id, [
            'contain' => ['OrderItems' => ['Orders' => ['AgentUsers']]]
        ]);

        if ($voucher->order_item && $voucher->order_item->order) {

            // $response = $this->response->withFile(WWW_ROOT.'order_files'.DS.$voucher->order_item->order->id.DS.$voucher->code_id.'.pdf',['download' => true,]);
            $filename = $voucher->code_id . '.pdf';
            if ($voucher->voucher_type == 'pdf') {
                $pdf = $this->Vouchers->Pdfs->find()->where(['Pdfs.id' => $voucher->code_id])->first();
                if ($pdf) {
                    $filename = basename($pdf->pdf_filename);
                }
            }
            $response = $this->response;
            $data = [];
            $http = new \Cake\Http\Client();
            $auth_user_email = $this->Auth->user('email');
            $auth_user_id = $this->Auth->user('id');
            if (empty($auth_user_email)) {
                $this->loadModel('Agents');
                $agent = $this->Agents->find()->where(['id' => $voucher->order_item->order->agent_user->agent_id])->first();
                if ($agent) {
                    $auth_user_email = $agent->email;
                    $auth_user_id = $agent->id;
                }
            }
            $headers = $this->_buildHeaders($data, $auth_user_email, $auth_user_id);
            $api_url = Configure::read('Api.url');
            $response = $http->get($api_url . '/voucher/download', ['voucher_id' => $voucher->id], ['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false]);
            if ($response->isOk()) {
                $resp_json = $response->getJson();
                if (!empty($resp_json) && !empty($resp_json['status'])) {
                    // api returns 6002
                } else {
                    if (in_array($voucher->order_item->order->agent_id, Configure::read('Vouchers.display_in_browser.agent_ids'))) {
                        return $this->response->withStringBody($response->getStringBody())->withType('pdf');
                    }
                    return $this->response->withStringBody($response->getStringBody())->withDownload($filename)->withType('pdf');
                }
            }
            $response = $response->withStatus(404);

            return $response;
        } else {
            return $this->response->withStatus(404);
        }

        $this->set('voucher', $voucher);
    }

    private function _buildHeaders($data, $email, $user_id)
    {
        $secret_key = Configure::read('Api.secret');

        $header = [];
        $header['X-API-Version'] = 'v1.10';
        $header['Date'] = date('Y-m-d H:i:s');
        $header['X-CA-User'] = $email;
        $header['X-Api-Role'] = 'admin';
        $header['X-Ca-Nonce'] = \Cake\Utility\Text::uuid();
        $header['X-Ca-SignatureMethod'] = 'HmacSHA256';
        $header['X-CA-UserSecret'] = hash('SHA256', $email . $user_id);
        $textToSign = $header['X-CA-User'] . "\n" .
            $header['Date'] . "\n" .
            $header['X-Ca-SignatureMethod'] . "\n" .
            $header['X-Ca-Nonce'];
        $signature = base64_encode(hash_hmac('SHA256', $textToSign, $secret_key, true));
        $header['Authorization'] = 'Bearer ' . $signature;
        return $header;
    }
}
