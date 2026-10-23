<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * OrdersToComplete Entity
 *
 * @property int $id
 * @property string $order_id
 * @property \Cake\I18n\DateTime|null $check_after
 * @property string|null $status
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Order $order
 */
class OrdersToComplete extends Entity
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
        'check_after' => true,
        'status' => true,
        'created' => true,
        'modified' => true,
        'order' => true
    ];
}
