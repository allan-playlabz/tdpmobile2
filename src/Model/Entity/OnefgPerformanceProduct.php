<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * OnefgPerformanceProduct Entity
 *
 * @property int $id
 * @property string $product_ak
 * @property string|null $product_name
 * @property string|null $product_description
 * @property string $performance_ak
 * @property \Cake\I18n\Date|null $performance_date
 * @property \Cake\I18n\DateTime|null $performance_time
 * @property string|null $seat_category_description
 * @property int|null $availability
 * @property float|null $price
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class OnefgPerformanceProduct extends Entity
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
        'product_ak' => true,
        'product_name' => true,
        'product_description' => true,
        'performance_ak' => true,
        'performance_date' => true,
        'performance_time' => true,
        'seat_category_description' => true,
        'availability' => true,
        'price' => true,
        'created' => true,
        'modified' => true
    ];
}
