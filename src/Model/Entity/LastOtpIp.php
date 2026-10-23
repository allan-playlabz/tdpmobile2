<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * LastOtpIp Entity
 *
 * @property int $id
 * @property string $agent_user_id
 * @property string $ip
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\AgentUser $agent_user
 */
class LastOtpIp extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array
     */
    protected array $_accessible = [
        'agent_user_id' => true,
        'ip' => true,
        'created' => true,
        'modified' => true,
        'agent_user' => true,
    ];
}
