<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;
use Authentication\PasswordHasher\DefaultPasswordHasher;

/**
 * AgentUser Entity
 *
 * @property $id
 * @property $agent_id
 * @property string $role
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $status
 * @property bool $two_factor_enabled
 * @property string $two_factor_method 'authenticator' or 'email_otp'
 * @property string|null $totp_secret Encrypted TOTP secret
 * @property \Cake\I18n\DateTime|null $totp_enabled
 * @property int|null $totp_last_step
 * @property string|null $backup_codes JSON list of password hashes
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Agent $agent
 * @property \App\Model\Entity\Order[] $orders
 */
class AgentUser extends Entity
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
    // agent_id, role, status, credits, rebates and associations are never mass-assigned:
    // controllers set them explicitly after checking the logged-in user's rights.
    protected array $_accessible = [
        'name' => true,
        'email' => true,
        'password' => true,
        'last_read_time' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array
     */

    // Kept out of toArray(), so out of the Auth.User session data and logs.
    protected array $_hidden = [
        'password',
        'otp',
        'totp_secret',
        'totp_last_step',
        'backup_codes',
    ];

    protected function _setPassword($value){
        $hasher = new DefaultPasswordHasher();
        return $hasher->hash($value);
    }
}
