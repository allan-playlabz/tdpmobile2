<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * FdatesettlementsAgent Entity
 *
 * @property int $id
 * @property $ticket_id
 * @property int $fdatesettlement_id
 * @property $agent_id
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Ticket $ticket
 * @property \App\Model\Entity\Fdatesettlement $fdatesettlement
 * @property \App\Model\Entity\Agent $agent
 */
class FdatesettlementsAgent extends Entity
{
    protected array $_accessible = [
        'ticket_id' => true,
        'fdatesettlement_id' => true,
        'agent_id' => true,
        'created' => true,
        'modified' => true,
        'ticket' => true,
        'fdatesettlement' => true,
        'agent' => true
    ];
}
