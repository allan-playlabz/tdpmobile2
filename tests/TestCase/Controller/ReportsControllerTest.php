<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\Support\AppDataTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ReportsController: report request form, generated report list and download.
 *
 * The "orders found" branch spawns `bin/cake generate_report` in the background and is
 * deliberately not exercised here.
 */
class ReportsControllerTest extends TestCase
{
    use AppDataTrait;
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Agents',
        'app.AgentUsers',
        'app.Announcements',
        'app.Logs',
        'app.Orders',
        'app.OrderItems',
        'app.Tickets',
        'app.Attractions',
        'app.Merchants',
        'app.Vouchers',
        'app.Barcodes',
        'app.GeneratedCodes',
        'app.Pdfs',
        'app.ApiPdfs',
    ];

    private string $reportDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAgentsAndUsers();
        $this->enableRetainFlashMessages();
        $this->reportDir = TMP . 'reports' . DS . self::MASTER_A;
        $this->removeReportDir();
    }

    protected function tearDown(): void
    {
        $this->removeReportDir();
        parent::tearDown();
    }

    private function removeReportDir(): void
    {
        if (!is_dir($this->reportDir)) {
            return;
        }
        foreach (glob($this->reportDir . DS . '*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->reportDir);
    }

    private function createReportFile(string $name, string $contents): void
    {
        if (!is_dir($this->reportDir)) {
            mkdir($this->reportDir, 0777, true);
        }
        file_put_contents($this->reportDir . DS . $name, $contents);
    }

    public function testIndexRenders(): void
    {
        $this->loginAs(self::MASTER_A);
        $this->get('/reports');

        $this->assertResponseOk();
        $this->assertSame(self::AGENT_A, $this->viewVariable('agent')->id);
    }

    public function testRequestWithoutOrdersFindsNoTransactions(): void
    {
        // Guard: with orders the action would spawn a background `bin/cake generate_report`.
        $this->assertSame(0, $this->countRows('orders'));

        $this->loginAs(self::MASTER_A);
        $this->enableCsrfToken();
        $this->post('/reports', ['issue_start_date' => '', 'issue_end_date' => '']);

        $this->assertResponseOk();
        $this->assertFlashMessage('No transactions found.');
        $this->assertFalse(is_dir($this->reportDir) && glob($this->reportDir . DS . '*.tmp'), 'no report was started');
    }

    public function testRequestWhileAReportIsProcessingIsRefused(): void
    {
        $this->createReportFile('pending report.tmp', '');
        $this->loginAs(self::MASTER_A);
        $this->enableCsrfToken();
        $this->post('/reports', ['issue_start_date' => '', 'issue_end_date' => '']);

        $this->assertRedirectContains('/reports');
        $this->assertFlashMessage('You have a report that is processing. Please wait before creating another report.');
    }

    public function testReportsListRendersWithoutFolder(): void
    {
        $this->loginAs(self::MASTER_A);
        $this->get('/reports/reports');

        $this->assertResponseOk();
        $this->assertSame([], $this->viewVariable('reports'));
        $this->assertSame(0, $this->viewVariable('processing_files_count'));
    }

    public function testReportsListShowsGeneratedAndProcessingFiles(): void
    {
        $this->createReportFile('Sales Report.csv', "a,b\n1,2\n");
        $this->createReportFile('Next Report.tmp', '');
        $this->loginAs(self::MASTER_A);
        $this->get('/reports/reports');

        $this->assertResponseOk();
        $reports = array_values($this->viewVariable('reports'));
        $this->assertCount(1, $reports);
        $this->assertSame('Sales Report.csv', $reports[0]['filename']);
        $this->assertSame(1, $this->viewVariable('processing_files_count'));
    }

    public function testDownloadMissingReportIs404(): void
    {
        $this->loginAs(self::MASTER_A);
        $this->get('/reports/download/not-there');

        $this->assertResponseCode(404);
    }

    public function testDownloadExistingReport(): void
    {
        $this->createReportFile('my-report.csv', "Order,Total\nREF1,50.00\n");
        $this->loginAs(self::MASTER_A);
        $this->get('/reports/download/my-report');

        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Disposition', 'attachment');
        $this->assertHeaderContains('Content-Disposition', 'my-report.csv');
        $this->assertResponseContains('REF1,50.00');
    }

    public function testDownloadOnlyReadsOwnFolder(): void
    {
        $this->createReportFile('my-report.csv', "secret\n");
        $this->loginAs(self::NORMAL_A);
        $this->get('/reports/download/my-report');

        $this->assertResponseCode(404);
    }
}
