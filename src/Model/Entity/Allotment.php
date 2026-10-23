<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Allotment Entity
 *
 * @property $id
 * @property $merchant_id
 * @property string $name
 * @property int|null $duration
 * @property int|null $available
 * @property string|null $pricing_type
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Merchant $merchant
 * @property \App\Model\Entity\AutoPopulateAllotment[] $auto_populate_allotments
 * @property \App\Model\Entity\Slot[] $slots
 * @property \App\Model\Entity\Ticket[] $tickets
 */
class Allotment extends Entity
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
        'merchant_id' => true,
        'name' => true,
        'duration' => true,
        'available' => true,
        'pricing_type' => true,
        'created' => true,
        'modified' => true,
        'merchant' => true,
        'auto_populate_allotments' => true,
        'slots' => true,
        'tickets' => true
    ];
}
