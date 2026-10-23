<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Vouchers Model
 *
 * @property \App\Model\Table\CodesTable&\Cake\ORM\Association\BelongsTo $Codes
 * @property \App\Model\Table\OrdersTable&\Cake\ORM\Association\BelongsTo $Orders
 *
 * @method \App\Model\Entity\Voucher get($primaryKey, $options = [])
 * @method \App\Model\Entity\Voucher newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Voucher[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Voucher|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Voucher saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Voucher patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Voucher[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Voucher findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class VouchersTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('vouchers');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('OrderItems', [
            'foreignKey' => 'order_item_id',
            'joinType' => 'INNER'
        ]);

        $this->hasOne('GeneratedCodes', [
            'foreignKey' => 'code',
            'bindingKey' => 'code_id',
            'joinType' => 'INNER'
        ]);

        $this->belongsTo('Barcodes', [
            'foreignKey' => 'code_id',
            'bindingKey'=> 'code',
            // 'conditions'=>['voucher_type'=>'barcode'],
            'joinType' => 'LEFT'
        ]);

        $this->belongsTo('ApiPdfs', [
            'foreignKey' => 'code_id',
            'bindingKey'=> 'code',
            // 'conditions'=>['voucher_type'=>'barcode'],
            'joinType' => 'LEFT'
        ]);

        $this->belongsTo('Pdfs', [
            'foreignKey' => 'code_id',
            'bindingKey'=> 'id',
            // 'conditions'=>['voucher_type'=>'pdf'],
            'joinType' => 'LEFT'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): \Cake\Validation\Validator
    {
        $validator
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('voucher_type')
            ->requirePresence('voucher_type', 'create')
            ->notEmptyString('voucher_type');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): \Cake\ORM\RulesChecker
    {
        $rules->add($rules->existsIn(['order_item_id'], 'OrderItems'));
        $rules->add($rules->isUnique(['code_id']));

        return $rules;
    }

    public function isCancellable($code, $agent_id)
    {
        $Agents = TableRegistry::getTableLocator()->get('Agents');
        
        $agent = $Agents->find()->where(['id' => $agent_id])->first();
        // is agent allowed to cancel
        if (!$agent || !$agent->allowed_api_cancel) {
            return false;
        }
        $voucher = $this->find()
            ->where(['code_id' => $code, 'Vouchers.voucher_type' => 'generated'])
            ->contain(['OrderItems' => ['Tickets','Orders'], 'GeneratedCodes'])
            ->first();
        // is voucher generated
        if (!$voucher) {
            return false;
        }
        // is ticket api cancellable
        if (!$voucher->order_item->ticket->api_cancellable) {
            return false;
        }
        // is ticket not api
        if ($voucher->order_item->ticket->connection_id) {
            return false;
        }
        // cannot cancel redeemed 
        if ($voucher->generated_code->status != 'purchased') {
            return false;
        }
        // package subticket
        if ($voucher->generated_code->package_code) {
            return false;
        }
        // flexi generated codes no redeemed yet
        if ($voucher->order_item->ticket->flexi_options) {
            $generated_code = $voucher->generated_code;
            $generated_code = $this->GeneratedCodes->loadInto($generated_code, ['ChildGeneratedCodes' => ['Tickets']]);
            if (!empty($generated_code->child_generated_codes)) {
                foreach ($generated_code->child_generated_codes as $child_generated_code) {
                    if ($child_generated_code->status != 'purchased') {
                        return false;
                    }
                }
            }
            // check if there are barcodes or pdfs in this flexi
            $pdf_count = $this->Pdfs->find()->where(['package_code' => $code])->count();
            if ($pdf_count) {
                return false;
            }
            $barcode_count = $this->Barcodes->find()->where(['package_code' => $code])->count();
            if ($barcode_count) {
                return false;
            }
        }
        // can only cancel credits
        if ($voucher->order_item->order->payment_method != 'credits') {
            return false;
        }
        return true;
    }


    public function getStatus($code, $type = null) {
        if (!$type) {
            $voucher = $this->find()->where(['code' => $code])->first();
            if ($voucher) {
                $type = $voucher->voucher_type;
            }
        }
        if (!$type) {
            return null;
        }
        if ($type == 'generated') {
            $generated_code = $this->GeneratedCodes->find()->where(['code'=>$code])->first();
            if (!$generated_code) {
                return null;
            }
            return $generated_code->status;
        }
        if ($type == 'barcode') {
            $barcode = $this->Barcodes->find()->where(['code'=>$code])->first();
            if (!$barcode) {
                return null;
            }
            return $barcode->status;
        }
        if ($type == 'pdf') {
            $pdf = $this->Pdfs->find()->where(['id'=>$code])->first();
            if (!$pdf) {
                return null;
            }
            return $pdf->status;
        }
        if ($type == 'api') {
            $api_pdf = null;
            if (strpos($code, '-') !== false) {
                $api_pdf = $this->ApiPdfs->find()->where(['id'=>$code])->first();
            } else {
                $api_pdf = $this->ApiPdfs->find()->where(['code'=>$code])->first();
            }
            if (!$api_pdf) {
                return null;
            }
            return $api_pdf->status;
        }
    }
}
