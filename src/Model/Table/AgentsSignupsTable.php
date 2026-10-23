<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * AgentsSignups Model
 *
 * @property \App\Model\Table\MasterAgentsTable&\Cake\ORM\Association\BelongsTo $MasterAgents
 *
 * @method \App\Model\Entity\AgentsSignup get($primaryKey, $options = [])
 * @method \App\Model\Entity\AgentsSignup newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\AgentsSignup[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\AgentsSignup|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\AgentsSignup saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\AgentsSignup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\AgentsSignup[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\AgentsSignup findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AgentsSignupsTable extends Table
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

        $this->setTable('agents_signups');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('MasterAgents', [
            'foreignKey' => 'master_agent_id',
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
            ->scalar('verification_code')
            ->maxLength('verification_code', 64)
            ->allowEmptyString('verification_code');

        $validator
            ->allowEmptyString('otp');

        $validator
            ->dateTime('otp_created')
            ->allowEmptyDateTime('otp_created');

        $validator
            ->scalar('company_name')
            ->maxLength('company_name', 100)
            ->requirePresence('company_name', 'create')
            ->notEmptyString('company_name');

        $validator
            ->scalar('image')
            ->maxLength('image', 255)
            ->requirePresence('image', 'create')
            ->notEmptyFile('image');

        $validator
            ->scalar('general_category')
            ->maxLength('general_category', 50)
            ->allowEmptyString('general_category');

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
            ->numeric('rebates')
            ->notEmptyString('rebates');

        $validator
            ->scalar('role')
            ->allowEmptyString('role');

        $validator
            ->boolean('enable_generation_confirmation')
            ->allowEmptyString('enable_generation_confirmation');

        $validator
            ->boolean('enable_api')
            ->requirePresence('enable_api', 'create')
            ->notEmptyString('enable_api');

        $validator
            ->scalar('api_key')
            ->maxLength('api_key', 50)
            ->allowEmptyString('api_key');

        $validator
            ->scalar('secret_key')
            ->maxLength('secret_key', 50)
            ->allowEmptyString('secret_key');

        $validator
            ->boolean('allowed_api_cancel')
            ->allowEmptyString('allowed_api_cancel');

        $validator
            ->scalar('email_displayname')
            ->maxLength('email_displayname', 255)
            ->allowEmptyString('email_displayname');

        $validator
            ->scalar('status')
            ->allowEmptyString('status');

        $validator
            ->boolean('can_add_sub')
            ->allowEmptyString('can_add_sub');

        $validator
            ->dateTime('last_activity')
            ->allowEmptyDateTime('last_activity');

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
        $rules->add($rules->existsIn(['master_agent_id'], 'MasterAgents'));

        return $rules;
    }
}
