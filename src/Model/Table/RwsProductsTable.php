<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * RwsProducts Model
 *
 * @method \App\Model\Entity\RwsProduct get($primaryKey, $options = [])
 * @method \App\Model\Entity\RwsProduct newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\RwsProduct[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\RwsProduct|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\RwsProduct saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\RwsProduct patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\RwsProduct[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\RwsProduct findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class RwsProductsTable extends Table
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

        $this->setTable('rws_products');
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
            ->scalar('offer_no')
            ->maxLength('offer_no', 255)
            ->requirePresence('offer_no', 'create')
            ->notEmptyString('offer_no');

        $validator
            ->scalar('product_no')
            ->maxLength('product_no', 100)
            ->requirePresence('product_no', 'create')
            ->notEmptyString('product_no')
            ->add('product_no', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->numeric('price')
            ->allowEmptyString('price');

        $validator
            ->scalar('theme_park')
            ->maxLength('theme_park', 255)
            ->allowEmptyString('theme_park');

        $validator
            ->scalar('ticket_type')
            ->maxLength('ticket_type', 100)
            ->allowEmptyString('ticket_type');

        $validator
            ->scalar('choice_constrain')
            ->allowEmptyString('choice_constrain');

        $validator
            ->scalar('admission')
            ->maxLength('admission', 100)
            ->allowEmptyString('admission');

        $validator
            ->scalar('purchase_with')
            ->maxLength('purchase_with', 255)
            ->allowEmptyString('purchase_with');

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
        $rules->add($rules->isUnique(['product_no']));

        return $rules;
    }
}
