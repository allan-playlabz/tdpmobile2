<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\Auth\DefaultPasswordHasher;

/**
 * AgentUser Entity
 *
 * @property $id
 * @property $agent_id
 * @property string $role
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $status
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\Agent $agent
 * @property \App\Model\Entity\Order[] $orders
 */
class AgentUser extends Entity
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
        'agent_id' => true,
        'role' => true,
        'name' => true,
        'email' => true,
        'password' => true,
        'credits' => true,
        'rebates' => true,
        'status' => true,
        'last_read_time' => true,
        'created' => true,
        'modified' => true,
        'agent' => true,
        'orders' => true
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array
     */

    protected $_hidden = [
        'password'
    ];

    protected function _setPassword($value){
        $hasher = new DefaultPasswordHasher();
        return $hasher->hash($value);
    }
}
