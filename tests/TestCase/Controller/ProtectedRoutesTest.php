<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\Support\AppDataTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Every routed page except the public ones sends a logged-out visitor to the login page.
 */
class ProtectedRoutesTest extends TestCase
{
    use AppDataTrait;
    use IntegrationTestTrait;

    protected array $fixtures = [
        ...self::USER_FIXTURES,
        ...self::ORDER_FIXTURES,
    ];

    private const ID = '00000000-0000-4000-8000-000000000123';

    public static function protectedUrlProvider(): array
    {
        $urls = [
            '/', '/attractions', '/attractions/view/' . self::ID, '/attractions/two-way/' . self::ID,
            '/attractions/get-tickets/' . self::ID, '/attractions/get-two-way-tickets/' . self::ID,
            '/attractions/get-fixeddate-time/' . self::ID, '/attractions/get-availability/' . self::ID,
            '/attractions/toggle-favorite',
            '/agent-topups', '/agent-topups/add', '/agent-topups/view/' . self::ID,
            '/agent-topups/topup-images/agenttopups/x/y.png',
            '/agent-users', '/agent-users/view', '/agent-users/add', '/agent-users/edit/' . self::ID,
            '/agent-users/read/' . self::ID, '/agent-users/agent-reset-password/' . self::ID,
            '/agent-users/suspend/' . self::ID, '/agent-users/contact', '/agent-users/logout',
            '/agent-users/two-factor', '/agent-users/reset-two-factor/' . self::ID, '/agent-users/change-password',
            '/credit-logs', '/credit-logs/index',
            '/orders', '/orders/cart', '/orders/checkout', '/orders/confirmation', '/orders/get-status',
            '/orders/thank-you', '/orders/view/REF1', '/orders/download-invoice/REF1', '/orders/my-tickets',
            '/orders/cancel-voucher/CODE1', '/orders/remove-ticket/' . self::ID . '/01-Jan-2027',
            '/vouchers/apiview/2026/0101/CODE1', '/vouchers/download-all/' . self::ID,
            '/reports', '/reports/reports', '/reports/download/report',
            '/voucher-merging/get-status',
        ];

        return array_combine($urls, array_map(fn ($url) => [$url], $urls));
    }

    #[DataProvider('protectedUrlProvider')]
    public function testLoggedOutVisitorIsSentToLogin(string $url): void
    {
        $this->get($url);

        $this->assertRedirectContains('/agent-users/login?redirect=' . rawurlencode($url));
    }

    public static function publicUrlProvider(): array
    {
        $urls = ['/agent-users/login', '/agent-users/forgot-password', '/signup', '/signup/thank-you'];

        return array_combine($urls, array_map(fn ($url) => [$url], $urls));
    }

    #[DataProvider('publicUrlProvider')]
    public function testPublicPagesRender(string $url): void
    {
        $this->get($url);

        $this->assertResponseOk();
    }

    public function testLoginFlowPagesWithoutPendingLoginGoBackToLogin(): void
    {
        foreach (['/agent-users/two-factor/setup', '/agent-users/two-factor/backup-codes'] as $url) {
            $this->get($url);
            $this->assertRedirectContains('/agent-users/login', $url);
        }

        $this->enableCsrfToken();
        $this->post('/agent-users/otp', ['otp' => '123456']);
        $this->assertRedirectContains('/agent-users/login');
    }
}
