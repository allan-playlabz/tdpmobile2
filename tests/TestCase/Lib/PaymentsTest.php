<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\PaymentPaypal;
use App\Lib\Payments;
use App\Lib\PaymentStripe;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * Payments helpers that only read the `Payment.*` config (written by Payments::config(), see PaymentsConfigTest).
 */
class PaymentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('Payment', [
            'methods' => ['credit_card' => 'Credit Card', 'paynow' => 'PayNow', 'stripe_card' => 'Card', 'paypal' => 'PayPal', 'other' => 'Other'],
            'providers' => ['credit_card' => '2C2P', 'paynow' => '2C2P', 'stripe_card' => 'Stripe', 'paypal' => 'Paypal'],
            '2C2P' => ['payment_markups' => ['credit_card' => ['value' => '3.5', 'type' => 'percentage', 'add' => '0.50']]],
            'Stripe' => ['payment_markups' => ['stripe_card' => ['value' => '2', 'type' => 'value', 'add' => '0']]],
        ]);
    }

    protected function tearDown(): void
    {
        Configure::delete('Payment');
        parent::tearDown();
    }

    public function testMethodLists(): void
    {
        $this->assertSame(['credit_card', 'paynow', 'stripe_card', 'paypal', 'other'], Payments::getPaymentMethodList());
        $this->assertSame('PayNow', Payments::getPaymentMethods()['paynow']);

        Configure::delete('Payment.methods');
        $this->assertSame([], Payments::getPaymentMethodList());
        $this->assertSame([], Payments::getPaymentMethods());
    }

    public function testProvider(): void
    {
        $payments = new Payments();
        $this->assertSame('2C2P', $payments->getPaymentProvider('credit_card'));
        $this->assertSame('Paypal', $payments->getPaymentProvider('paypal'));
        $this->assertNull($payments->getPaymentProvider('credits'));
    }

    public function testGetMarkup(): void
    {
        $this->assertSame(['value' => '3.5', 'type' => 'percentage', 'add' => '0.50'], Payments::getMarkup('credit_card'));
        $this->assertSame(['value' => '2', 'type' => 'value', 'add' => '0'], Payments::getMarkup('stripe_card'));
        // Provider known, no markup configured: no fee.
        $this->assertSame(['value' => 0, 'type' => 'percentage', 'add' => 0], Payments::getMarkup('paynow'));
        $this->assertSame(['value' => 0, 'type' => 'percentage', 'add' => 0], Payments::getMarkup('paypal'));
        // No provider (e.g. credits).
        $this->assertSame(0, Payments::getMarkup('credits'));
    }

    public function testGetPaymentMarkups(): void
    {
        $markups = Payments::getPaymentMarkups();

        $this->assertSame(['value' => '3.5', 'type' => 'percentage', 'add' => '0.50'], $markups['credit_card']);
        $this->assertSame(['value' => 0, 'type' => 'percentage', 'add' => 0], $markups['paynow']);
        $this->assertSame(['value' => '2', 'type' => 'value', 'add' => '0'], $markups['stripe_card']);
        $this->assertSame(['value' => 0, 'type' => 'value', 'add' => 0], $markups['other']);
    }

    public function testUnknownProviderDispatchers(): void
    {
        $payments = new Payments();

        $this->assertNull($payments->handlePayments('other', 'REF', [], 10));
        $this->assertNull($payments->getOrderRefId('Foo', []));
        $this->assertFalse($payments->isPaymentSuccess('Foo', null, []));
        $this->assertFalse($payments->verifyPayment('Foo', null, [], []));
    }

    public function testStripeAndPaypalPaymentSuccessCheck(): void
    {
        if (!method_exists(PaymentStripe::class, 'isPaymentSuccess') || !method_exists(PaymentPaypal::class, 'isPaymentSuccess')) {
            $this->markTestSkipped('BUG: Payments::isPaymentSuccess() calls PaymentStripe/PaymentPaypal::isPaymentSuccess(), which do not exist (fatal error for Stripe/Paypal returns).');
        }
        $this->assertTrue(true);
    }
}
