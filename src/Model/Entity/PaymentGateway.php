<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * PaymentGateway Entity
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property array|null $data
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\PaymentMarkup[] $payment_markups
 * @property \App\Model\Entity\PaymentMethod[] $payment_methods
 */
class PaymentGateway extends Entity
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
        'description' => true,
        'data' => true,
        'created' => true,
        'modified' => true,
        'payment_markups' => true,
        'payment_methods' => true,
    ];
}
