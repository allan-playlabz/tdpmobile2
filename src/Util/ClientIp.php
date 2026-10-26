<?php
declare(strict_types=1);

namespace App\Util;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;

/**
 * Client IP for security decisions (login rate limiting, trusted-IP OTP skip).
 *
 * `CF-Connecting-IP` is only trusted when the request really comes from Cloudflare,
 * otherwise any client could pick its own IP. Config `Security.cfConnectingIp`:
 * - 'cloudflare' (default): trust the header only if REMOTE_ADDR is in `Security.cloudflareRanges`
 * - 'always': trust the header from anyone (old behaviour; only if the origin is unreachable except via Cloudflare)
 * - 'never': ignore the header
 */
class ClientIp
{
    /**
     * Cloudflare edge ranges (https://www.cloudflare.com/ips/).
     *
     * @var list<string>
     */
    public const CLOUDFLARE_RANGES = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
        '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    /**
     * @param \Cake\Http\ServerRequest $request Request
     * @return string
     */
    public static function fromRequest(ServerRequest $request): string
    {
        $remote = (string)$request->clientIp();
        $cf = trim($request->getHeaderLine('CF-Connecting-IP'));
        if ($cf === '' || filter_var($cf, FILTER_VALIDATE_IP) === false) {
            return $remote;
        }

        $mode = Configure::read('Security.cfConnectingIp', 'cloudflare');
        if ($mode === 'always') {
            return $cf;
        }
        if ($mode === 'cloudflare') {
            $ranges = Configure::read('Security.cloudflareRanges', self::CLOUDFLARE_RANGES);
            $peer = (string)$request->getEnv('REMOTE_ADDR');
            if (self::inRanges($peer, $ranges)) {
                return $cf;
            }
        }

        return $remote;
    }

    /**
     * @param string $ip IP address
     * @param array<string> $ranges CIDR ranges
     * @return bool
     */
    public static function inRanges(string $ip, array $ranges): bool
    {
        $bin = @inet_pton($ip);
        if ($bin === false) {
            return false;
        }
        foreach ($ranges as $cidr) {
            [$net, $bits] = array_pad(explode('/', $cidr, 2), 2, null);
            $netBin = @inet_pton((string)$net);
            if ($netBin === false || strlen($netBin) !== strlen($bin)) {
                continue;
            }
            $bits = $bits === null ? strlen($bin) * 8 : (int)$bits;
            $bytes = intdiv($bits, 8);
            $rest = $bits % 8;
            if (substr($bin, 0, $bytes) !== substr($netBin, 0, $bytes)) {
                continue;
            }
            if ($rest === 0) {
                return true;
            }
            $mask = chr((0xFF << (8 - $rest)) & 0xFF);
            if ((ord($bin[$bytes]) & ord($mask)) === (ord($netBin[$bytes]) & ord($mask))) {
                return true;
            }
        }

        return false;
    }
}
