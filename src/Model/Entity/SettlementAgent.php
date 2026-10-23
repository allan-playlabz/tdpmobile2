<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SettlementAgent Entity
 *
 * @property int $id
 * @property $attraction_id
 * @property $ticket_id
 * @property int $settlement_id
 * @property $agent_id
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Attraction $attraction
 * @property \App\Model\Entity\Ticket $ticket
 * @property \App\Model\Entity\Settlement $settlement
 * @property \App\Model\Entity\Agent $agent
 */
class SettlementAgent extends Entity
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
        'attraction_id' => true,
        'ticket_id' => true,
        'settlement_id' => true,
        'agent_id' => true,
        'created' => true,
        'modified' => true,
        'attraction' => true,
        'ticket' => true,
        'settlement' => true,
        'agent' => true
    ];
}
