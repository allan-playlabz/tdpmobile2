<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\Support\AppDataTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\CreditLogsController: the agent's balance history and its CSV export.
 */
class CreditLogsControllerTest extends TestCase
{
    use AppDataTrait;
    use IntegrationTestTrait;

    private const LOG_A1 = '00000000-0000-4000-8000-0000000002a1';
    private const LOG_A2 = '00000000-0000-4000-8000-0000000002a2';
    private const LOG_B1 = '00000000-0000-4000-8000-0000000002b1';

    protected array $fixtures = [
        'app.Agents',
        'app.AgentUsers',
        'app.Announcements',
        'app.Logs',
        'app.CreditLogs',
        'app.Orders',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAgentsAndUsers();
        $this->enableRetainFlashMessages();
        // Top-ups (debit, no order): rendered without an order.
        $this->insertRow('credit_logs', [
            'id' => self::LOG_A1, 'agent_id' => self::AGENT_A, 'debit_credit' => 'debit', 'amount' => '200.00', 'credits' => '1200.00',
            'created' => new DateTime('-2 days'), 'modified' => new DateTime('-2 days'),
        ]);
        $this->insertRow('credit_logs', [
            'id' => self::LOG_A2, 'agent_id' => self::AGENT_A, 'debit_credit' => 'debit', 'amount' => '300.00', 'credits' => '1500.00',
            'created' => new DateTime('-1 day'), 'modified' => new DateTime('-1 day'),
        ]);
        $this->insertRow('credit_logs', [
            'id' => self::LOG_B1, 'agent_id' => self::AGENT_B, 'debit_credit' => 'debit', 'amount' => '777.00', 'credits' => '777.00',
            'created' => new DateTime('-1 day'), 'modified' => new DateTime('-1 day'),
        ]);
    }

    protected function tearDown(): void
    {
        foreach (glob(TMP . 'reports' . DS . '*Balance Report*.csv') ?: [] as $csv) {
            @unlink($csv);
        }
        parent::tearDown();
    }

    private static function day(string $relative): string
    {
        return (new DateTime($relative))->format('d M Y');
    }

    public function testRequiresCreditLogsView(): void
    {
        $this->loginAs(self::MASTER_A);
        $this->_session['Auth']['User']['agent']['enable_credit_logs_view'] = false;
        $this->get('/credit-logs');

        $this->assertRedirect('/');
    }

    public function testNonMastersAreRefused(): void
    {
        foreach ([self::NORMAL_A, self::VIEWER_A] as $userId) {
            $this->loginAs($userId);
            $this->enableRetainFlashMessages();
            $this->get('/credit-logs');

            $this->assertRedirect('/', $userId);
            $this->assertFlashMessage('You are not authorized to access that location.');
        }
    }

    public function testListsOnlyOwnAgentsLogs(): void
    {
        $this->loginAs(self::MASTER_A);
        $this->get('/credit-logs');

        $this->assertResponseOk();
        $ids = [];
        foreach ($this->viewVariable('creditLogs') as $log) {
            $ids[] = $log->id;
        }
        $this->assertSame([self::LOG_A2, self::LOG_A1], $ids, 'newest (highest log_no) first');
    }

    public function testCsvWithoutDateRangeRedirects(): void
    {
        $this->loginAs(self::MASTER_A);
        $this->enableCsrfToken();
        $this->post('/credit-logs');

        $this->assertRedirectContains('/credit-logs');
        $this->assertFlashMessage('Select a date and click filter to download!');
    }

    public function testCsvWithRangeOverAMonthRedirects(): void
    {
        $this->loginAs(self::MASTER_A);
        $this->enableCsrfToken();
        $this->post('/credit-logs?' . http_build_query(['from' => self::day('-50 days'), 'to' => self::day('now')]));

        $this->assertRedirectContains('/credit-logs');
        $this->assertFlashMessage('Date range cannot be more than 30 days! Please select another date then filter.');
    }

    public function testCsvDownloadContainsOwnLogsOnly(): void
    {
        $this->loginAs(self::MASTER_A);
        $this->enableCsrfToken();
        $this->post('/credit-logs?' . http_build_query(['from' => self::day('-10 days'), 'to' => self::day('now')]));

        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Disposition', 'attachment');
        $this->assertResponseContains('Balance Report');
        $this->assertResponseContains('Top Up');
        $this->assertResponseContains('300.00');
        $this->assertResponseNotContains('777.00');
    }
}
