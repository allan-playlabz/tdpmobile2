<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * PackagesTicket Entity
 *
 * @property int $id
 * @property $ticket_id
 * @property $package_ticket_id
 *
 * @property \App\Model\Entity\Ticket $ticket
 * @property \App\Model\Entity\PackageTicket $package_ticket
 */
class PackagesTicket extends Entity
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
        'package_ticket_id' => true,
        'ticket' => true,
        'package_ticket' => true
    ];
}
