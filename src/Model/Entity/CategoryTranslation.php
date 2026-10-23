<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * CategoryTranslation Entity
 *
 * @property int $id
 * @property $category_id
 * @property string $language
 * @property string $name
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\Category $category
 */
class CategoryTranslation extends Entity
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
        'category_id' => true,
        'language' => true,
        'name' => true,
        'created' => true,
        'modified' => true,
        'category' => true,
    ];
}
