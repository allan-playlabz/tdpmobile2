<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * AgentsSignup Entity
 *
 * @property $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $password
 * @property string|null $verification_code
 * @property int|null $otp
 * @property \Cake\I18n\FrozenTime|null $otp_created
 * @property string $company_name
 * @property string $image
 * @property string|null $general_category
 * @property string $country
 * @property string|null $issued_bank
 * @property string|null $account_no
 * @property string|null $contact_no
 * @property string $gender
 * @property float|null $credits
 * @property float $rebates
 * @property string|null $role
 * @property |null $master_agent_id
 * @property bool|null $enable_generation_confirmation
 * @property bool $enable_api
 * @property string|null $api_key
 * @property string|null $secret_key
 * @property bool|null $allowed_api_cancel
 * @property string|null $email_displayname
 * @property string|null $status
 * @property bool|null $can_add_sub
 * @property \Cake\I18n\FrozenTime|null $last_activity
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\MasterAgent $master_agent
 */
class AgentsSignup extends Entity
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
        'first_name' => true,
        'last_name' => true,
        'email' => true,
        'password' => true,
        'verification_code' => true,
        'otp' => true,
        'otp_created' => true,
        'company_name' => true,
        'image' => true,
        'general_category' => true,
        'country' => true,
        'issued_bank' => true,
        'account_no' => true,
        'contact_no' => true,
        'gender' => true,
        'credits' => true,
        'rebates' => true,
        'role' => true,
        'master_agent_id' => true,
        'enable_generation_confirmation' => true,
        'enable_api' => true,
        'api_key' => true,
        'secret_key' => true,
        'allowed_api_cancel' => true,
        'email_displayname' => true,
        'status' => true,
        'can_add_sub' => true,
        'last_activity' => true,
        'created' => true,
        'modified' => true,
        'master_agent' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array
     */
    protected $_hidden = [
        'password',
    ];
}
