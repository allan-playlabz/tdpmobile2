<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * PaymentGateways Model
 *
 * @property \App\Model\Table\PaymentMarkupsTable&\Cake\ORM\Association\HasMany $PaymentMarkups
 * @property \App\Model\Table\PaymentMethodsTable&\Cake\ORM\Association\HasMany $PaymentMethods
 *
 * @method \App\Model\Entity\PaymentGateway get($primaryKey, $options = [])
 * @method \App\Model\Entity\PaymentGateway newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\PaymentGateway[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\PaymentGateway|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\PaymentGateway saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\PaymentGateway patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\PaymentGateway[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\PaymentGateway findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class PaymentGatewaysTable extends Table
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

        $this->setTable('payment_gateways');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('PaymentMarkups', [
            'foreignKey' => 'payment_gateway_id',
        ]);
        $this->hasMany('PaymentMethods', [
            'foreignKey' => 'payment_gateway_id',
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
            ->scalar('name')
            ->maxLength('name', 45)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->allowEmptyString('description');

        $validator
            ->allowEmptyString('data');

        return $validator;
    }
}
