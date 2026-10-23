<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Announcement Entity
 *
 * @property $id
 * @property string $subject
 * @property string $message
 * @property \Cake\I18n\FrozenTime $publish_start_date
 * @property \Cake\I18n\FrozenTime $publish_end_date
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class Announcement extends Entity
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
        'subject' => true,
        'message' => true,
        'publish_start_date' => true,
        'publish_end_date' => true,
        'created' => true,
        'modified' => true
    ];
}
