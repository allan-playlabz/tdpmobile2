<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Fdatesettlement Entity
 *
 * @property int $id
 * @property $ticket_id
 * @property string $label
 * @property float $rate
 * @property float $rebate
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Ticket $ticket
 * @property \App\Model\Entity\FdatesettlementsAgent[] $fdatesettlements_agents
 * @property \App\Model\Entity\FdateOverrideRate[] $fdate_override_rates
 */
class Fdatesettlement extends Entity
{
    protected array $_accessible = [
        'ticket_id' => true,
        'label' => true,
        'rate' => true,
        'rebate' => true,
        'created' => true,
        'modified' => true,
        'ticket' => true,
        'fdatesettlements_agents' => true,
        'fdate_override_rates' => true
    ];
}
