<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * AgentTopups Model
 *
 * @property \App\Model\Table\AgentsTable&\Cake\ORM\Association\BelongsTo $Agents
 *
 * @method \App\Model\Entity\AgentTopup get($primaryKey, $options = [])
 * @method \App\Model\Entity\AgentTopup newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\AgentTopup[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\AgentTopup|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\AgentTopup saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\AgentTopup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\AgentTopup[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\AgentTopup findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AgentTopupsTable extends Table
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

        $this->setTable('agent_topups');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Loggable');

        $this->belongsTo('Agents', [
            'foreignKey' => 'agent_id'
        ]);

        $this->belongsTo('Admins', [
            'foreignKey' => 'approved_by'
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
            ->numeric('amount')
            ->notEmptyString('amount');

        $validator
            ->scalar('image')
            ->maxLength('image', 255)
            ->allowEmptyFile('image');

        $validator
            ->date('topup_date')
            ->allowEmptyDate('topup_date');

        $validator
            ->allowEmptyString('approved_by');

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
        $rules->add($rules->existsIn(['agent_id'], 'Agents'));

        return $rules;
    }
}
