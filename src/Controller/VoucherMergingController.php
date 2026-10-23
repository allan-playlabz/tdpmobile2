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
        $this->VoucherMerging = $this->fetchTable('VoucherMerging');
    }

    public function getStatus()
    {
        $this->autoRender = false;
        $this->AgentUsers = $this->fetchTable('AgentUsers');
        $voucher_merging = $this->VoucherMerging->find()->where(['order_ref_id' => (string)$this->request->getQuery('order_ref_id'),
        'agent_id' => $this->getAuthUser('agent_id')])->first();

        return $this->response->withStatus(200)->withStringBody(json_encode([
            'voucher_merging' => $voucher_merging,
        ]));
    }
}
