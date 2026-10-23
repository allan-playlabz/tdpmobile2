<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Merchant Entity
 *
 * @property $id
 * @property string $name
 * @property string $email
 * @property string|null $contact_number
 * @property string $business_description
 * @property string $contact_person_name
 * @property string $contact_person_designation
 * @property string $contact_person_number
 * @property string|null $contact_person_email
 * @property string|null $merchant_website
 * @property string|null $status
 * @property bool|null $online_only
 * @property string|null $signature
 * @property string|null $voucher_template
 * @property string|null $voucher_code_type
 * @property bool|null $multiple_redemption
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\Attraction[] $attractions
 * @property \App\Model\Entity\Ticket[] $tickets
 * @property \App\Model\Entity\LandTransfer[] $land_transfers
 * @property \App\Model\Entity\MerchantUser[] $merchant_users
 */
class Merchant extends Entity
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
        'email' => true,
        'contact_number' => true,
        'business_description' => true,
        'contact_person_name' => true,
        'contact_person_designation' => true,
        'contact_person_number' => true,
        'contact_person_email' => true,
        'merchant_website' => true,
        'status' => true,
        'online_only' => true,
        'signature' => true,
        'voucher_template' => true,
        'voucher_code_type' => true,
        'multiple_redemption' => true,
        'created' => true,
        'modified' => true,
        'attractions' => true,
        'tickets' => true,
        'land_transfers' => true,
        'merchant_users' => true
    ];
}
