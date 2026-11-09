<?php
declare(strict_types=1);

namespace App\Test\TestCase\View;

use Cake\Database\Connection;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Renders the redesigned pages (empty data) and checks they stay CSP-ready: no inline
 * <script>/<style> blocks or on*= / style= attributes, and the JSON page-data island is valid.
 */
class RedesignRenderTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Agents',
        'app.AgentUsers',
    ];

    private const AGENT = '00000000-0000-4000-8000-0000000000c1';
    private const USER = '00000000-0000-4000-8000-0000000000c2';

    protected function setUp(): void
    {
        parent::setUp();
        /** @var Connection $db */
        $db = $this->getTableLocator()->get('Agents')->getConnection();
        $db->insert('agents', [
            'id' => self::AGENT, 'first_name' => 'Render', 'last_name' => 'Agent', 'email' => 'render.agent@example.com',
            'company_name' => 'Render Agent', 'image' => '', 'country' => 'Singapore', 'gender' => 'male', 'enable_api' => 0,
            'status' => 'active', 'enable_agent_login' => 1, 'mobile_otp' => 0, 'credits' => '100.00',
        ], ['id' => 'binaryuuid']);
        $db->insert('agent_users', [
            'id' => self::USER, 'agent_id' => self::AGENT, 'name' => 'Render User', 'email' => 'render.user@example.com',
            'password' => 'x', 'role' => 'master', 'status' => 'active', 'two_factor_method' => 'email_otp',
        ], ['id' => 'binaryuuid', 'agent_id' => 'binaryuuid']);
        $this->session(['Auth' => ['User' => [
            'id' => self::USER, 'agent_id' => self::AGENT, 'role' => 'master', 'email' => 'render.user@example.com',
            'name' => 'Render User', 'credits' => '100.00',
            'agent' => ['mobile_otp' => false, 'low_credit_limit' => '0.00', 'first_name' => 'Render', 'last_name' => 'Agent'],
        ]]]);
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function pages(): array
    {
        return [
            'attractions index' => ['/attractions', ['id="attraction-filters"', 'name="search"', 'No attractions found.', 'page-attractions-index']],
            'empty cart' => ['/orders/cart', ['Your Cart', 'No items on cart.', 'class="sg-steps"', 'page-orders-cart']],
            'thank you without order' => ['/orders/thank-you', ['Thank you for your purchase!', 'page-orders-thank-you']],
            'contact us' => ['/agent-users/contact', ['Contact Us', 'id="contact-form"', 'name="subject"', 'page-agent-users-contact']],
        ];
    }

    /**
     * @param list<string> $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function testPageRendersWithoutInlineCode(string $url, array $expected): void
    {
        $this->get($url);
        $this->assertResponseOk();
        $html = (string)$this->_response->getBody();

        foreach ($expected as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        // Theme + shared script are loaded; the navbar uses the new markup.
        $this->assertStringContainsString('theme.css', $html);
        $this->assertStringContainsString('app.js', $html);
        $this->assertStringContainsString('sg-navbar', $html);
        $this->assertStringContainsString('name="csrf-token"', $html);

        // CSP: only external scripts and the JSON data island.
        preg_match_all('/<script\b([^>]*)>/i', $html, $scripts);
        foreach ($scripts[1] as $attrs) {
            $this->assertMatchesRegularExpression('/\bsrc=|type="application\/json"/', $attrs, "inline <script{$attrs}> on {$url}");
        }
        $this->assertDoesNotMatchRegularExpression('/<style\b/i', $html, "inline <style> on {$url}");
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=\s*["\']/i', $html, "inline event handler on {$url}");
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=\s*["\']/i', $html, "style attribute on {$url}");

        if (preg_match('/<script type="application\/json" id="page-data">(.*?)<\/script>/s', $html, $m)) {
            $this->assertIsArray(json_decode($m[1], true), 'page-data is valid JSON');
        }
    }
}
