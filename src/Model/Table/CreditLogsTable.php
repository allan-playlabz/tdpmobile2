<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CreditLogs Model
 *
 * @property \App\Model\Table\AgentsTable&\Cake\ORM\Association\BelongsTo $Agents
 *
 * @method \App\Model\Entity\CreditLog get($primaryKey, $options = [])
 * @method \App\Model\Entity\CreditLog newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\CreditLog[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\CreditLog|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\CreditLog saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\CreditLog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\CreditLog[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\CreditLog findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CreditLogsTable extends Table
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

        $this->setTable('credit_logs');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Loggable');

        $this->belongsTo('Agents', [
            'foreignKey' => 'agent_id',
            'joinType' => 'INNER'
        ]);

        $this->belongsTo('Orders', [
            'foreignKey' => 'order_id',
            'joinType' => 'LEFT'
        ]);
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
            ->scalar('debit_credit')
            ->requirePresence('debit_credit', 'create')
            ->notEmptyString('debit_credit');

        $validator
            ->numeric('amount')
            ->requirePresence('amount', 'create')
            ->notEmptyString('amount');

        $validator
            ->numeric('credits')
            ->notEmptyString('credits');

        return $validator;
    }

    public function credit($agent_id, $order) {
        // $last_log = $this->find()->where(['agent_id'=>$agent_id])->orderBy(['created'=>'DESC'])->first();
        // $new_log = $this->newEntity(['agent_id'=>$agent_id,'order_id'=>$order->id,'debit_credit'=>'credit','amount'=>$order->total,'credits'=>$last_log ? $last_log->credits - $order->total : $order->total]);
        // if ($this->save($new_log)) {
        //     return true;
        // }
        if ($this->getCOnnection()->execute('INSERT INTO credit_logs (id,agent_id,order_id,debit_credit,amount,credits,created,modified) values(UUID(),?,?,?,?,(SELECT credits FROM credit_logs c_l where agent_id = ? order by created DESC limit 1)-?,NOW(),NOW())',
            [$agent_id,$order->id,'credit',$order->total,$agent_id,$order->total],['binaryuuid','binaryuuid','string','float','binaryuuid','float'])) {
            return true;
        }
        return false;   
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
        $rules->add($rules->existsIn(['agent_id'], 'Agents'));

        return $rules;
    }
}
