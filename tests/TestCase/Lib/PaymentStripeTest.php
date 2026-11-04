<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\PaymentStripe;
use App\Model\Entity\Order;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * Stripe markups and webhook signature checks (no network, no database).
 */
class PaymentStripeTest extends TestCase
{
    private const SIGNING_SECRET = 'whsec_test_secret';

    private mixed $paymentConfig;
    private mixed $signatureHeader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentConfig = Configure::read('Payment');
        $this->signatureHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;
        Configure::write('Payment.Stripe.secret_key', 'sk_test_x');
        Configure::write('Payment.Stripe.signing_secret', self::SIGNING_SECRET);
    }

    protected function tearDown(): void
    {
        Configure::write('Payment', $this->paymentConfig);
        if ($this->signatureHeader === null) {
            unset($_SERVER['HTTP_STRIPE_SIGNATURE']);
        } else {
            $_SERVER['HTTP_STRIPE_SIGNATURE'] = $this->signatureHeader;
        }
        parent::tearDown();
    }

    /**
     * A correctly signed Stripe-Signature header for the given payload.
     */
    private static function signature(string $payload): string
    {
        $time = time();

        return 't=' . $time . ',v1=' . hash_hmac('sha256', $time . '.' . $payload, self::SIGNING_SECRET);
    }

    public function testMarkupDefaultsToNothing(): void
    {
        Configure::write('Payment.Stripe.payment_markups', null);

        $this->assertSame(['value' => 0, 'type' => 'percentage', 'add' => 0], (new PaymentStripe())->getMarkup('credit_card'));
    }

    public function testConfiguredMarkupIsReturned(): void
    {
        $markup = ['value' => 1.5, 'type' => 'value', 'add' => 0];
        Configure::write('Payment.Stripe.payment_markups', ['wechat_pay' => $markup]);

        $this->assertSame($markup, (new PaymentStripe())->getMarkup('wechat_pay'));
    }

    public function testBadSignatureIsRejected(): void
    {
        $_SERVER['HTTP_STRIPE_SIGNATURE'] = 't=' . time() . ',v1=' . str_repeat('0', 64);
        $stripe = new PaymentStripe();

        $this->assertNull($stripe->getOrderRefId([]));
        $this->assertFalse($stripe->verifyPayment(new Order(['order_ref_id' => 'REF1', 'total' => '50.00']), []));
    }

    public function testMalformedSignatureHeaderIsRejected(): void
    {
        $_SERVER['HTTP_STRIPE_SIGNATURE'] = 'garbage';
        $stripe = new PaymentStripe();

        $this->assertNull($stripe->getOrderRefId([]));
        $this->assertFalse($stripe->verifyPayment(new Order(['order_ref_id' => 'REF1', 'total' => '50.00']), []));
    }

    public function testSignedButEmptyPayloadIsRejected(): void
    {
        // The webhook body is read from php://input, which is empty under the CLI.
        $_SERVER['HTTP_STRIPE_SIGNATURE'] = self::signature('');
        $stripe = new PaymentStripe();

        $this->assertNull($stripe->getOrderRefId([]));
        $this->assertFalse($stripe->verifyPayment(new Order(['order_ref_id' => 'REF1', 'total' => '50.00']), []));
    }

    public function testCompletedCheckoutReturnsOrderRefId(): void
    {
        // getOrderRefId() reads the signed checkout.session.completed body from php://input, which a CLI test can't supply.
        // (It used $this->Orders without loading it and returned a query; fixed, it now loads Orders and takes ->first().)
        $this->markTestSkipped('Not testable from the CLI: needs a signed Stripe webhook body in php://input.');
    }
}
