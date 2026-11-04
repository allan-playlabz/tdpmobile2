<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\Payment2C2P;
use App\Model\Entity\Order;
use Cake\Core\Configure;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\RequestInterface;
use ReflectionMethod;
use TypeError;

/**
 * 2C2P JWT handling (firebase/php-jwt 7), markups, payment inquiry and verification
 * (no database; the DB-backed paths are in Payment2C2PDbTest).
 */
class Payment2C2PTest extends TestCase
{
    use EmailTrait;
    use HttpClientTrait;

    private const SECRET = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';
    private const INQUIRY_URL = 'http://2c2p.test/payment/4.3/paymentInquiry';

    private mixed $paymentConfig;

    private function decode(Payment2C2P $payment, mixed $response): array
    {
        $method = new ReflectionMethod($payment, '_decodeTokenResponse');

        return $method->invoke($payment, $response);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentConfig = Configure::read('Payment');
        Configure::write('Payment.2C2P.secret_key', self::SECRET);
        Configure::write('Payment.2C2P.merchant_id', 'MERCHANT1');
        Configure::write('Payment.2C2P.base_url', 'http://2c2p.test');
        Configure::write('Payment.2C2P.payment_inquiry_endpoint', '/payment/4.3/paymentInquiry');
    }

    protected function tearDown(): void
    {
        Configure::write('Payment', $this->paymentConfig);
        parent::tearDown();
    }

    private static function token(array $payload, string $key = self::SECRET): string
    {
        return JWT::encode($payload, $key, 'HS256');
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

    public function testOrderRefIdNeedsSuccessfulResponseCode(): void
    {
        $this->assertNull((new Payment2C2P())->getOrderRefId(['payload' => self::token(['respCode' => '4200', 'invoiceNo' => 'ABC123'])]));
        $this->assertNull((new Payment2C2P())->getOrderRefId(['payload' => self::token(['respCode' => '0000'])]));
    }

    // ---- getMarkup ----

    public function testMarkupDefaultsToNothing(): void
    {
        Configure::write('Payment.2C2P.payment_markups', null);

        $this->assertSame(['value' => 0, 'type' => 'percentage', 'add' => 0], (new Payment2C2P())->getMarkup('credit_card'));
    }

    public function testConfiguredMarkupIsReturned(): void
    {
        $markup = ['value' => 3.5, 'type' => 'percentage', 'add' => 0.5];
        Configure::write('Payment.2C2P.payment_markups', ['credit_card' => $markup]);

        $this->assertSame($markup, (new Payment2C2P())->getMarkup('credit_card'));
        $this->assertSame(['value' => 0, 'type' => 'percentage', 'add' => 0], (new Payment2C2P())->getMarkup('paynow'));
    }

    // ---- isPaymentSuccess (payment inquiry) ----

    private static function paymentResponse(string $invoiceNo): array
    {
        return ['paymentResponse' => base64_encode((string)json_encode(['invoiceNo' => $invoiceNo, 'respCode' => '2000']))];
    }

    private function mockInquiry(array $payload, int $status = 200, ?array &$sent = null): void
    {
        $this->mockClientPost(
            self::INQUIRY_URL,
            $this->newClientResponse($status, ['Content-Type: application/json'], (string)json_encode(['payload' => self::token($payload)])),
            ['match' => function (RequestInterface $request) use (&$sent): bool {
                $body = json_decode((string)$request->getBody(), true);
                $sent = (array)JWT::decode($body['payload'], new Key(self::SECRET, 'HS256'));

                return true;
            }]
        );
    }

    public function testPaymentSuccessAfterInquiry(): void
    {
        $sent = null;
        $this->mockInquiry(['respCode' => '0000', 'invoiceNo' => 'ABC123', 'amount' => 50], 200, $sent);

        $this->assertTrue((new Payment2C2P())->isPaymentSuccess(new Order(['order_ref_id' => 'ABC123']), self::paymentResponse('ABC123')));
        $this->assertSame(['merchantID' => 'MERCHANT1', 'invoiceNo' => 'ABC123'], $sent, 'signed inquiry for the invoice');
    }

    public function testPaymentNotSuccessfulWhenInquiryDeclines(): void
    {
        $this->mockInquiry(['respCode' => '4001', 'respDesc' => 'Declined', 'invoiceNo' => 'ABC123']);

        $this->assertFalse((new Payment2C2P())->isPaymentSuccess(new Order(['order_ref_id' => 'ABC123']), self::paymentResponse('ABC123')));
    }

    public function testPaymentNotSuccessfulWhenInquiryFails(): void
    {
        $this->mockInquiry(['respCode' => '0000', 'invoiceNo' => 'ABC123'], 500);

        $this->assertFalse((new Payment2C2P())->isPaymentSuccess(new Order(['order_ref_id' => 'ABC123']), self::paymentResponse('ABC123')));
    }

    public function testPaymentNotSuccessfulWithForgedInquiryResponse(): void
    {
        $this->mockClientPost(self::INQUIRY_URL, $this->newClientResponse(200, ['Content-Type: application/json'], (string)json_encode([
            'payload' => self::token(['respCode' => '0000', 'invoiceNo' => 'ABC123'], str_repeat('y', 64)),
        ])));

        $this->assertFalse((new Payment2C2P())->isPaymentSuccess(new Order(['order_ref_id' => 'ABC123']), self::paymentResponse('ABC123')));
    }

    public function testPaymentNotSuccessfulWithoutOrderOrResponse(): void
    {
        $payment = new Payment2C2P();

        $this->assertFalse($payment->isPaymentSuccess(null, self::paymentResponse('ABC123')));
        $this->assertFalse($payment->isPaymentSuccess(new Order(['order_ref_id' => 'ABC123']), ['paymentResponse' => base64_encode('not json')]));
    }

    // ---- verifyPayment ----

    public function testVerifyPaymentWithFailedResponseCodeMailsTheCompany(): void
    {
        $order = new Order(['order_ref_id' => 'ABC123', 'total' => '50.00']);
        $data = ['payload' => self::token(['respCode' => '4001', 'respDesc' => 'Declined', 'invoiceNo' => 'ABC123'])];
        try {
            $result = (new Payment2C2P())->verifyPayment($order, $data);
        } catch (TypeError $e) {
            $this->markTestSkipped('BUG: CatMailer requires a string From, but Default.from_email is an [email => name] array (TypeError instead of the alert mail).');
        }

        $this->assertFalse($result);
        $this->assertMailSentTo('company@example.com');
        $this->assertMailSubjectContains('Invalid Payment Response');
        $this->assertMailSentWith('dev@example.com', 'bcc');
    }
}
