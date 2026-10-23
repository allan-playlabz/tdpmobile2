<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Fixeddate Entity
 *
 * @property int $id
 * @property $ticket_id
 * @property \Cake\I18n\FrozenDate|null $date
 * @property \Cake\I18n\FrozenTime|null $time
 * @property int|null $quantity
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 *
 * @property \App\Model\Entity\Ticket $ticket
 */
class Fixeddate extends Entity
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
        'date' => true,
        'time' => true,
        'quantity' => true,
        'created' => true,
        'modified' => true,
        'ticket' => true
    ];
}
