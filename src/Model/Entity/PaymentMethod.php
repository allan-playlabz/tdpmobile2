<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * PaymentMethod Entity
 *
 * @property int $id
 * @property string $name
 * @property int|null $payment_gateway_id
 * @property string|null $status
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\PaymentGateway $payment_gateway
 * @property \App\Model\Entity\PaymentMarkup[] $payment_markups
 */
class PaymentMethod extends Entity
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
        'name' => true,
        'payment_gateway_id' => true,
        'status' => true,
        'created' => true,
        'modified' => true,
        'payment_gateway' => true,
        'payment_markups' => true,
    ];
}
