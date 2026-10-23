<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * OrderItem Entity
 *
 * @property $id
 * @property $order_id
 * @property $ticket_id
 * @property |null $voucher_id
 * @property int $qty
 * @property float $price
 * @property float $total
 * @property string|null $order_data
 * @property string|null $travellers_data
 * @property \Cake\I18n\Date|null $date_of_visit
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Order $order
 * @property \App\Model\Entity\Ticket $ticket
 * @property \App\Model\Entity\Voucher $voucher
 */
class OrderItem extends Entity
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
        'order_id' => true,
        'ticket_id' => true,
        'voucher_id' => true,
        'qty' => true,
        'price' => true,
        'total' => true,
        'order_data' => true,
        'travellers_data' => true,
        'date_of_visit' => true,
        'created' => true,
        'modified' => true,
        'order' => true,
        'ticket' => true,
        'voucher' => true
    ];
}
