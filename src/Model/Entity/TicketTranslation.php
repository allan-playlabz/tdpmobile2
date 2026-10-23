<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TicketTranslation Entity
 *
 * @property $id
 * @property $ticket_id
 * @property string $language
 * @property string $title
 * @property string $description
 * @property string $terms_and_conditions
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Ticket $ticket
 */
class TicketTranslation extends Entity
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
        'ticket_id' => true,
        'language' => true,
        'title' => true,
        'description' => true,
        'terms_and_conditions' => true,
        'created' => true,
        'modified' => true,
        'ticket' => true,
    ];
}
