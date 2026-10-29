<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\VouchersController;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use ReflectionMethod;

/**
 * Regression tests for the security fixes (access control, OTP, redirects,
 * S3 / voucher-link checks).
 */
class SecurityTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Agents',
        'app.AgentUsers',
        'app.Logins',
        'app.Logs',
        'app.UsersIpaddress',
        'app.LastOtpIps',
    ];

    private const AGENT_A = '00000000-0000-4000-8000-00000000000a';
    private const AGENT_B = '00000000-0000-4000-8000-00000000000b';
    private const MASTER_A = '00000000-0000-4000-8000-0000000000a1';
    private const NORMAL_A = '00000000-0000-4000-8000-0000000000a2';
    private const NORMAL_B = '00000000-0000-4000-8000-0000000000b2';
    private const PASSWORD = 'Secret#12345';

    private Connection $db;

    protected function setUp(): void
    {
        parent::setUp();
        TransportFactory::drop('default');
        TransportFactory::setConfig('default', ['className' => 'Debug']);

        $this->db = $this->getTableLocator()->get('Agents')->getConnection();
        // Raw inserts: the ORM would run LoggableBehavior, which isn't the subject here.
        foreach ([self::AGENT_A => 'Agent A', self::AGENT_B => 'Agent B'] as $id => $name) {
            $this->db->insert('agents', [
                'id' => $id, 'first_name' => 'Test', 'last_name' => $name, 'email' => strtolower(str_replace(' ', '', $name)) . '@example.com',
                'company_name' => $name, 'image' => '', 'country' => 'Singapore', 'gender' => 'male', 'enable_api' => 0,
                'status' => 'active', 'enable_agent_login' => 1, 'mobile_otp' => 1, 'credits' => '100.00',
            ], ['id' => 'binaryuuid']);
        }
        $hash = (new DefaultPasswordHasher())->hash(self::PASSWORD);
        $users = [
            [self::MASTER_A, self::AGENT_A, 'master', 'master.a@example.com'],
            [self::NORMAL_A, self::AGENT_A, 'normal', 'normal.a@example.com'],
            [self::NORMAL_B, self::AGENT_B, 'normal', 'normal.b@example.com'],
        ];
        foreach ($users as [$id, $agentId, $role, $email]) {
            $this->db->insert('agent_users', [
                'id' => $id, 'agent_id' => $agentId, 'name' => $role . ' user', 'email' => $email,
                'password' => $hash, 'role' => $role, 'status' => 'active',
                // The trusted-IP / OTP tests here are about the email-code flow (see TwoFactorLoginTest).
                'two_factor_method' => 'email_otp',
            ], ['id' => 'binaryuuid', 'agent_id' => 'binaryuuid']);
        }
        $this->configRequest(['environment' => ['REMOTE_ADDR' => '10.1.2.3']]);
    }

    private function loginAs(string $userId, string $agentId, string $role): void
    {
        $this->session(['Auth' => ['User' => [
            'id' => $userId, 'agent_id' => $agentId, 'role' => $role, 'email' => 'x@example.com', 'name' => 'Test',
            'credits' => '0.00',
            'agent' => ['mobile_otp' => true, 'low_credit_limit' => '0.00', 'first_name' => 'T', 'last_name' => 'A'],
        ]]]);
    }

    private function userRow(string $id): array
    {
        return $this->db->execute(
            'SELECT role, status, password, otp, LOWER(HEX(agent_id)) AS agent_hex FROM agent_users WHERE id = UNHEX(REPLACE(?, \'-\', \'\'))',
            [$id]
        )->fetch('assoc');
    }

    public function testChangePasswordCannotSetRoleOrAgent(): void
    {
        $this->loginAs(self::NORMAL_A, self::AGENT_A, 'normal');
        $this->enableCsrfToken();
        $this->post('/agent-users/change-password', [
            'old_password' => self::PASSWORD,
            'password' => 'NewSecret#123',
            'confirm_password' => 'NewSecret#123',
            'role' => 'master',
            'agent_id' => self::AGENT_B,
            'status' => 'inactive',
        ]);

        $row = $this->userRow(self::NORMAL_A);
        $this->assertSame('normal', $row['role']);
        $this->assertSame('active', $row['status']);
        $this->assertSame(str_replace('-', '', self::AGENT_A), $row['agent_hex']);
        $this->assertTrue((new DefaultPasswordHasher())->check('NewSecret#123', $row['password']), 'password itself is still changed');
    }

    public function testNormalUserCannotSuspendOrResetOthers(): void
    {
        $this->loginAs(self::NORMAL_A, self::AGENT_A, 'normal');
        $this->enableCsrfToken();
        $before = $this->userRow(self::MASTER_A);

        $this->post('/agent-users/suspend/' . self::MASTER_A);
        $this->assertRedirect();
        $this->post('/agent-users/agent-reset-password/' . self::MASTER_A);
        $this->assertRedirect();

        $after = $this->userRow(self::MASTER_A);
        $this->assertSame('active', $after['status']);
        $this->assertSame($before['password'], $after['password']);
    }

    public function testMasterCannotManageUsersOfAnotherAgent(): void
    {
        $this->loginAs(self::MASTER_A, self::AGENT_A, 'master');
        $this->enableCsrfToken();
        $before = $this->userRow(self::NORMAL_B);

        $this->post('/agent-users/suspend/' . self::NORMAL_B);
        $this->post('/agent-users/agent-reset-password/' . self::NORMAL_B);

        $after = $this->userRow(self::NORMAL_B);
        $this->assertSame('active', $after['status']);
        $this->assertSame($before['password'], $after['password']);
    }

    public function testMasterCanSuspendOwnSubUser(): void
    {
        $this->loginAs(self::MASTER_A, self::AGENT_A, 'master');
        $this->enableCsrfToken();
        $this->post('/agent-users/suspend/' . self::NORMAL_A);

        $this->assertSame('inactive', $this->userRow(self::NORMAL_A)['status']);
    }

    private function loginPost(string $query = ''): void
    {
        $this->enableCsrfToken();
        $this->post('/agent-users/login' . $query, ['email' => 'master.a@example.com', 'password' => self::PASSWORD]);
    }

    private function trustCurrentIp(): void
    {
        $this->db->insert('last_otp_ips', [
            'agent_user_id' => self::MASTER_A, 'ip' => '10.1.2.3', 'created' => new DateTime(), 'modified' => new DateTime(),
        ], ['agent_user_id' => 'binaryuuid', 'created' => 'datetime', 'modified' => 'datetime']);
    }

    public function testLoginRedirectRejectsExternalUrl(): void
    {
        $this->trustCurrentIp();
        $this->loginPost('?redirect=' . urlencode('https://evil.example/phish'));

        $this->assertRedirect();
        $this->assertHeaderNotContains('Location', 'evil.example');
    }

    public function testLoginRedirectKeepsLocalPath(): void
    {
        $this->trustCurrentIp();
        $this->loginPost('?redirect=' . urlencode('/orders'));

        $this->assertRedirectContains('/orders');
    }

    public function testSpoofedCloudflareHeaderDoesNotSkipOtp(): void
    {
        // Trusted IP is 10.9.9.9, but the request does not come from Cloudflare.
        $this->db->insert('last_otp_ips', [
            'agent_user_id' => self::MASTER_A, 'ip' => '10.9.9.9', 'created' => new DateTime(), 'modified' => new DateTime(),
        ], ['agent_user_id' => 'binaryuuid', 'created' => 'datetime', 'modified' => 'datetime']);
        $this->configRequest([
            'environment' => ['REMOTE_ADDR' => '10.1.2.3'],
            'headers' => ['CF-Connecting-IP' => '10.9.9.9'],
        ]);
        $this->loginPost();

        $this->assertRedirectContains('/agent-users/otp');
        $this->assertSession(null, 'Auth.User');
    }

    public function testOtpCannotBeReused(): void
    {
        $this->db->update('agent_users', ['otp' => 123456, 'otp_created' => new DateTime()], ['id' => self::MASTER_A], ['id' => 'binaryuuid', 'otp_created' => 'datetime']);
        $this->session(['agent_id' => self::MASTER_A]);
        $this->enableCsrfToken();

        $this->post('/agent-users/otp', ['otp' => '123456']);
        $this->assertRedirectContains('city_id'); // Attractions index after a successful OTP
        $this->assertSession(self::MASTER_A, 'Auth.User.id');
        $this->assertNull($this->userRow(self::MASTER_A)['otp'], 'OTP cleared after use');

        $this->session(['agent_id' => self::MASTER_A, 'Auth' => null]);
        $this->enableCsrfToken();
        $this->post('/agent-users/otp', ['otp' => '123456']);
        $this->assertRedirectContains('/agent-users/login');
    }

    public function testTopupImagesRejectsForeignKeys(): void
    {
        $this->loginAs(self::NORMAL_A, self::AGENT_A, 'normal');

        $this->get('/agent-topups/topup-images/vouchers/2026/0101/ABC_0.pdf');
        $this->assertResponseCode(404);
        $this->get('/agent-topups/topup-images/agenttopups/' . self::AGENT_B . '/proof.png');
        $this->assertResponseCode(404);
        $this->get('/agent-topups/topup-images/agenttopups/' . self::AGENT_A . '/x.html');
        $this->assertResponseCode(404);
    }

    public function testVoucherDownloadRejectsUnsignedMd5(): void
    {
        $this->get('/vouchers/download/123/' . md5('123'));
        $this->assertResponseCode(404);
    }

    public function testVoucherCheckFormats(): void
    {
        Configure::write('Api.secret', 'test-secret');
        $controller = new VouchersController(new ServerRequest());
        $check = new ReflectionMethod($controller, '_isValidVoucherCheck');

        $this->assertTrue($check->invoke($controller, '123', hash_hmac('sha256', '123', 'test-secret')));
        $this->assertFalse($check->invoke($controller, '123', md5('123')));

        Configure::write('Vouchers.allowLegacyMd5Links', true);
        $this->assertTrue($check->invoke($controller, '123', md5('123test-secret')));
        Configure::write('Vouchers.allowLegacyMd5Links', false);
        $this->assertFalse($check->invoke($controller, '123', md5('123test-secret')));
    }
}
