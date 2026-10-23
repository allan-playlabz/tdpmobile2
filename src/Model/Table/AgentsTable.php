<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Agents Model
 *
 * @property \App\Model\Table\MasterAgentsTable&\Cake\ORM\Association\BelongsTo $MasterAgents
 * @property \App\Model\Table\AgentTopupsTable&\Cake\ORM\Association\HasMany $AgentTopups
 * @property \App\Model\Table\AgentUsersTable&\Cake\ORM\Association\HasMany $AgentUsers
 * @property \App\Model\Table\CreditLogsTable&\Cake\ORM\Association\HasMany $CreditLogs
 * @property \App\Model\Table\OrdersTable&\Cake\ORM\Association\HasMany $Orders
 * @property \App\Model\Table\SettlementAgentsTable&\Cake\ORM\Association\HasMany $SettlementAgents
 * @property \App\Model\Table\CticketsTable&\Cake\ORM\Association\BelongsToMany $Ctickets
 *
 * @method \App\Model\Entity\Agent get($primaryKey, $options = [])
 * @method \App\Model\Entity\Agent newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Agent[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Agent|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Agent saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Agent patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Agent[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Agent findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AgentsTable extends Table
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

        $this->setTable('agents');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Loggable');

        $this->belongsTo('MasterAgents', [
            'foreignKey' => 'master_agent_id',
            'className'=>'Agents'
        ]);
        $this->hasMany('AgentTopups', [
            'foreignKey' => 'agent_id'
        ]);
        $this->hasMany('AgentUsers', [
            'foreignKey' => 'agent_id'
        ]);
        $this->hasMany('CreditLogs', [
            'foreignKey' => 'agent_id'
        ]);
        // $this->hasMany('Orders', [
        //     'foreignKey' => 'agent_id'
        // ]);
        $this->belongsToMany('Orders', [
            'through'=>'AgentUsers',
            'foreignKey' => 'agent_id',
            'targetForeignKey'=>'id',
            'bindingKey'=> 'id'
        ]);
        $this->hasMany('SettlementAgents', [
            'foreignKey' => 'agent_id'
        ]);
        $this->belongsToMany('Tickets', [
            'foreignKey' => 'agent_id',
            'targetForeignKey' => 'ticket_id',
            'joinTable' => 'fixeddates_agents'
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
            ->scalar('first_name')
            ->maxLength('first_name', 50)
            ->requirePresence('first_name', 'create')
            ->notEmptyString('first_name');

        $validator
            ->scalar('last_name')
            ->maxLength('last_name', 50)
            ->requirePresence('last_name', 'create')
            ->notEmptyString('last_name');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->scalar('password')
            ->maxLength('password', 64)
            ->allowEmptyString('password');

        $validator
            ->scalar('company_name')
            ->maxLength('company_name', 100)
            ->requirePresence('company_name', 'create')
            ->notEmptyString('company_name');

        $validator
            ->scalar('general_category')
            ->maxLength('general_category', 50)
            ->requirePresence('general_category', 'create')
            ->notEmptyString('general_category');

        $validator
            ->scalar('country')
            ->maxLength('country', 100)
            ->requirePresence('country', 'create')
            ->notEmptyString('country');

        $validator
            ->scalar('issued_bank')
            ->maxLength('issued_bank', 255)
            ->allowEmptyString('issued_bank');

        $validator
            ->scalar('account_no')
            ->maxLength('account_no', 100)
            ->allowEmptyString('account_no');

        $validator
            ->scalar('contact_no')
            ->maxLength('contact_no', 50)
            ->allowEmptyString('contact_no');

        $validator
            ->scalar('gender')
            ->requirePresence('gender', 'create')
            ->notEmptyString('gender');

        $validator
            ->numeric('credits')
            ->allowEmptyString('credits');

        $validator
            ->scalar('role')
            ->allowEmptyString('role');

        $validator
            ->boolean('enable_generation_confirmation')
            ->allowEmptyString('enable_generation_confirmation');

        $validator
            ->scalar('status')
            ->allowEmptyString('status');

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
        $rules->add($rules->isUnique(['email']));

        return $rules;
    }
}
