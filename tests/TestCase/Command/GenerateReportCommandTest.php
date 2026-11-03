<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Test\TestCase\Support\AppDataTrait;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * `bin/cake generate_report`: writes the sales report CSV of the given agent users' orders
 * to TMP/reports/<agent_user_id>/<company> Sales Report <from> to <to>.csv.
 */
class GenerateReportCommandTest extends TestCase
{
    use AppDataTrait;
    use ConsoleIntegrationTestTrait;

    private const ATTRACTION = '00000000-0000-4000-8000-000000002101';
    private const TICKET = '00000000-0000-4000-8000-000000002102';

    protected array $fixtures = [...self::USER_FIXTURES, ...self::CATALOGUE_FIXTURES, ...self::ORDER_FIXTURES, 'app.ApiPdfs'];

    /** A fresh agent user id per test, so the report directory is ours alone. */
    private string $userId;

    private string $otherUserId;

    private mixed $companyName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = Text::uuid();
        $this->otherUserId = Text::uuid();
        $this->companyName = Configure::read('Site.company_name');
        Configure::write('Site.company_name', 'TDP Test');
    }

    protected function tearDown(): void
    {
        Configure::write('Site.company_name', $this->companyName);
        foreach ([$this->userId, $this->otherUserId] as $id) {
            $dir = TMP . 'reports' . DS . $id;
            if (is_dir($dir)) {
                array_map('unlink', glob($dir . DS . '*') ?: []);
                rmdir($dir);
            }
        }
        parent::tearDown();
    }

    private function reportPath(): string
    {
        return TMP . 'reports' . DS . $this->userId . DS . 'Report Co Sales Report 2026-01-01 to 2026-01-31.csv';
    }

    private function runReport(?string $agentUserIds = null): void
    {
        $this->exec(sprintf(
            'generate_report --agent_user_id=%s --issue_start_date=2026-01-01 --issue_end_date=2026-01-31'
            . ' --start_date="2026-01-01 00:00:00" --end_date="2026-01-31 23:59:59" --agent_user_ids=%s',
            $this->userId,
            $agentUserIds ?? $this->userId,
        ));
    }

    private function createData(bool $hideVoucherStatus = false): void
    {
        $this->createAgent(self::AGENT_A, ['company_name' => 'Report Co', 'hide_voucher_status' => (int)$hideVoucherStatus]);
        $this->createAgentUser($this->userId, self::AGENT_A, 'master', 'report.user@example.com', ['name' => 'Report User']);
        $this->createAgentUser($this->otherUserId, self::AGENT_A, 'normal', 'other.user@example.com', ['name' => 'Other User']);
        $this->createAttraction(self::ATTRACTION, ['title' => 'Night Zoo']);
        $this->createTicket(self::TICKET, self::ATTRACTION, ['name' => 'Adult']);

        $order = function (string $userId, string $ref, string $created, string $status, string $price, int $qty, ?string $code = null): void {
            $orderId = Text::uuid();
            $itemId = Text::uuid();
            $at = new DateTime($created);
            $total = bcmul($price, (string)$qty, 2);
            $this->createOrder($orderId, $userId, self::AGENT_A, [
                'order_ref_id' => $ref, 'tracking_no' => $code ? 'TRK-' . $ref : null, 'status' => $status,
                'subtotal' => $total, 'total' => $total, 'created' => $at, 'modified' => $at,
            ]);
            $this->createOrderItem($itemId, $orderId, self::TICKET, ['qty' => $qty, 'price' => $price, 'total' => $total, 'created' => $at, 'modified' => $at]);
            if ($code) {
                $this->createVoucher(Text::uuid(), $itemId, $code);
                $this->insertRow('generated_codes', [
                    'id' => Text::uuid(), 'code' => $code, 'ticket_id' => self::TICKET, 'status' => 'used', 'redeemable' => $qty,
                    'redeemed_datetime' => new DateTime('2026-01-11 09:00:00'),
                ]);
            }
        };
        $order($this->userId, 'REFONE', '2026-01-10 10:00:00', 'done', '25.00', 2, 'GENRPT01');
        $order($this->userId, 'REFTWO', '2026-01-20 15:30:00', 'done', '30.00', 1);
        // Excluded: out of range, pending, another user (not in --agent_user_ids).
        $order($this->userId, 'REFLATE', '2026-02-05 10:00:00', 'done', '10.00', 1);
        $order($this->userId, 'REFPEND', '2026-01-15 10:00:00', 'pending', '10.00', 1);
        $order($this->otherUserId, 'REFOTHER', '2026-01-16 10:00:00', 'done', '10.00', 1);
    }

    /**
     * @return list<list<string|null>>
     */
    private function readReport(): array
    {
        $this->assertFileExists($this->reportPath());
        $lines = file($this->reportPath(), FILE_IGNORE_NEW_LINES);

        return array_map(fn(string $line): array => $line === '' ? [] : str_getcsv($line, ',', '"', '\\'), $lines);
    }

    public function testWritesTheReport(): void
    {
        $this->createData();

        $this->runReport();

        $this->assertExitCode(0);
        $rows = $this->readReport();
        $this->assertCount(9, $rows);
        $this->assertSame('Report Date', $rows[0][0]);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $rows[0][1]);
        $this->assertSame(['Report Title', 'Report Sheet from TDP Test'], $rows[1]);
        $this->assertSame(['Reseller', 'Report Co'], $rows[2]);
        $this->assertSame(['Start Date', '2026-01-01'], $rows[3]);
        $this->assertSame(['End Date', '2026-01-31'], $rows[4]);
        $this->assertSame([], $rows[5]);
        $this->assertSame([
            'No', 'Date Issued', 'Time Issued', 'Reseller Name', 'Reseller Email', 'Order Tracking Number', 'Order Reference', 'Platform',
            'Confirmation No.', 'Attraction Name', 'Product Type', 'Package Name', 'Ticket Type', 'Customer Name', 'Payment Method',
            'Unit Price', 'Units', 'Total Amount', 'Status',
        ], $rows[6]);
        // One row per voucher; quantity is the code's redeemable count.
        $this->assertSame([
            '1', '10-01-2026', '10:00:00', 'Report User', 'report.user@example.com', 'TRK-REFONE', 'REFONE', 'B2B',
            'GENRPT01', 'Night Zoo', 'Attraction', '', 'Adult', 'Customer', 'credits', '25.00', '2', '50', 'redeemed',
        ], $rows[7]);
        // An item without vouchers gets one row with the order status.
        $this->assertSame([
            '2', '20-01-2026', '15:30:00', 'Report User', 'report.user@example.com', '', 'REFTWO', 'B2B',
            '', 'Night Zoo', 'Attraction', '', 'Adult', 'Customer', 'credits', '30.00', '1', '30', 'done',
        ], $rows[8]);
        $this->assertFileDoesNotExist($this->reportPath() . '.tmp');
    }

    public function testIncludesAllRequestedUsers(): void
    {
        $this->createData();

        $this->runReport($this->userId . ',' . $this->otherUserId);

        $refs = array_column(array_slice($this->readReport(), 7), 6);
        $this->assertSame(['REFONE', 'REFOTHER', 'REFTWO'], $refs);
    }

    public function testHidesVoucherStatusWhenTheAgentDoes(): void
    {
        $this->createData(true);

        $this->runReport();

        $rows = $this->readReport();
        $this->assertCount(18, $rows[6]);
        $this->assertSame('Total Amount', end($rows[6]));
        $this->assertCount(18, $rows[7]);
        $this->assertSame('50', end($rows[7]));
    }

    public function testNoReportWithoutOrders(): void
    {
        $this->createData();
        $this->db()->execute("UPDATE orders SET status = 'void'");

        $this->runReport();

        $this->assertExitCode(0);
        $this->assertDirectoryDoesNotExist(TMP . 'reports' . DS . $this->userId);
    }

    public function testUnknownAgentUserDoesNothing(): void
    {
        $this->runReport();

        $this->assertExitCode(0);
        $this->assertDirectoryDoesNotExist(TMP . 'reports' . DS . $this->userId);
    }
}
