<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\Payment2C2P;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Firebase\JWT\JWT;
use ReflectionMethod;

/**
 * 2C2P JWT handling (firebase/php-jwt 7).
 */
class Payment2C2PTest extends TestCase
{
    private const SECRET = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    private function decode(Payment2C2P $payment, mixed $response): array
    {
        $method = new ReflectionMethod($payment, '_decodeTokenResponse');

        return $method->invoke($payment, $response);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('Payment.2C2P.secret_key', self::SECRET);
    }

    public function testValidTokenIsDecoded(): void
    {
        $payload = ['respCode' => '0000', 'invoiceNo' => 'ABC123', 'amount' => 10.5];
        $token = JWT::encode($payload, self::SECRET, 'HS256');

        $decoded = $this->decode(new Payment2C2P(), ['payload' => $token]);

        $this->assertSame('0000', $decoded['respCode']);
        $this->assertSame('ABC123', $decoded['invoiceNo']);
        $this->assertSame('ABC123', (new Payment2C2P())->getOrderRefId(['payload' => $token]));
    }

    public function testTokenSignedWithAnotherKeyIsRejected(): void
    {
        $token = JWT::encode(['respCode' => '0000', 'invoiceNo' => 'ABC123'], str_repeat('x', 64), 'HS256');

        $this->assertSame([], $this->decode(new Payment2C2P(), ['payload' => $token]));
        $this->assertNull((new Payment2C2P())->getOrderRefId(['payload' => $token]));
    }

    public function testGarbageOrMissingPayloadIsRejected(): void
    {
        $payment = new Payment2C2P();

        $this->assertSame([], $this->decode($payment, ['payload' => 'garbage.token.value']));
        $this->assertSame([], $this->decode($payment, ['payload' => '']));
        $this->assertSame([], $this->decode($payment, []));
        $this->assertSame([], $this->decode($payment, null));
    }
}
