<?php
namespace App\Model\Table;

use App\Util\ClientIp;
use Cake\ORM\Table;
use Cake\Core\Configure;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * Logins Model
 *
 * @method \App\Model\Entity\Login get($primaryKey, $options = [])
 * @method \App\Model\Entity\Login newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Login[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Login|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Login saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Login patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Login[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Login findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class LoginsTable extends Table
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

        $this->setTable('logins');
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
            ->scalar('ip')
            ->maxLength('ip', 64)
            ->requirePresence('ip', 'create')
            ->notEmptyString('ip');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->scalar('system')
            ->allowEmptyString('system');

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
        // $rules->add($rules->isUnique(['email','ip', 'system']));

        return $rules;
    }

    /**
     * Determins whether login request is allowed or not
     *
     * @param Request $request
     * @return boolean
     */
    public function isAllowed($request) {
        
        $max_login_minutes = Configure::read('Login.max_minutes');
        $max_login_count = Configure::read('Login.max_count_per_max_minutes');

        /* Delete first logins more than x minutes ago */
        $this->deleteAll(['created < ' => \Cake\I18n\DateTime::now()->subMinutes($max_login_minutes)]);

        $clientIp = ClientIp::fromRequest($request);
        $email = $request->getData('email');
        $system = 'agent';
        $login_count = $this->find()->where([
            'ip' => $clientIp,
            'email' => $email,
            'loc' => $system
        ])->count();
        
        $allowed = $login_count < $max_login_count;

        if ($allowed) {
            $this->save($this->newEntity([
                'ip' => $clientIp,
                'email' => $email,
                'loc' => $system
            ]));
        }

        return $allowed;
    }

    /**
     * Clears login records when login success
     *
     * @param Request $request
     * @return void
     */
    public function clearLogin($request) {
        $clientIp = ClientIp::fromRequest($request);
        $this->deleteAll(['ip' => $clientIp, 'loc' => 'agent']);
    }
}
