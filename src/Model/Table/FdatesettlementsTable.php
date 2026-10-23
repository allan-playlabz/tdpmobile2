<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Fdatesettlements Model
 *
 * @property \App\Model\Table\TicketsTable&\Cake\ORM\Association\BelongsTo $Tickets
 * @property \App\Model\Table\FdatesettlementsAgentsTable&\Cake\ORM\Association\HasMany $FdatesettlementsAgents
 * @property \App\Model\Table\FdateOverrideRatesTable&\Cake\ORM\Association\HasMany $FdateOverrideRates
 */
class FdatesettlementsTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('fdatesettlements');
        $this->setDisplayField('label');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Tickets', [
            'foreignKey' => 'ticket_id',
            'joinType' => 'INNER'
        ]);
        $this->hasMany('FdatesettlementsAgents', [
            'foreignKey' => 'fdatesettlement_id'
        ]);
        $this->hasMany('FdateOverrideRates', [
            'foreignKey' => 'fdatesettlement_id'
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->notEmptyString('label');

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

    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['ticket_id'], 'Tickets'));

        return $rules;
    }
}
