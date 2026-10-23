<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Ticket Entity
 *
 * @property $id
 * @property $attraction_id
 * @property string $sku_id
 * @property string $name
 * @property string $description
 * @property string $terms_and_conditions
 * @property int $sequence
 * @property \Cake\I18n\DateTime $publish_start_date
 * @property \Cake\I18n\DateTime $publish_end_date
 * @property float|null $price
 * @property int|null $release_period
 * @property \Cake\I18n\DateTime $redemption_start_date
 * @property \Cake\I18n\DateTime $redemption_end_date
 * @property bool|null $fixed_date_ticket
 * @property string|null $fixed_date_info
 * @property int|null $slot_allotment
 * @property bool|null $instant_confirmation
 * @property int|null $quantity
 * @property string|null $voucher_note
 * @property string|null $type
 * @property |null $parent_id
 * @property string|null $flexi_options
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Attraction $attraction
 * @property \App\Model\Entity\Skus $skus
 * @property \App\Model\Entity\ParentTicket $parent_ticket
 * @property \App\Model\Entity\GeneratedCode[] $generated_codes
 * @property \App\Model\Entity\OrderItem[] $order_items
 * @property \App\Model\Entity\SettlementAgent[] $settlement_agents
 * @property \App\Model\Entity\Settlement[] $settlements
 * @property \App\Model\Entity\ChildTicket[] $child_tickets
 */
class Ticket extends Entity
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
        'sku_id' => true,
        'api_sku' => true,
        'name' => true,
        'description' => true,
        'terms_and_conditions' => true,
        'sequence' => true,
        'publish_start_date' => true,
        'publish_end_date' => true,
        'price' => true,
        'release_period' => true,
        'redemption_start_date' => true,
        'redemption_end_date' => true,
        'fixed_date_ticket' => true,
        'fixed_date_info' => true,
        'slot_allotment' => true,
        'instant_confirmation' => true,
        'quantity' => true,
        'stock' => true,
        'voucher_note' => true,
        'type' => true,
        'parent_id' => true,
        'flexi_options' => true,
        'created' => true,
        'modified' => true,
        'attraction' => true,
        'skus' => true,
        'parent_ticket' => true,
        'generated_codes' => true,
        'order_items' => true,
        'settlement_agents' => true,
        'settlements' => true,
        'child_tickets' => true
    ];

    protected array $_hidden = [
        'cost_price'
    ];
}
