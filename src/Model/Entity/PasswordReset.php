<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * PasswordReset Entity
 *
 * @property int $id
 * @property string $email
 * @property string $reset_code
 * @property string $type
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class PasswordReset extends Entity
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
        'email' => true,
        'reset_code' => true,
        'type' => true,
        'created' => true,
        'modified' => true
    ];
}
