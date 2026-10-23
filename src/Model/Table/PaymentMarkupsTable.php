<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * PaymentMarkups Model
 *
 * @property \App\Model\Table\PaymentMethodsTable&\Cake\ORM\Association\BelongsTo $PaymentMethods
 * @property \App\Model\Table\PaymentGatewaysTable&\Cake\ORM\Association\BelongsTo $PaymentGateways
 *
 * @method \App\Model\Entity\PaymentMarkup get($primaryKey, $options = [])
 * @method \App\Model\Entity\PaymentMarkup newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\PaymentMarkup[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\PaymentMarkup|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\PaymentMarkup saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\PaymentMarkup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\PaymentMarkup[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\PaymentMarkup findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class PaymentMarkupsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('payment_markups');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('PaymentMethods', [
            'foreignKey' => 'payment_method_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('PaymentGateways', [
            'foreignKey' => 'payment_gateway_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->numeric('rate')
            ->requirePresence('rate', 'create')
            ->notEmptyString('rate');

        $validator
            ->scalar('type')
            ->notEmptyString('type');

        $validator
            ->numeric('additional')
            ->notEmptyString('additional');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['payment_method_id'], 'PaymentMethods'));
        $rules->add($rules->existsIn(['payment_gateway_id'], 'PaymentGateways'));

        return $rules;
    }
}
