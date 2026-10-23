<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * AgentTopup Entity
 *
 * @property $id
 * @property |null $agent_id
 * @property float|null $amount
 * @property string|null $image
 * @property \Cake\I18n\Date|null $topup_date
 * @property |null $approved_by
 * @property string|null $status
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Agent $agent
 */
class AgentTopup extends Entity
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
    protected array $_accessible = [
        'agent_id' => true,
        'amount' => true,
        'image' => true,
        'topup_date' => true,
        'approved_by' => true,
        'remarks'=> true,
        'status' => true,
        'created' => true,
        'modified' => true,
        'agent' => true
    ];
}
