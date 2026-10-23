<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * RwsProduct Entity
 *
 * @property int $id
 * @property string $offer_no
 * @property string $product_no
 * @property float|null $price
 * @property string|null $theme_park
 * @property string|null $ticket_type
 * @property string|null $choice_constrain
 * @property string|null $admission
 * @property string|null $purchase_with
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class RwsProduct extends Entity
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
        'offer_no' => true,
        'product_no' => true,
        'price' => true,
        'theme_park' => true,
        'ticket_type' => true,
        'choice_constrain' => true,
        'admission' => true,
        'purchase_with' => true,
        'created' => true,
        'modified' => true
    ];
}
