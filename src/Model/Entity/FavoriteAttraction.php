<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * FavoriteAttraction Entity
 *
 * @property int $id
 * @property $attraction_id
 * @property |null $agent_user_id
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Attraction $attraction
 * @property \App\Model\Entity\AgentUser $agent_user
 */
class FavoriteAttraction extends Entity
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
        'attraction_id' => true,
        'agent_user_id' => true,
        'created' => true,
        'modified' => true,
        'attraction' => true,
        'agent_user' => true,
    ];
}
