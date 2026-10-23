<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * RecommendedAttraction Entity
 *
 * @property int $id
 * @property string $attraction_id
 * @property int $display_order
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\Attraction $attraction
 */
class RecommendedAttraction extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array
     */
    protected $_accessible = [
        'attraction_id' => true,
        'display_order' => true,
        'created' => true,
        'modified' => true,
        'attraction' => true,
    ];
}
