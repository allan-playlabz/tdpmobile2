<?php
declare(strict_types=1);

namespace App\Lib;

use App\Model\Entity\AgentUser;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
use Throwable;

/**
 * Two-factor sign-in for agent users.
 *
 * 2FA applies when the agent has OTP on (`agents.mobile_otp`) and the user has
 * `two_factor_enabled`; `two_factor_method` is `authenticator` (TOTP app, RFC 6238,
 * Google / Microsoft Authenticator compatible) or `email_otp` (the emailed code).
 * TOTP secrets are stored encrypted, backup codes as password hashes. App codes
 * are accepted one step either side of now, and a step is never accepted twice.
 */
class TwoFactor
{
    public const METHOD_AUTHENTICATOR = 'authenticator';
    public const METHOD_EMAIL = 'email_otp';

    public const BACKUP_CODE_COUNT = 10;

    private const BACKUP_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Whether the user must pass a second factor (agent OTP switch and the user's own).
     * The user must be loaded with `Agents`.
     *
     * @param \App\Model\Entity\AgentUser $user Agent user
     * @return bool
     */
    public static function required(AgentUser $user): bool
    {
        return !empty($user->agent?->mobile_otp) && !empty($user->two_factor_enabled);
    }

    /**
     * Whether the user's second factor is the authenticator app.
     *
     * @param \App\Model\Entity\AgentUser $user Agent user (with `Agents`)
     * @return bool
     */
    public static function usesAuthenticator(AgentUser $user): bool
    {
        return self::required($user) && $user->two_factor_method === self::METHOD_AUTHENTICATOR;
    }

    /**
     * Whether the user has linked an authenticator app.
     *
     * @param \App\Model\Entity\AgentUser $user Agent user
     * @return bool
     */
    public static function isSetUp(AgentUser $user): bool
    {
        return !empty($user->totp_enabled) && !empty($user->totp_secret);
    }

    /**
     * @return \Cake\ORM\Table
     */
    private static function table(): Table
    {
        return TableRegistry::getTableLocator()->get('AgentUsers');
    }

    /**
     * @return \RobThree\Auth\TwoFactorAuth
     */
    private static function tfa(): TwoFactorAuth
    {
        return new TwoFactorAuth(self::qrProvider(), (string)(Configure::read('TwoFactor.issuer') ?: 'TDP Agent'));
    }

    /**
     * @return \RobThree\Auth\Providers\Qr\BaconQrCodeProvider
     */
    private static function qrProvider(): BaconQrCodeProvider
    {
        return new BaconQrCodeProvider(1, '#ffffff', '#000000', 'svg');
    }

    /**
     * A new Base32 secret (160 bits).
     *
     * @return string
     */
    public static function newSecret(): string
    {
        return self::tfa()->createSecret();
    }

    /**
     * The QR code an authenticator app scans, as inline SVG.
     *
     * @param string $email Account shown in the app
     * @param string $secret Base32 secret
     * @return string
     */
    public static function qrSvg(string $email, string $secret): string
    {
        $svg = self::qrProvider()->getQRCodeImage(self::tfa()->getQRText($email, $secret), 220);

        return (string)preg_replace('/^<\?xml[^>]*>\s*/', '', $svg);
    }

    /**
     * The secret in groups of four, for typing into an app by hand.
     *
     * @param string $secret Base32 secret
     * @return string
     */
    public static function formatSecret(string $secret): string
    {
        return trim(chunk_split($secret, 4, ' '));
    }

    /**
     * Checks a code against a plain secret; returns the matching time step
     * (0 when the code is wrong).
     *
     * @param string $secret Base32 secret
     * @param string $code Code typed by the user
     * @param int|null $time Unix time (now when null)
     * @return int
     */
    public static function matchStep(string $secret, string $code, ?int $time = null): int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (!preg_match('/^\d{6}$/', $code)) {
            return 0;
        }
        $step = 0;
        self::tfa()->verifyCode($secret, $code, 1, $time, $step);

        return (int)$step;
    }

    /**
     * Checks a user's app code and records its time step, so the same code
     * (or an older one) can't be used again.
     *
     * @param \App\Model\Entity\AgentUser $user User with an authenticator set up
     * @param string $code Code typed by the user
     * @param int|null $time Unix time (now when null)
     * @return bool
     */
    public static function verify(AgentUser $user, string $code, ?int $time = null): bool
    {
        $secret = self::decrypt((string)$user->totp_secret);
        if ($secret === '') {
            return false;
        }
        $step = self::matchStep($secret, $code, $time);
        if ($step === 0 || $step <= (int)$user->totp_last_step) {
            return false;
        }
        // Claim the step in one statement, so two requests with the same code can't both pass.
        $claimed = self::table()->updateQuery()
            ->set(['totp_last_step' => $step], ['totp_last_step' => 'biginteger'])
            ->where(['id' => $user->id, 'OR' => ['totp_last_step IS' => null, 'totp_last_step <' => $step]])
            ->execute()
            ->rowCount();
        if ($claimed !== 1) {
            return false;
        }
        $user->totp_last_step = $step;

        return true;
    }

    /**
     * Uses up a backup code; returns the number of codes left, or null when the
     * code is not one of the user's unused codes.
     *
     * @param \App\Model\Entity\AgentUser $user Agent user
     * @param string $code Code typed by the user (dashes and spaces ignored)
     * @return int|null
     */
    public static function useBackupCode(AgentUser $user, string $code): ?int
    {
        $code = strtoupper((string)preg_replace('/[\s-]+/', '', $code));
        if (strlen($code) !== 8) {
            return null;
        }
        $stored = (string)$user->backup_codes;
        $hashes = (array)json_decode($stored, true);
        foreach ($hashes as $i => $hash) {
            if (is_string($hash) && password_verify($code, $hash)) {
                unset($hashes[$i]);
                $hashes = array_values($hashes);
                $remaining = (string)json_encode($hashes);
                // Compare-and-swap: only one of two parallel logins can spend the code.
                $claimed = self::table()->updateAll(['backup_codes' => $remaining], ['id' => $user->id, 'backup_codes' => $stored]);
                if ($claimed !== 1) {
                    return null;
                }
                $user->backup_codes = $remaining;

                return count($hashes);
            }
        }

        return null;
    }

    /**
     * Number of unused backup codes.
     *
     * @param \App\Model\Entity\AgentUser $user Agent user
     * @return int
     */
    public static function backupCodesLeft(AgentUser $user): int
    {
        return count((array)json_decode((string)$user->backup_codes, true));
    }

    /**
     * New backup codes (XXXX-XXXX) and their hashes for storage.
     *
     * @return array{plain: array<string>, hashed: string}
     */
    public static function newBackupCodes(): array
    {
        $plain = [];
        $hashed = [];
        $alphabet = self::BACKUP_ALPHABET;
        $made = 0;
        while ($made < self::BACKUP_CODE_COUNT) {
            $code = '';
            // 32-character alphabet: byte % 32 has no bias.
            foreach (str_split(random_bytes(8)) as $byte) {
                $code .= $alphabet[ord($byte) % strlen($alphabet)];
            }
            if (isset($plain[$code])) {
                continue;
            }
            $plain[$code] = substr($code, 0, 4) . '-' . substr($code, 4);
            $hashed[] = password_hash($code, PASSWORD_DEFAULT);
            $made++;
        }

        return ['plain' => array_values($plain), 'hashed' => (string)json_encode($hashed)];
    }

    /**
     * Saves a confirmed authenticator and fresh backup codes for a user;
     * returns the plain backup codes (shown once).
     *
     * @param \App\Model\Entity\AgentUser $user Agent user
     * @param string $secret Confirmed Base32 secret
     * @param int $step Time step of the confirming code
     * @return array<string>
     */
    public static function enable(AgentUser $user, string $secret, int $step): array
    {
        $codes = self::newBackupCodes();
        $values = [
            'totp_secret' => self::encrypt($secret),
            'totp_enabled' => new DateTime(),
            'totp_last_step' => $step,
            'backup_codes' => $codes['hashed'],
        ];
        // Explicit types: with a stale schema cache a DateTime would be written in the locale format.
        self::table()->updateQuery()
            ->set($values, ['totp_enabled' => 'datetime', 'totp_last_step' => 'biginteger'])
            ->where(['id' => $user->id])
            ->execute();
        foreach ($values as $field => $value) {
            $user->set($field, $value);
        }

        return $codes['plain'];
    }

    /**
     * Replaces a user's backup codes; returns the plain codes (shown once).
     *
     * @param \App\Model\Entity\AgentUser $user Agent user
     * @return array<string>
     */
    public static function regenerateBackupCodes(AgentUser $user): array
    {
        $codes = self::newBackupCodes();
        self::table()->updateAll(['backup_codes' => $codes['hashed']], ['id' => $user->id]);
        $user->backup_codes = $codes['hashed'];

        return $codes['plain'];
    }

    /**
     * Removes a user's authenticator and backup codes (they set it up again
     * at their next login, after an emailed code).
     *
     * @param string $userId Agent user id
     * @return void
     */
    public static function reset(string $userId): void
    {
        self::table()->updateQuery()
            ->set(
                ['totp_secret' => null, 'totp_enabled' => null, 'totp_last_step' => null, 'backup_codes' => null],
                ['totp_enabled' => 'datetime', 'totp_last_step' => 'biginteger'],
            )
            ->where(['id' => $userId])
            ->execute();
    }

    /**
     * Key the secrets are encrypted with: TwoFactor.encryptionKey (TWO_FACTOR_KEY env)
     * so rotating Security.salt doesn't lock every user out of their app; the salt
     * is the fallback when no key is configured.
     *
     * @return string
     */
    private static function key(): string
    {
        return hash('sha256', (string)(Configure::read('TwoFactor.encryptionKey') ?: Security::getSalt()) . '|totp-secret');
    }

    /**
     * Keys a stored secret may have been encrypted with, newest first: the
     * configured key, then the salt (secrets saved before a key was set).
     *
     * @return array<string>
     */
    private static function readKeys(): array
    {
        return array_values(array_unique([self::key(), hash('sha256', Security::getSalt() . '|totp-secret')]));
    }

    /**
     * @param string $secret Plain secret
     * @return string
     */
    public static function encrypt(string $secret): string
    {
        return base64_encode(Security::encrypt($secret, self::key()));
    }

    /**
     * @param string $stored Stored (encrypted) secret
     * @return string Plain secret, '' when it can't be read with any known key
     */
    public static function decrypt(string $stored): string
    {
        $raw = $stored === '' ? false : base64_decode($stored, true);
        if ($raw === false) {
            return '';
        }
        foreach (self::readKeys() as $key) {
            try {
                $plain = Security::decrypt($raw, $key);
            } catch (Throwable) {
                $plain = null;
            }
            if (is_string($plain) && $plain !== '') {
                return $plain;
            }
        }

        return '';
    }
}
