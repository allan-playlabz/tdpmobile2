<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Tag Entity
 *
 * @property int $id
 * @property string $name
 * @property \Cake\I18n\FrozenTime|null $created
 *
 * @property \App\Model\Entity\Attraction[] $attractions
 */
class Tag extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array
     */
    protected $_accessible = [
        'name' => true,
        'created' => true,
        'attractions' => true,
    ];
}
