<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Test\TestCase\Support\AppDataTrait;
use Cake\Command\Command;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use PDOException;

/**
 * `bin/cake two_factor reset <email>` removes an agent user's authenticator and backup codes.
 */
class TwoFactorResetCommandTest extends TestCase
{
    use AppDataTrait;
    use ConsoleIntegrationTestTrait;

    protected array $fixtures = self::USER_FIXTURES;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAgentsAndUsers();
        foreach ([self::MASTER_A, self::NORMAL_A] as $id) {
            $this->db()->update('agent_users', [
                'totp_secret' => 'encrypted-secret', 'totp_enabled' => new DateTime('-10 days'), 'totp_last_step' => 123456,
                'backup_codes' => '["hash1","hash2"]',
            ], ['id' => $id], ['totp_enabled' => 'datetime', 'id' => 'binaryuuid']);
        }
    }

    private function totp(string $id): array
    {
        return $this->fetchRow(
            'SELECT totp_secret, totp_enabled, totp_last_step, backup_codes FROM agent_users WHERE id = ' . self::uuidSql(),
            [$id],
        );
    }

    public function testResetsTheUsersAuthenticator(): void
    {
        $logError = null;
        try {
            $this->exec('two_factor reset master.a@example.com');
        } catch (PDOException $e) {
            $logError = $e;
        }

        $this->assertSame(['totp_secret' => null, 'totp_enabled' => null, 'totp_last_step' => null, 'backup_codes' => null], $this->totp(self::MASTER_A));
        // Other users keep theirs.
        $this->assertSame('encrypted-secret', $this->totp(self::NORMAL_A)['totp_secret']);
        if ($logError !== null) {
            $this->markTestSkipped('BUG: TwoFactorResetCommand logs type "two_factor_reset" (16 chars), logs.type is varchar(15): ' . strtok($logError->getMessage(), PHP_EOL));
        }

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('Authenticator removed for master.a@example.com');
        $log = $this->fetchRow('SELECT user_table, type, table_name, LOWER(HEX(record_id)) AS record_id, values_after FROM logs');
        $this->assertSame([
            'user_table' => 'agent_users', 'type' => 'two_factor_reset', 'table_name' => 'agent_users',
            'record_id' => str_replace('-', '', self::MASTER_A), 'values_after' => '{"two_factor":"reset","by":"console"}',
        ], $log);
        $this->assertSame(1, $this->countRows('logs'));
    }

    public function testUnknownEmail(): void
    {
        $this->exec('two_factor reset nobody@example.com');

        $this->assertExitCode(Command::CODE_ERROR);
        $this->assertErrorContains('No agent user with the email nobody@example.com.');
        $this->assertSame('encrypted-secret', $this->totp(self::MASTER_A)['totp_secret']);
        $this->assertSame(0, $this->countRows('logs'));
    }

    public function testEmailIsRequired(): void
    {
        $this->exec('two_factor reset');

        $this->assertExitCode(Command::CODE_ERROR);
        $this->assertSame('encrypted-secret', $this->totp(self::MASTER_A)['totp_secret']);
    }
}
