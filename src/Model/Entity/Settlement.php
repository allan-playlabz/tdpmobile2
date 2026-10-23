<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Settlement Entity
 *
 * @property int $id
 * @property $ticket_id
 * @property int $settlement_group
 * @property float $rate
 * @property float $rebate
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\Ticket $ticket
 * @property \App\Model\Entity\SettlementAgent[] $settlement_agents
 */
class Settlement extends Entity
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
        'ticket_id' => true,
        'settlement_group' => true,
        'rate' => true,
        'rebate' => true,
        'created' => true,
        'modified' => true,
        'ticket' => true,
        'settlement_agents' => true
    ];
}
