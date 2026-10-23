<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SettlementAgents Model
 *
 * @property \App\Model\Table\AttractionsTable&\Cake\ORM\Association\BelongsTo $Attractions
 * @property \App\Model\Table\TicketsTable&\Cake\ORM\Association\BelongsTo $Tickets
 * @property \App\Model\Table\SettlementsTable&\Cake\ORM\Association\BelongsTo $Settlements
 * @property \App\Model\Table\AgentsTable&\Cake\ORM\Association\BelongsTo $Agents
 *
 * @method \App\Model\Entity\SettlementAgent get($primaryKey, $options = [])
 * @method \App\Model\Entity\SettlementAgent newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\SettlementAgent[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\SettlementAgent|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\SettlementAgent saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\SettlementAgent patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\SettlementAgent[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\SettlementAgent findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SettlementAgentsTable extends Table
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

        $this->setTable('settlement_agents');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Attractions', [
            'foreignKey' => 'attraction_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('Tickets', [
            'foreignKey' => 'ticket_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('Settlements', [
            'foreignKey' => 'settlement_id',
            'joinType' => 'LEFT'
        ]);
        $this->belongsTo('Agents', [
            'foreignKey' => 'agent_id',
            'joinType' => 'INNER'
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
        $rules->add($rules->existsIn(['attraction_id'], 'Attractions'));
        $rules->add($rules->existsIn(['ticket_id'], 'Tickets'));
        $rules->add($rules->existsIn(['settlement_id'], 'Settlements'));
        $rules->add($rules->existsIn(['agent_id'], 'Agents'));

        return $rules;
    }
}
