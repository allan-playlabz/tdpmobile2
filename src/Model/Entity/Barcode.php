<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Barcode Entity
 *
 * @property $id
 * @property string|null $package_code
 * @property string $code
 * @property string|null $purchased_ticket_sku_id
 * @property string $ticket_sku_id
 * @property \Cake\I18n\FrozenDate $validity_start_date
 * @property \Cake\I18n\FrozenDate $validity_end_date
 * @property string $status
 * @property bool|null $sold
 * @property \Cake\I18n\FrozenTime|null $redeemed_datetime
 * @property string|null $custom_data
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\PurchasedTicketSkus $purchased_ticket_skus
 * @property \App\Model\Entity\TicketSkus $ticket_skus
 */
class Barcode extends Entity
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
        'package_code' => true,
        'code' => true,
        'purchased_ticket_sku_id' => true,
        'ticket_sku_id' => true,
        'validity_start_date' => true,
        'validity_end_date' => true,
        'status' => true,
        'sold' => true,
        'redeemed_datetime' => true,
        'custom_data' => true,
        'created' => true,
        'modified' => true,
        'purchased_ticket_skus' => true,
        'ticket_skus' => true
    ];
}
