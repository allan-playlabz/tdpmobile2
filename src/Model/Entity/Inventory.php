<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Inventory Entity
 *
 * @property int $id
 * @property $ticket_id
 * @property |null $order_id
 * @property $user_id
 * @property int|null $quantity
 * @property int|null $sold
 * @property int|null $stock
 * @property string|null $type
 * @property \Cake\I18n\FrozenTime|null $created
 *
 * @property \App\Model\Entity\Ticket $ticket
 * @property \App\Model\Entity\Order $order
 * @property \App\Model\Entity\User $user
 */
class Inventory extends Entity
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
        'order_id' => true,
        'user_id' => true,
        'quantity' => true,
        'current_quantity' => true,
        'sold' => true,
        'stock' => true,
        'type' => true,
        'created' => true,
        'ticket' => true,
        'order' => true,
        'user' => true
    ];
}
