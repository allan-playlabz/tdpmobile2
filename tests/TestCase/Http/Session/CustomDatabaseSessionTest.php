<?php
declare(strict_types=1);

namespace App\Test\TestCase\Http\Session;

use App\Http\Session\CustomDatabaseSession;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use SessionHandlerInterface;

/**
 * Database session handler (agent_sessions). write() reads the logged-in user out of the
 * serialized data with session_decode(), which only works while a PHP session is active.
 * The CLI can't start one once output has been sent, so every test runs in its own process
 * and starts a session (with a no-op save handler) before anything is printed.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class CustomDatabaseSessionTest extends TestCase
{
    use AppDataTrait;

    protected array $fixtures = [...self::USER_FIXTURES, 'app.AgentSessions'];

    private CustomDatabaseSession $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->startPhpSession();
        $this->handler = new CustomDatabaseSession();
        $this->handler->setTimeout(1440);
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_abort();
        }
        parent::tearDown();
    }

    private function startPhpSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (headers_sent($file, $line)) {
            $this->markTestSkipped("Can't start a PHP session: output already sent at $file:$line");
        }
        ini_set('session.use_cookies', '0');
        ini_set('session.cache_limiter', '');
        session_set_save_handler(new class implements SessionHandlerInterface {
            public function open(string $path, string $name): bool
            {
                return true;
            }

            public function close(): bool
            {
                return true;
            }

            public function read(string $id): string|false
            {
                return '';
            }

            public function write(string $id, string $data): bool
            {
                return true;
            }

            public function destroy(string $id): bool
            {
                return true;
            }

            public function gc(int $max_lifetime): int|false
            {
                return 0;
            }
        }, false);
        session_id('phpunit');
        session_start();
        $_SESSION = ['current' => 'request'];
    }

    private static function authData(string $userId): string
    {
        return 'Auth|' . serialize(['User' => ['id' => $userId, 'email' => 'user@example.com']]) . 'Config|' . serialize(['time' => 1]);
    }

    private function sessionRow(string $id): ?array
    {
        return $this->fetchRow('SELECT data, expires, agent_user_id FROM agent_sessions WHERE id = ?', [$id]);
    }

    private function insertSession(string $id, ?string $userId, int $expires): void
    {
        $this->db()->insert('agent_sessions', ['id' => $id, 'data' => 'x|i:1;', 'expires' => $expires, 'agent_user_id' => $userId]);
    }

    public function testWriteThenRead(): void
    {
        $data = 'Flash|' . serialize(['flash' => []]) . 'ShoppingCart|' . serialize(['t' => ['01 Jan 2027' => 2]]);

        $this->assertTrue($this->handler->write('sess-anon', $data));

        $this->assertSame($data, $this->handler->read('sess-anon'));
        $row = $this->sessionRow('sess-anon');
        $this->assertNull($row['agent_user_id']);
        $this->assertEqualsWithDelta(time() + 1440, (int)$row['expires'], 5);
        // The current request's $_SESSION is left alone.
        $this->assertSame(['current' => 'request'], $_SESSION);
    }

    public function testWriteUpdatesExistingSession(): void
    {
        $this->handler->write('sess-1', 'a|i:1;');
        $this->handler->write('sess-1', 'a|i:2;');

        $this->assertSame('a|i:2;', $this->handler->read('sess-1'));
        $this->assertSame(1, $this->countRows('agent_sessions'));
    }

    public function testReadUnknownSessionIsEmpty(): void
    {
        $this->assertSame('', $this->handler->read('missing'));
    }

    public function testWriteEmptyIdFails(): void
    {
        $this->assertFalse($this->handler->write('', 'a|i:1;'));
        $this->assertSame(0, $this->countRows('agent_sessions'));
    }

    public function testLoggedInUserKeepsOnlyOneSession(): void
    {
        $this->createAgentsAndUsers();
        $this->insertSession('old-master', self::MASTER_A, time() + 1000);
        $this->insertSession('other-user', self::NORMAL_A, time() + 1000);
        $this->insertSession('anonymous', null, time() + 1000);

        $this->assertTrue($this->handler->write('new-master', self::authData(self::MASTER_A)));

        $this->assertSame(self::MASTER_A, $this->sessionRow('new-master')['agent_user_id']);
        $this->assertNull($this->sessionRow('old-master'));
        $this->assertNotNull($this->sessionRow('other-user'));
        $this->assertNotNull($this->sessionRow('anonymous'));
        $this->assertSame(['current' => 'request'], $_SESSION);

        // Writing the same session again keeps it.
        $this->assertTrue($this->handler->write('new-master', self::authData(self::MASTER_A)));
        $this->assertNotNull($this->sessionRow('new-master'));
    }

    public function testDestroy(): void
    {
        $this->insertSession('doomed', null, time() + 1000);
        $this->insertSession('kept', null, time() + 1000);

        $this->assertTrue($this->handler->destroy('doomed'));
        $this->assertTrue($this->handler->destroy('never-existed'));

        $this->assertNull($this->sessionRow('doomed'));
        $this->assertNotNull($this->sessionRow('kept'));
    }

    public function testGcRemovesLongExpiredSessions(): void
    {
        $this->insertSession('long-expired', null, time() - 10000);
        $this->insertSession('active', null, time() + 1000);

        $this->assertSame(1, $this->handler->gc(1440));

        $this->assertNull($this->sessionRow('long-expired'));
        $this->assertNotNull($this->sessionRow('active'));
    }
}
