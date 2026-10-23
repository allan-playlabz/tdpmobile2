<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Voucher Entity
 *
 * @property $id
 * @property string $voucher_type
 * @property string $code_id
 * @property $order_id
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\Code $code
 * @property \App\Model\Entity\Order $order
 */
class Voucher extends Entity
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
        'voucher_type' => true,
        'code_id' => true,
        'pdf_filename' => true,
        'order_item_id' => true,
        'order_qty_id' => true,
        'created' => true,
        'modified' => true,
        'code' => true,
        'order' => true
    ];
}
