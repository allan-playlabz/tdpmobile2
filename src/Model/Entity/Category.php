<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Category Entity
 *
 * @property $id
 * @property string $name
 * @property string|null $description
 * @property |null $parent_id
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\ParentCategory $parent_category
 * @property \App\Model\Entity\Attraction[] $attractions
 * @property \App\Model\Entity\ChildCategory[] $child_categories
 * @property \App\Model\Entity\LandTransfer[] $land_transfers
 */
class Category extends Entity
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
        'name' => true,
        'description' => true,
        'parent_id' => true,
        'created' => true,
        'modified' => true,
        'parent_category' => true,
        'attractions' => true,
        'child_categories' => true,
        'land_transfers' => true
    ];
}
