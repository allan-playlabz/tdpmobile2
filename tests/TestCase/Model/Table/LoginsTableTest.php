<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\LoginsTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Login rate limiting: at most Login.max_count_per_max_minutes (5) attempts per IP + email
 * within Login.max_minutes (3). Chronos test-now is fixed in tests/bootstrap.php.
 */
class LoginsTableTest extends TestCase
{
    use AppDataTrait;
    use ValidationTestTrait;

    protected array $fixtures = ['app.Logins', 'app.Agents'];

    private LoginsTable $Logins;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Logins = $this->getTableLocator()->get('Logins');
    }

    protected function validData(): array
    {
        return ['ip' => '10.0.0.1', 'email' => 'jane@example.com'];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'missing ip' => [['ip' => self::UNSET], ['ip' => ['_required']]],
            'long ip' => [['ip' => str_repeat('1', 65)], ['ip' => ['maxLength']]],
            'missing email' => [['email' => self::UNSET], ['email' => ['_required']]],
            'invalid email' => [['email' => 'nope'], ['email' => ['email']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->Logins, $changes, $expected);
    }

    private function request(string $email, string $remoteAddr = '10.0.0.1', ?string $cfIp = null): ServerRequest
    {
        $env = ['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => $remoteAddr];
        if ($cfIp !== null) {
            $env['HTTP_CF_CONNECTING_IP'] = $cfIp;
        }

        return new ServerRequest(['environment' => $env, 'post' => ['email' => $email]]);
    }

    public function testAllowsUpToTheLimitThenBlocks(): void
    {
        $request = $this->request('jane@example.com');
        for ($i = 1; $i <= 5; $i++) {
            $this->assertTrue($this->Logins->isAllowed($request), "attempt $i");
        }
        $this->assertFalse($this->Logins->isAllowed($request));
        // A blocked attempt isn't recorded.
        $this->assertSame(5, $this->countRows('logins', "ip = '10.0.0.1' AND email = 'jane@example.com' AND loc = 'agent'"));
    }

    public function testCountsPerIpAndEmail(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->Logins->isAllowed($this->request('jane@example.com'));
        }

        $this->assertFalse($this->Logins->isAllowed($this->request('jane@example.com')));
        $this->assertTrue($this->Logins->isAllowed($this->request('john@example.com')));
        $this->assertTrue($this->Logins->isAllowed($this->request('jane@example.com', '10.0.0.2')));
    }

    public function testOldAttemptsArePurgedAndDontCount(): void
    {
        $old = DateTime::now()->subMinutes(Configure::read('Login.max_minutes') + 1);
        for ($i = 0; $i < 5; $i++) {
            $this->insertRow('logins', ['ip' => '10.0.0.1', 'email' => 'jane@example.com', 'loc' => 'agent', 'created' => $old]);
        }

        $this->assertTrue($this->Logins->isAllowed($this->request('jane@example.com')));
        $this->assertSame(1, $this->countRows('logins'));
    }

    public function testOtherSystemsAttemptsDontCount(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->insertRow('logins', ['ip' => '10.0.0.1', 'email' => 'jane@example.com', 'loc' => 'admin', 'created' => DateTime::now()]);
        }

        $this->assertTrue($this->Logins->isAllowed($this->request('jane@example.com')));
    }

    public function testCloudflareHeaderOnlyTrustedFromCloudflare(): void
    {
        // Peer inside a Cloudflare range: the header is the client IP.
        $this->Logins->isAllowed($this->request('jane@example.com', '173.245.48.10', '203.0.113.7'));
        // Any other peer: the header is ignored.
        $this->Logins->isAllowed($this->request('john@example.com', '10.0.0.9', '203.0.113.8'));

        $this->assertSame(1, $this->countRows('logins', "ip = '203.0.113.7' AND email = 'jane@example.com'"));
        $this->assertSame(1, $this->countRows('logins', "ip = '10.0.0.9' AND email = 'john@example.com'"));
        $this->assertSame(0, $this->countRows('logins', "ip = '203.0.113.8'"));
    }

    public function testClearLoginRemovesAgentAttemptsOfTheIp(): void
    {
        $now = DateTime::now();
        $this->insertRow('logins', ['ip' => '10.0.0.1', 'email' => 'jane@example.com', 'loc' => 'agent', 'created' => $now]);
        $this->insertRow('logins', ['ip' => '10.0.0.1', 'email' => 'john@example.com', 'loc' => 'agent', 'created' => $now]);
        $this->insertRow('logins', ['ip' => '10.0.0.1', 'email' => 'jane@example.com', 'loc' => 'admin', 'created' => $now]);
        $this->insertRow('logins', ['ip' => '10.0.0.2', 'email' => 'jane@example.com', 'loc' => 'agent', 'created' => $now]);

        $this->Logins->clearLogin($this->request('jane@example.com'));

        $this->assertSame(0, $this->countRows('logins', "ip = '10.0.0.1' AND loc = 'agent'"));
        $this->assertSame(1, $this->countRows('logins', "ip = '10.0.0.1' AND loc = 'admin'"));
        $this->assertSame(1, $this->countRows('logins', "ip = '10.0.0.2'"));
    }
}
