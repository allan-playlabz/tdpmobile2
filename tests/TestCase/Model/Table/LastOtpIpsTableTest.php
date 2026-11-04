<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\LastOtpIpsTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * The last 3 IPs an agent user passed the emailed OTP from (trusted IPs).
 */
class LastOtpIpsTableTest extends TestCase
{
    use AppDataTrait;

    protected array $fixtures = self::USER_FIXTURES;

    private LastOtpIpsTable $LastOtpIps;

    protected function setUp(): void
    {
        parent::setUp();
        $this->LastOtpIps = $this->getTableLocator()->get('LastOtpIps');
        $this->createAgentsAndUsers();
    }

    private function addIp(string $userId, string $ip, string $modified): void
    {
        $this->insertRow('last_otp_ips', [
            'agent_user_id' => $userId, 'ip' => $ip, 'created' => new DateTime($modified), 'modified' => new DateTime($modified),
        ]);
    }

    private function ips(string $userId): array
    {
        $rows = $this->db()->execute(
            'SELECT ip FROM last_otp_ips WHERE agent_user_id = ' . self::uuidSql() . ' ORDER BY ip',
            [$userId],
        )->fetchAll('assoc');

        return array_column($rows, 'ip');
    }

    public function testGetLatestIpsNewestFirstAndLimited(): void
    {
        $this->addIp(self::MASTER_A, '10.0.0.1', '-4 hours');
        $this->addIp(self::MASTER_A, '10.0.0.2', '-1 hour');
        $this->addIp(self::MASTER_A, '10.0.0.3', '-3 hours');
        $this->addIp(self::MASTER_A, '10.0.0.4', '-2 hours');
        $this->addIp(self::NORMAL_A, '10.9.9.9', '-1 minute');

        $this->assertSame(['10.0.0.2', '10.0.0.4', '10.0.0.3'], array_values($this->LastOtpIps->getLatestIps(self::MASTER_A)));
        $this->assertSame(['10.0.0.2'], array_values($this->LastOtpIps->getLatestIps(self::MASTER_A, 1)));
        $this->assertSame([], $this->LastOtpIps->getLatestIps(self::VIEWER_A));
    }

    public function testAddAndPruneKeepsTheNewestThree(): void
    {
        $this->addIp(self::MASTER_A, '10.0.0.1', '-3 hours');
        $this->addIp(self::MASTER_A, '10.0.0.2', '-2 hours');
        $this->addIp(self::MASTER_A, '10.0.0.3', '-1 hour');
        $this->addIp(self::NORMAL_A, '10.9.9.9', '-5 hours');

        $row = $this->LastOtpIps->addAndPrune(self::MASTER_A, '10.0.0.4');

        $this->assertSame('10.0.0.4', $row->ip);
        $this->assertSame(['10.0.0.2', '10.0.0.3', '10.0.0.4'], $this->ips(self::MASTER_A));
        // Other users are untouched.
        $this->assertSame(['10.9.9.9'], $this->ips(self::NORMAL_A));
    }

    public function testAddAndPruneBumpsAKnownIp(): void
    {
        $this->addIp(self::MASTER_A, '10.0.0.1', '-3 hours');
        $this->addIp(self::MASTER_A, '10.0.0.2', '-2 hours');
        $this->addIp(self::MASTER_A, '10.0.0.3', '-1 hour');

        // Re-using the oldest IP makes it the newest, so the next new IP pushes out 10.0.0.2.
        $this->LastOtpIps->addAndPrune(self::MASTER_A, '10.0.0.1');
        $this->assertSame(3, $this->countRows('last_otp_ips'));

        $this->LastOtpIps->addAndPrune(self::MASTER_A, '10.0.0.4');

        $this->assertSame(['10.0.0.1', '10.0.0.3', '10.0.0.4'], $this->ips(self::MASTER_A));
    }

    public function testAddAndPruneWithCustomKeep(): void
    {
        $this->addIp(self::MASTER_A, '10.0.0.1', '-2 hours');
        $this->addIp(self::MASTER_A, '10.0.0.2', '-1 hour');

        $this->LastOtpIps->addAndPrune(self::MASTER_A, '10.0.0.3', 1);

        $this->assertSame(['10.0.0.3'], $this->ips(self::MASTER_A));
    }
}
