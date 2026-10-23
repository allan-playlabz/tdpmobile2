<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * PaymentResponses Model
 *
 * @property \App\Model\Table\OrderRevesTable&\Cake\ORM\Association\BelongsTo $OrderReves
 *
 * @method \App\Model\Entity\PaymentResponse get($primaryKey, $options = [])
 * @method \App\Model\Entity\PaymentResponse newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\PaymentResponse[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\PaymentResponse|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\PaymentResponse saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\PaymentResponse patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\PaymentResponse[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\PaymentResponse findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class PaymentResponsesTable extends Table
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

        $this->setTable('payment_responses');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

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
            ->scalar('provider')
            ->maxLength('provider', 45)
            ->requirePresence('provider', 'create')
            ->notEmptyString('provider');

        $validator
            ->scalar('data')
            ->requirePresence('data', 'create')
            ->notEmptyString('data');

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

        return $rules;
    }

    /**
     * Creates a new record of payment response
     *
     * @param string $provider
     * @param array $data
     * @return boolean
     */
    public function create($provider, $data) {
        $payment_response = $this->newEntity([
            'provider' => $provider,
            'data' => json_encode($data)
        ]);
        if ($this->save($payment_response)) {
            return $payment_response;
        }
        debug($payment_response->getErrors());
        return false;
    }

    /**
     * Set Order Ref ID
     *
     * @param \App\Model\Entity\PaymentResponse $payment_response
     * @param string $order_ref_id
     * @return bool
     */
    public function setOrderRefId($payment_response, $order_ref_id) {
        $payment_response->order_ref_id = $order_ref_id;
        if ($this->save($payment_response)) {
            return true;
        }
        return false;
    }
}
