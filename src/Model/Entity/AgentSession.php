<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * AgentSession Entity
 *
 * @property string $id
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property string|resource|null $data
 * @property int|null $expires
 * @property |null $agent_user_id
 *
 * @property \App\Model\Entity\AgentUser $agent_user
 */
class AgentSession extends Entity
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
        'created' => true,
        'modified' => true,
        'data' => true,
        'expires' => true,
        'agent_user_id' => true,
        'agent_user' => true
    ];
}
