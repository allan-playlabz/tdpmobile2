<?php

namespace App\Model\Table;

use Exception;
use Cake\Log\Log;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\RulesChecker;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Datasource\ConnectionManager;

/**
 * Logs Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property &\Cake\ORM\Association\BelongsTo $Records
 *
 * @method \App\Model\Entity\Log get($primaryKey, $options = [])
 * @method \App\Model\Entity\Log newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Log[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Log|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Log saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Log patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Log[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Log findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class LogsTable extends Table
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

        $this->setTable('logs');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Admins', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT'
        ]);
        $this->belongsTo('Agents', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT'
        ]);
        $this->belongsTo('Merchants', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT'
        ]);
        $this->belongsTo('AgentUsers', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT'
        ]);
        $this->belongsTo('MerchantUsers', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT'
        ]);
        $this->belongsTo('Records', [
            'foreignKey' => 'record_id',
            'joinType' => 'INNER'
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('user_table')
            ->maxLength('user_table', 45)
            ->requirePresence('user_table', 'create')
            ->notEmptyString('user_table');

        $validator
            ->scalar('type')
            ->maxLength('type', 15)
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

        $validator
            ->scalar('table_name')
            ->maxLength('table_name', 40)
            ->requirePresence('table_name', 'create')
            ->notEmptyString('table_name');

        $validator
            ->scalar('values_before')
            ->allowEmptyString('values_before');

        $validator
            ->scalar('values_after')
            ->allowEmptyString('values_after');

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

    /**
     * Creates log not that cannot be created by behavior
     *
     * @param string $type
     * @param string $user_table
     * @param string $table_name
     * @param string $record_id
     * @param string $values_before
     * @param string $values_after
     * @return bool
     */
    public function logCustom($type, $user_table, $table_name, $record_id, $values_before, $values_after) {
        $log = $this->newEntity([
            'user_table' => $user_table,
            'type' => $type,
            'table_name' => $table_name,
            'record_id' => $record_id,
            'values_before' => is_array($values_before) ? json_encode($values_before) : $values_before,
            'values_after' => is_array($values_after) ? json_encode($values_after) : $values_after,
        ]);
        if (!empty($_SESSION['Auth']) && !empty($_SESSION['Auth']['User'])) {
            $log->user_id = $_SESSION['Auth']['User']['id'];
        }
	    $log->created = date('Y-m-d H:i:s');
        $headers = getallheaders();
        $clientIp = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        try {
            if (is_array($headers) && array_key_exists('CF-Connecting-IP', $headers)) {
                $clientIp = $headers['CF-Connecting-IP'];
            }
        } catch (Exception $e) {
            Log::debug($e->getMessage());
        }

        $log->ip_address = $clientIp;

        if (!$this->save($log)) {
            Log::write('info', $log);
            return false;
        }
      	return true;
    }
}
