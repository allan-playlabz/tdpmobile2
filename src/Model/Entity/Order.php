<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Order Entity
 *
 * @property $id
 * @property string|null $order_ref_id
 * @property $agent_id
 * @property string $customer_name
 * @property string|null $status
 * @property float|null $subtotal
 * @property float|null $convinience_fee
 * @property float|null $total
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\OrderRef $order_ref
 * @property \App\Model\Entity\Agent $agent
 * @property \App\Model\Entity\OrderItem[] $order_items
 */
class Order extends Entity
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
        'order_ref_id' => true,
        'tracking_no' => true,
        'agent_user_id' => true,
        'customer_name' => true,
        'status' => true,
        'subtotal' => true,
        'convinience_fee' => true,
        'total' => true,
        'created' => true,
        'modified' => true,
        'order_ref' => true,
        'agent' => true,
        'order_items' => true
    ];
}
