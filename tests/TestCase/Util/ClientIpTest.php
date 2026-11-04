<?php
declare(strict_types=1);

namespace App\Test\TestCase\Util;

use App\Util\ClientIp;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ClientIpTest extends TestCase
{
    private const CLOUDFLARE_PEER = '173.245.48.10';

    private function request(string $remote, ?string $cf = null): ServerRequest
    {
        $environment = ['REMOTE_ADDR' => $remote];
        if ($cf !== null) {
            $environment['HTTP_CF_CONNECTING_IP'] = $cf;
        }

        return new ServerRequest(['environment' => $environment]);
    }

    public function testWithoutHeaderUsesRemoteAddr(): void
    {
        $this->assertSame('10.1.2.3', ClientIp::fromRequest($this->request('10.1.2.3')));
    }

    public function testCloudflareModeTrustsHeaderFromCloudflarePeer(): void
    {
        Configure::write('Security.cfConnectingIp', 'cloudflare');

        $this->assertSame('8.8.8.8', ClientIp::fromRequest($this->request(self::CLOUDFLARE_PEER, '8.8.8.8')));
    }

    public function testCloudflareModeIgnoresSpoofedHeader(): void
    {
        Configure::write('Security.cfConnectingIp', 'cloudflare');

        $this->assertSame('10.1.2.3', ClientIp::fromRequest($this->request('10.1.2.3', '8.8.8.8')));
    }

    public function testCloudflareModeUsesConfiguredRanges(): void
    {
        Configure::write('Security.cfConnectingIp', 'cloudflare');
        Configure::write('Security.cloudflareRanges', ['10.0.0.0/8']);

        $this->assertSame('8.8.8.8', ClientIp::fromRequest($this->request('10.1.2.3', '8.8.8.8')));
        Configure::delete('Security.cloudflareRanges');
    }

    public function testAlwaysModeTrustsAnyone(): void
    {
        Configure::write('Security.cfConnectingIp', 'always');

        $this->assertSame('8.8.8.8', ClientIp::fromRequest($this->request('10.1.2.3', '8.8.8.8')));
        Configure::write('Security.cfConnectingIp', 'cloudflare');
    }

    public function testNeverModeIgnoresHeader(): void
    {
        Configure::write('Security.cfConnectingIp', 'never');

        $this->assertSame(self::CLOUDFLARE_PEER, ClientIp::fromRequest($this->request(self::CLOUDFLARE_PEER, '8.8.8.8')));
        Configure::write('Security.cfConnectingIp', 'cloudflare');
    }

    public function testInvalidHeaderIsIgnored(): void
    {
        Configure::write('Security.cfConnectingIp', 'always');

        $this->assertSame('10.1.2.3', ClientIp::fromRequest($this->request('10.1.2.3', 'not-an-ip')));
        Configure::write('Security.cfConnectingIp', 'cloudflare');
    }

    public static function rangeProvider(): array
    {
        return [
            'ipv4 inside /20' => ['173.245.63.255', ['173.245.48.0/20'], true],
            'ipv4 outside /20' => ['173.245.64.0', ['173.245.48.0/20'], false],
            'ipv4 non-byte mask /13' => ['104.23.255.1', ['104.16.0.0/13'], true],
            'ipv4 just outside /13' => ['104.24.0.1', ['104.16.0.0/13'], false],
            'ipv4 /32' => ['1.2.3.4', ['1.2.3.4/32'], true],
            'bare ip matches exactly' => ['1.2.3.4', ['1.2.3.4'], true],
            'bare ip other' => ['1.2.3.5', ['1.2.3.4'], false],
            'ipv6 inside' => ['2606:4700::1', ['2606:4700::/32'], true],
            'ipv6 outside' => ['2606:4701::1', ['2606:4700::/32'], false],
            'ipv6 non-byte mask /29' => ['2a06:98c7::1', ['2a06:98c0::/29'], true],
            'ipv4 vs ipv6 range' => ['1.2.3.4', ['2606:4700::/32'], false],
            'invalid ip' => ['nope', ['0.0.0.0/0'], false],
            'invalid range skipped' => ['1.2.3.4', ['garbage/8', '1.2.3.0/24'], true],
            'empty ranges' => ['1.2.3.4', [], false],
        ];
    }

    #[DataProvider('rangeProvider')]
    public function testInRanges(string $ip, array $ranges, bool $expected): void
    {
        $this->assertSame($expected, ClientIp::inRanges($ip, $ranges));
    }
}
