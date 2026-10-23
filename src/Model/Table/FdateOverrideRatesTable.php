<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * FdateOverrideRates Model
 *
 * @property \App\Model\Table\FdatesettlementsTable&\Cake\ORM\Association\BelongsTo $Fdatesettlements
 * @property \App\Model\Table\FixeddatesTable&\Cake\ORM\Association\BelongsTo $Fixeddates
 */
class FdateOverrideRatesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('fdate_override_rates');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Fdatesettlements', [
            'foreignKey' => 'fdatesettlement_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('Fixeddates', [
            'foreignKey' => 'fdate_id',
            'joinType' => 'INNER'
        ]);
    }

    public function validationDefault(Validator $validator): \Cake\Validation\Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->numeric('rate')
            ->requirePresence('rate', 'create')
            ->notEmptyString('rate');

        $validator
            ->numeric('rebate')
            ->requirePresence('rebate', 'create')
            ->notEmptyString('rebate');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): \Cake\ORM\RulesChecker
    {
        $rules->add($rules->existsIn(['fdatesettlement_id'], 'Fdatesettlements'));
        $rules->add($rules->existsIn(['fdate_id'], 'Fixeddates'));

        return $rules;
    }
}
