<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\Turnstile;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\TestSuite\TestCase;
use RuntimeException;

class TurnstileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('Turnstile', ['siteKey' => 'site', 'secretKey' => 'secret']);
    }

    protected function tearDown(): void
    {
        Turnstile::setClient(null);
        Configure::delete('Turnstile');
        parent::tearDown();
    }

    private function answer(array $json): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())->method('post')
            ->with(Turnstile::VERIFY_URL, ['secret' => 'secret', 'response' => 'tok', 'remoteip' => '10.0.0.1'])
            ->willReturn(new Response(['Content-Type: application/json'], (string)json_encode($json)));
        Turnstile::setClient($client);
    }

    public function testEnabledOnlyWithBothKeys(): void
    {
        $this->assertTrue(Turnstile::isEnabled());
        Configure::write('Turnstile.secretKey', '');
        $this->assertFalse(Turnstile::isEnabled());
        $this->assertFalse(Turnstile::verify('tok', null, 'email-otp'));
    }

    public function testSuccess(): void
    {
        $this->answer(['success' => true, 'action' => 'email-otp', 'hostname' => 'example.com']);
        $this->assertTrue(Turnstile::verify('tok', '10.0.0.1', 'email-otp'));
    }

    public function testRejected(): void
    {
        $this->answer(['success' => false, 'error-codes' => ['timeout-or-duplicate']]);
        $this->assertFalse(Turnstile::verify('tok', '10.0.0.1', 'email-otp'));
    }

    public function testWrongAction(): void
    {
        $this->answer(['success' => true, 'action' => 'login']);
        $this->assertFalse(Turnstile::verify('tok', '10.0.0.1', 'email-otp'));
    }

    public function testNetworkErrorFailsClosed(): void
    {
        $client = $this->createStub(Client::class);
        $client->method('post')->willThrowException(new RuntimeException('timeout'));
        Turnstile::setClient($client);
        $this->assertFalse(Turnstile::verify('tok', '10.0.0.1', 'email-otp'));
    }

    public function testEmptyOrHugeTokenIsNotSent(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('post');
        Turnstile::setClient($client);
        $this->assertFalse(Turnstile::verify('', '10.0.0.1', 'email-otp'));
        $this->assertFalse(Turnstile::verify(str_repeat('a', 2049), '10.0.0.1', 'email-otp'));
    }
}
