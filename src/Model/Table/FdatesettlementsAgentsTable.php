<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * FdatesettlementsAgents Model
 *
 * @property \App\Model\Table\TicketsTable&\Cake\ORM\Association\BelongsTo $Tickets
 * @property \App\Model\Table\FdatesettlementsTable&\Cake\ORM\Association\BelongsTo $Fdatesettlements
 * @property \App\Model\Table\AgentsTable&\Cake\ORM\Association\BelongsTo $Agents
 */
class FdatesettlementsAgentsTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('fdatesettlements_agents');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Tickets', [
            'foreignKey' => 'ticket_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('Fdatesettlements', [
            'foreignKey' => 'fdatesettlement_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('Agents', [
            'foreignKey' => 'agent_id',
            'joinType' => 'INNER'
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        return $validator;
    }

    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['ticket_id'], 'Tickets'));
        $rules->add($rules->existsIn(['fdatesettlement_id'], 'Fdatesettlements'));
        $rules->add($rules->existsIn(['agent_id'], 'Agents'));

        return $rules;
    }
}
