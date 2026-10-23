<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Attraction Entity
 *
 * @property $id
 * @property |null $merchant_id
 * @property $category_id
 * @property string $title
 * @property string $sku_id
 * @property string|null $note_for_reseller
 * @property int|null $display_position
 * @property string|null $featured_image
 * @property string $description
 * @property string $terms_and_conditions
 * @property string|null $status
 * @property bool|null $print_as_qr_code
 * @property string|null $gallery
 * @property string|null $type
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Merchant $merchant
 * @property \App\Model\Entity\Category $category
 * @property \App\Model\Entity\Skus $skus
 * @property \App\Model\Entity\AttractionMedia[] $attraction_medias
 * @property \App\Model\Entity\AttractionTranslation[] $attraction_translations
 * @property \App\Model\Entity\SettlementAgent[] $settlement_agents
 * @property \App\Model\Entity\Ticket[] $tickets
 */
class Attraction extends Entity
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
        'merchant_id' => true,
        'category_id' => true,
        'title' => true,
        'sku_id' => true,
        'note_for_reseller' => true,
        'display_position' => true,
        'featured_image' => true,
        'description' => true,
        'terms_and_conditions' => true,
        'status' => true,
        'print_as_qr_code' => true,
        'gallery' => true,
        'type' => true,
        'created' => true,
        'modified' => true,
        'merchant' => true,
        'category' => true,
        'skus' => true,
        'attraction_medias' => true,
        'attraction_translations' => true,
        'settlement_agents' => true,
        'tickets' => true
    ];
}
