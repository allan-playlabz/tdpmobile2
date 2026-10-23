<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * StripeRecords Model
 *
 * @property \App\Model\Table\PisTable&\Cake\ORM\Association\BelongsTo $Pis
 *
 * @method \App\Model\Entity\StripeRecord get($primaryKey, $options = [])
 * @method \App\Model\Entity\StripeRecord newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\StripeRecord[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\StripeRecord|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\StripeRecord saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\StripeRecord patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\StripeRecord[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\StripeRecord findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class StripeRecordsTable extends Table
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

        $this->setTable('stripe_records');
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
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

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
    public function buildRules(RulesChecker $rules)
    {

        return $rules;
    }
}
