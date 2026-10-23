<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Login Entity
 *
 * @property int $id
 * @property string $ip
 * @property string $email
 * @property string|null $loc
 * @property \Cake\I18n\FrozenTime|null $created
 */
class Login extends Entity
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
        'ip' => true,
        'email' => true,
        'loc' => true,
        'created' => true,
    ];
}
