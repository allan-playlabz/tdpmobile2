<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * PaymentMarkup Entity
 *
 * @property int $id
 * @property int $payment_method_id
 * @property int $payment_gateway_id
 * @property float $rate
 * @property string $type
 * @property float $additional
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\PaymentMethod $payment_method
 * @property \App\Model\Entity\PaymentGateway $payment_gateway
 */
class PaymentMarkup extends Entity
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
        'payment_method_id' => true,
        'payment_gateway_id' => true,
        'rate' => true,
        'type' => true,
        'additional' => true,
        'created' => true,
        'modified' => true,
        'payment_method' => true,
        'payment_gateway' => true,
    ];
}
