<?php

namespace App\Controller;

use App\Lib\QueueSender;
use Cake\Core\Configure;
use App\Controller\AppController;

/**
 * VoucherMerging Controller
 *
 * @property \App\Model\Table\VoucherMergingTable $Vouchers
 *
 * @method \App\Model\Entity\VoucherMerging[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class VoucherMergingController extends AppController
{

    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('S3');
        $this->loadModel('VoucherMerging');
    }

    public function getStatus()
    {
        $this->autoRender = false;
        $this->loadModel('AgentUsers');
        $voucher_merging = $this->VoucherMerging->find()->where(['order_ref_id' => $this->request->getQuery('order_ref_id'),
        'agent_id' => $this->Auth->user('agent_id')])->first();

        return $this->response->withStatus(200)->withStringBody(json_encode([
            'voucher_merging' => $voucher_merging,
        ]));
    }
}
