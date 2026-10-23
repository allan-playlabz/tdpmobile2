<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\Auth\DefaultPasswordHasher;

/**
 * Agent Entity
 *
 * @property $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $password
 * @property string $company_name
 * @property string $general_category
 * @property string $country
 * @property string|null $issued_bank
 * @property string|null $account_no
 * @property string|null $contact_no
 * @property string $gender
 * @property float|null $credits
 * @property string|null $role
 * @property |null $master_agent_id
 * @property bool|null $enable_generation_confirmation
 * @property string|null $status
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\MasterAgent $master_agent
 * @property \App\Model\Entity\AgentTopup[] $agent_topups
 * @property \App\Model\Entity\AgentUser[] $agent_users
 * @property \App\Model\Entity\CreditLog[] $credit_logs
 * @property \App\Model\Entity\Order[] $orders
 * @property \App\Model\Entity\SettlementAgent[] $settlement_agents
 * @property \App\Model\Entity\Cticket[] $ctickets
 */
class Agent extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'first_name' => true,
        'last_name' => true,
        'email' => true,
        'email_displayname' => true,
        'password' => true,
        'company_name' => true,
        'general_category' => true,
        'country' => true,
        'issued_bank' => true,
        'account_no' => true,
        'contact_no' => true,
        'gender' => true,
        'credits' => true,
        'role' => true,
        'master_agent_id' => true,
        'enable_generation_confirmation' => true,
        'status' => true,
        'last_activity' => true,
        'created' => true,
        'modified' => true,
        'master_agent' => true,
        'agent_topups' => true,
        'agent_users' => true,
        'credit_logs' => true,
        'orders' => true,
        'settlement_agents' => true,
        'ctickets' => true
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array
     */
    protected $_hidden = [
        'password'
    ];

    protected function _getFullname()
    {
        return $this->first_name . '  ' . $this->last_name;
    }

    protected function _setPassword($value){
        $hasher = new DefaultPasswordHasher();
        return $hasher->hash($value);
    }
}
