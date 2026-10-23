<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ApiPdf Entity
 *
 * @property $id
 * @property string|null $package_code
 * @property string $filename
 * @property string|null $purchased_ticket_sku_id
 * @property string $ticket_sku_id
 * @property string|null $pdf_filename
 * @property \Cake\I18n\Date $validity_start_date
 * @property \Cake\I18n\Date $validity_end_date
 * @property string $status
 * @property bool|null $sold
 * @property \Cake\I18n\DateTime|null $redeemed_datetime
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\PurchasedTicketSkus $purchased_ticket_skus
 * @property \App\Model\Entity\TicketSkus $ticket_skus
 */
class ApiPdf extends Entity
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
        'package_code' => true,
        'code' => true,
        'filename' => true,
        'purchased_ticket_sku_id' => true,
        'ticket_sku_id' => true,
        'pdf_filename' => true,
        'validity_start_date' => true,
        'validity_end_date' => true,
        'status' => true,
        'sold' => true,
        'redeemed_datetime' => true,
        'created' => true,
        'modified' => true,
        'purchased_ticket_skus' => true,
        'ticket_skus' => true
    ];
}
