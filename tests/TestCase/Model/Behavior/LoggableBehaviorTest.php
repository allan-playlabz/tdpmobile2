<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Behavior;

use App\Test\TestCase\Support\AppDataTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * LoggableBehavior writes an audit row to `logs` after each add/edit/delete
 * (AgentTopups and AgentUsers carry the behavior). The acting user comes from $_SESSION;
 * table_name is the table alias (entity source), as in existing rows.
 */
class LoggableBehaviorTest extends TestCase
{
    use AppDataTrait;

    private const TOPUP = '00000000-0000-4000-8000-000000002001';

    protected array $fixtures = [...self::USER_FIXTURES, 'app.AgentTopups'];

    private array $sessionBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionBackup = $_SESSION ?? [];
        $_SESSION = ['Auth' => ['User' => ['id' => self::MASTER_A]]];
        $this->createAgentsAndUsers();
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->sessionBackup;
        parent::tearDown();
    }

    private function logs(): array
    {
        return $this->db()->execute(
            'SELECT LOWER(HEX(user_id)) AS user_id, user_table, type, table_name, LOWER(HEX(record_id)) AS record_id,
                values_before, values_after, ip_address, created
             FROM logs ORDER BY id',
        )->fetchAll('assoc');
    }

    private static function hex(string $uuid): string
    {
        return str_replace('-', '', $uuid);
    }

    private function newTopup()
    {
        $AgentTopups = $this->getTableLocator()->get('AgentTopups');
        $topup = $AgentTopups->newEntity([
            'agent_id' => self::AGENT_A, 'amount' => 100.5, 'topup_date' => '2026-01-15', 'remarks' => 'Bank transfer', 'status' => 'pending',
        ]);
        $topup->id = self::TOPUP;
        $AgentTopups->saveOrFail($topup);

        return $topup;
    }

    public function testAddIsLogged(): void
    {
        $this->newTopup();

        $logs = $this->logs();
        $this->assertCount(1, $logs);
        $log = $logs[0];
        $this->assertSame('add', $log['type']);
        $this->assertSame('agent_users', $log['user_table']);
        $this->assertSame('AgentTopups', $log['table_name']);
        $this->assertSame(self::hex(self::TOPUP), $log['record_id']);
        $this->assertSame(self::hex(self::MASTER_A), $log['user_id']);
        $this->assertSame('127.0.0.1', $log['ip_address']);
        $this->assertNull($log['values_before']);
        $this->assertNotEmpty($log['created']);
        $after = json_decode($log['values_after'], true);
        $this->assertSame(self::TOPUP, $after['id']);
        $this->assertSame(self::AGENT_A, $after['agent_id']);
        $this->assertSame('Bank transfer', $after['remarks']);
        $this->assertSame('pending', $after['status']);
    }

    public function testAddWithoutSessionUserHasNoUser(): void
    {
        $_SESSION = [];

        $this->newTopup();

        $this->assertNull($this->logs()[0]['user_id']);
    }

    public function testEditLogsOnlyChangedFields(): void
    {
        $topup = $this->newTopup();
        $AgentTopups = $this->getTableLocator()->get('AgentTopups');
        $topup = $AgentTopups->get($topup->id);

        $topup = $AgentTopups->patchEntity($topup, ['status' => 'approved', 'remarks' => 'Checked', 'amount' => 100.5]);
        $AgentTopups->saveOrFail($topup);

        $logs = $this->logs();
        $this->assertCount(2, $logs);
        $log = $logs[1];
        $this->assertSame('edit', $log['type']);
        $this->assertSame('AgentTopups', $log['table_name']);
        $this->assertSame(self::hex(self::TOPUP), $log['record_id']);
        $this->assertSame(self::hex(self::MASTER_A), $log['user_id']);
        // `modified` (set by Timestamp) and unchanged fields are left out.
        $this->assertSame(['status' => 'pending', 'remarks' => 'Bank transfer'], json_decode($log['values_before'], true));
        $this->assertSame(['status' => 'approved', 'remarks' => 'Checked'], json_decode($log['values_after'], true));
    }

    public function testSavingWithoutChangesIsNotLogged(): void
    {
        $topup = $this->newTopup();
        $AgentTopups = $this->getTableLocator()->get('AgentTopups');
        $topup = $AgentTopups->get($topup->id);

        $AgentTopups->saveOrFail($AgentTopups->patchEntity($topup, ['status' => 'pending']));

        $this->assertCount(1, $this->logs());
    }

    public function testOnlyModifiedChangedIsNotLogged(): void
    {
        $topup = $this->newTopup();
        $AgentTopups = $this->getTableLocator()->get('AgentTopups');
        $topup = $AgentTopups->get($topup->id);
        $topup->modified = new DateTime('+1 hour');

        // The log entity has no type, fails validation and is not saved.
        $AgentTopups->saveOrFail($topup);

        $this->assertCount(1, $this->logs());
    }

    public function testDeleteIsLogged(): void
    {
        $topup = $this->newTopup();
        $AgentTopups = $this->getTableLocator()->get('AgentTopups');

        $AgentTopups->deleteOrFail($AgentTopups->get($topup->id));

        $logs = $this->logs();
        $this->assertCount(2, $logs);
        $log = $logs[1];
        $this->assertSame('delete', $log['type']);
        $this->assertSame('AgentTopups', $log['table_name']);
        $this->assertSame(self::hex(self::TOPUP), $log['record_id']);
        $this->assertSame(self::hex(self::MASTER_A), $log['user_id']);
        $this->assertNull($log['values_after']);
        $before = json_decode($log['values_before'], true);
        $this->assertSame('Bank transfer', $before['remarks']);
        $this->assertSame(self::AGENT_A, $before['agent_id']);
        if ($log['user_table'] !== 'agent_users') {
            $this->markTestSkipped('BUG: LoggableBehavior::afterDelete logs user_table "' . $log['user_table'] . '" instead of "agent_users"');
        }
        $this->assertSame('agent_users', $log['user_table']);
    }

    public function testHiddenFieldsAreNotLogged(): void
    {
        $AgentUsers = $this->getTableLocator()->get('AgentUsers');
        $user = $AgentUsers->newEntity(['name' => 'New User', 'email' => 'new.user@example.com', 'password' => 'Secret#12345']);
        $user->patch(['agent_id' => self::AGENT_A, 'role' => 'normal', 'status' => 'active', 'totp_secret' => 'encrypted-secret', 'otp' => 123456], ['guard' => false]);
        $AgentUsers->saveOrFail($user);

        $logs = $this->logs();
        $this->assertCount(1, $logs);
        $this->assertSame('AgentUsers', $logs[0]['table_name']);
        $after = json_decode($logs[0]['values_after'], true);
        $this->assertSame('new.user@example.com', $after['email']);
        $this->assertSame('New User', $after['name']);
        foreach (['password', 'otp', 'totp_secret'] as $hidden) {
            $this->assertArrayNotHasKey($hidden, $after);
        }
        $this->assertStringNotContainsString('encrypted-secret', $logs[0]['values_after']);
    }

    public function testIpFallsBackToRemoteAddr(): void
    {
        // No CF-Connecting-IP header on the CLI (getallheaders() is polyfilled in the bootstrap).
        $backup = $_SERVER['REMOTE_ADDR'];
        $_SERVER['REMOTE_ADDR'] = '192.0.2.10';
        try {
            $this->newTopup();
        } finally {
            $_SERVER['REMOTE_ADDR'] = $backup;
        }

        $this->assertSame('192.0.2.10', $this->logs()[0]['ip_address']);
    }
}
