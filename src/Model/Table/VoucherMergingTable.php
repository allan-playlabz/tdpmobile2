<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;


/**
 * VoucherMerging Model
 *
 * @property \App\Model\Table\JobsTable&\Cake\ORM\Association\BelongsTo $Jobs
 * @property \App\Model\Table\AgentsTable&\Cake\ORM\Association\BelongsTo $Agents
 * @property \App\Model\Table\OrdersTable&\Cake\ORM\Association\BelongsTo $Orders
 *
 * @method \App\Model\Entity\VoucherMerging get($primaryKey, $options = [])
 * @method \App\Model\Entity\VoucherMerging newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\VoucherMerging[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\VoucherMerging|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\VoucherMerging saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\VoucherMerging patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\VoucherMerging[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\VoucherMerging findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class VoucherMergingTable extends Table
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

        $this->setTable('voucher_merging');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Jobs', [
            'foreignKey' => 'job_id',
        ]);
        $this->belongsTo('Agents', [
            'foreignKey' => 'agent_id',
        ]);
        $this->belongsTo('Orders', [
            'foreignKey' => 'order_id',
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('type')
            ->allowEmptyString('type');

        $validator
            ->scalar('status')
            ->allowEmptyString('status');

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
        $rules->add($rules->existsIn(['agent_id'], 'Agents'));
        $rules->add($rules->existsIn(['order_id'], 'Orders'));

        return $rules;
    }

    /**
     * Checks if job exists
     *
     * @param string $order_ref_id
     * @param string $agent_id
     * @return boolean
     */
    public function isJobExists($order_ref_id, $agent_id) {
        $voucher_merging = $this->find()->where([
            'order_ref_id' => $order_ref_id,
            'agent_id' => $agent_id,
        ])->first();
        if ($voucher_merging) {
            return true;
        }

        return false;
    }
}
