<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * LastOtpIps Model
 *
 * Tracks, per agent user, the most recently OTP-trusted IP addresses. Only the
 * newest 3 rows per agent user are kept; used as the login OTP gate. Full IP
 * history still lives in the users_ipaddress table.
 *
 * @property \App\Model\Table\AgentUsersTable&\Cake\ORM\Association\BelongsTo $AgentUsers
 *
 * @method \App\Model\Entity\LastOtpIp get($primaryKey, $options = [])
 * @method \App\Model\Entity\LastOtpIp newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\LastOtpIp[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\LastOtpIp|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 */
class LastOtpIpsTable extends Table
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

        $this->setTable('last_otp_ips');
        $this->setDisplayField('ip');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('AgentUsers', [
            'foreignKey' => 'agent_user_id',
            'joinType' => 'INNER',
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
            ->scalar('ip')
            ->maxLength('ip', 45)
            ->requirePresence('ip', 'create')
            ->notEmptyString('ip');

        return $validator;
    }

    /**
     * Return the agent user's most recently trusted IP addresses (newest first).
     *
     * @param string $agentUserId The agent user id.
     * @param int $limit Maximum number of IPs to return.
     * @return string[] List of ip address strings.
     */
    public function getLatestIps($agentUserId, $limit = 3)
    {
        return $this->find('list', keyField: 'id', valueField: 'ip')
            ->where(['agent_user_id' => $agentUserId])
            ->orderBy(['modified' => 'DESC', 'id' => 'DESC'])
            ->limit($limit)
            ->toArray();
    }

    /**
     * Record an OTP-trusted IP for the agent user (inserting it, or refreshing
     * its recency if already present), then keep only the newest $keep rows.
     *
     * @param string $agentUserId The agent user id.
     * @param string $ip The client IP address.
     * @param int $keep How many recent IPs to retain per agent user.
     * @return \App\Model\Entity\LastOtpIp The saved row.
     */
    public function addAndPrune($agentUserId, $ip, $keep = 3)
    {
        $row = $this->find()->where(['agent_user_id' => $agentUserId, 'ip' => $ip])->first();
        if ($row) {
            // Bump recency so a re-used IP stays within the newest window.
            $row->modified = date('Y-m-d H:i:s');
        } else {
            $row = $this->newEntity(['agent_user_id' => $agentUserId, 'ip' => $ip]);
        }
        $this->save($row);

        $ids = $this->find()
            ->select(['id'])
            ->where(['agent_user_id' => $agentUserId])
            ->orderBy(['modified' => 'DESC', 'id' => 'DESC'])
            ->extract('id')
            ->toArray();
        if (count($ids) > $keep) {
            $this->deleteAll(['id IN' => array_slice($ids, $keep)]);
        }

        return $row;
    }
}
