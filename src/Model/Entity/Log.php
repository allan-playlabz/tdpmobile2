<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Log Entity
 *
 * @property int $id
 * @property $user_id
 * @property string $user_table
 * @property string $type
 * @property string $table_name
 * @property $record_id
 * @property string|null $values_before
 * @property string|null $values_after
 * @property \Cake\I18n\DateTime|null $created
 *
 * @property \App\Model\Entity\User $user
 */
class Log extends Entity
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
        'user_id' => true,
        'user_table' => true,
        'type' => true,
        'table_name' => true,
        'record_id' => true,
        'values_before' => true,
        'values_after' => true,
        'created' => true,
        'user' => true
    ];
}
