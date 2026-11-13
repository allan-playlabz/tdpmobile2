<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Log\Log;
use Throwable;

/**
 * Cloudflare Turnstile (bot check) for the "try another way" sign-in.
 *
 * Keys: Turnstile.siteKey / Turnstile.secretKey (TURNSTILE_SITE_KEY / TURNSTILE_SECRET_KEY env).
 * Without both keys the feature is off.
 */
class Turnstile
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    private static ?Client $client = null;

    /**
     * Whether both keys are configured.
     */
    public static function isEnabled(): bool
    {
        return self::siteKey() !== '' && (string)Configure::read('Turnstile.secretKey') !== '';
    }

    /**
     * Public site key for the widget.
     */
    public static function siteKey(): string
    {
        return (string)Configure::read('Turnstile.siteKey');
    }

    /**
     * Replace the HTTP client (tests); null restores the default.
     */
    public static function setClient(?Client $client): void
    {
        self::$client = $client;
    }

    /**
     * Checks a widget token with Cloudflare. Fails closed (false) on any error.
     *
     * @param string $token The `cf-turnstile-response` form value.
     * @param string|null $ip Client IP, sent as `remoteip`.
     * @param string $action Expected widget `data-action`.
     */
    public static function verify(string $token, ?string $ip, string $action): bool
    {
        if (!self::isEnabled() || $token === '' || strlen($token) > 2048) {
            return false;
        }
        $data = ['secret' => (string)Configure::read('Turnstile.secretKey'), 'response' => $token];
        if ($ip !== null && $ip !== '') {
            $data['remoteip'] = $ip;
        }
        try {
            $client = self::$client ?? new Client(['timeout' => 10]);
            $response = $client->post(self::VERIFY_URL, $data);
            $json = $response->getJson();
        } catch (Throwable $e) {
            Log::error('Turnstile verify failed: ' . $e->getMessage());

            return false;
        }
        if (!is_array($json) || ($json['success'] ?? false) !== true) {
            Log::debug('Turnstile rejected: ' . json_encode($json['error-codes'] ?? $json));

            return false;
        }
        // Cloudflare's test keys answer without an action; a real widget always sends ours.
        if (isset($json['action']) && $json['action'] !== $action) {
            Log::debug('Turnstile action mismatch: ' . json_encode($json['action']));

            return false;
        }

        return true;
    }
}
