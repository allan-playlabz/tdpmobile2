<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Connection Entity
 *
 * @property $id
 * @property string $name
 * @property string|null $api_url
 * @property string|null $credentials
 * @property string|null $function_name
 * @property string|null $status
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\Ticket[] $tickets
 * @property \App\Model\Entity\Attraction[] $attractions
 */
class Connection extends Entity
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
        'name' => true,
        'api_url' => true,
        'credentials' => true,
        'function_name' => true,
        'status' => true,
        'created' => true,
        'modified' => true,
        'tickets' => true,
        'attractions' => true
    ];
}
