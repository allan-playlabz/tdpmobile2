<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Allotments Model
 *
 * @property \App\Model\Table\MerchantsTable&\Cake\ORM\Association\BelongsTo $Merchants
 * @property \App\Model\Table\AutoPopulateAllotmentsTable&\Cake\ORM\Association\HasMany $AutoPopulateAllotments
 * @property \App\Model\Table\SlotsTable&\Cake\ORM\Association\HasMany $Slots
 * @property \App\Model\Table\TicketsTable&\Cake\ORM\Association\HasMany $Tickets
 *
 * @method \App\Model\Entity\Allotment get($primaryKey, $options = [])
 * @method \App\Model\Entity\Allotment newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Allotment[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Allotment|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Allotment saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Allotment patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Allotment[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Allotment findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AllotmentsTable extends Table
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

        $this->setTable('allotments');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Merchants', [
            'foreignKey' => 'merchant_id',
            'joinType' => 'INNER'
        ]);
        $this->hasMany('AutoPopulateAllotments', [
            'foreignKey' => 'allotment_id'
        ]);
        $this->hasMany('Slots', [
            'foreignKey' => 'allotment_id'
        ]);
        $this->hasMany('Tickets', [
            'foreignKey' => 'allotment_id'
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
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('name')
            ->maxLength('name', 100)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->integer('duration')
            ->allowEmptyString('duration');

        $validator
            ->integer('available')
            ->allowEmptyString('available');

        $validator
            ->scalar('pricing_type')
            ->allowEmptyString('pricing_type');

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
        $rules->add($rules->existsIn(['merchant_id'], 'Merchants'));

        return $rules;
    }
}
