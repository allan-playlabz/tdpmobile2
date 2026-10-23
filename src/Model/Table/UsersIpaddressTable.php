<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * UsersIpaddress Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\UsersIpaddres get($primaryKey, $options = [])
 * @method \App\Model\Entity\UsersIpaddres newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\UsersIpaddres[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\UsersIpaddres|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\UsersIpaddres saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\UsersIpaddres patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\UsersIpaddres[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\UsersIpaddres findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UsersIpaddressTable extends Table
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

        $this->setTable('users_ipaddress');
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
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('ip_address')
            ->maxLength('ip_address', 64)
            ->requirePresence('ip_address', 'create')
            ->notEmptyString('ip_address');

        $validator
            ->scalar('type')
            ->maxLength('type', 45)
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

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

        return $rules;
    }

    public function addOrUpdate($data) {
        $clean_data = ['ip_address'=>$data['ip_address'],'user_id'=>$data['user_id'],'type'=>$data['type']];
        $user_ip_address = $this->find()
            ->where($clean_data)->limit(3)->order(['modified'=>'desc'])->first();
        if (empty($user_ip_address)) {
            $user_ip_address = $this->newEntity($clean_data);
        }
        else {
            $clean_data['modified'] = date('Y-m-d H:i:s');
            $user_ip_address = $this->patchEntity($user_ip_address,$clean_data);
        }
        $this->save($user_ip_address);
        return $user_ip_address;
    }
}
