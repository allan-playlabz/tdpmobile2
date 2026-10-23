<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * GeneratedCode Entity
 *
 * @property $id
 * @property string $code
 * @property |null $ticket_id
 * @property string|null $status
 * @property int|null $redeemable
 * @property int|null $redeemed
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\Ticket $ticket
 */
class GeneratedCode extends Entity
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
        'code' => true,
        'ticket_id' => true,
        'status' => true,
        'redeemable' => true,
        'ticket_sku_id'=> true,
        'redeemed' => true,
        'created' => true,
        'modified' => true,
        'ticket' => true
    ];
}
