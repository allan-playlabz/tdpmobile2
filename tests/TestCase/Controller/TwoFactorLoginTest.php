<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Lib\TwoFactor;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Database\Connection;
use Cake\I18n\DateTime;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;

/**
 * Login with the second factor: authenticator app, emailed OTP, backup codes,
 * first-time set-up, and the master's reset.
 */
class TwoFactorLoginTest extends TestCase
{
    use EmailTrait;
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
    private const IP = '10.1.2.3';

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
            ], ['id' => 'binaryuuid', 'agent_id' => 'binaryuuid']);
        }
        $this->configRequest(['environment' => ['REMOTE_ADDR' => self::IP]]);
    }

    private function setUser(string $id, array $values): void
    {
        $this->db->update('agent_users', $values, ['id' => $id], ['id' => 'binaryuuid']);
    }

    private function trustCurrentIp(string $id): void
    {
        $this->db->insert('last_otp_ips', [
            'agent_user_id' => $id, 'ip' => self::IP, 'created' => new DateTime(), 'modified' => new DateTime(),
        ], ['agent_user_id' => 'binaryuuid', 'created' => 'datetime', 'modified' => 'datetime']);
    }

    private function loginPost(string $email = 'master.a@example.com'): void
    {
        $this->enableCsrfToken();
        $this->post('/agent-users/login', ['email' => $email, 'password' => self::PASSWORD]);
    }

    private function row(string $id): array
    {
        return $this->db->execute(
            'SELECT otp, totp_secret, totp_enabled, totp_last_step, backup_codes FROM agent_users WHERE id = UNHEX(REPLACE(?, \'-\', \'\'))',
            [$id]
        )->fetch('assoc');
    }

    private function user(string $id)
    {
        return $this->getTableLocator()->get('AgentUsers')->get($id, contain: ['Agents']);
    }

    /**
     * Links an authenticator for the user; returns [secret, backup codes].
     */
    private function linkAuthenticator(string $id): array
    {
        $secret = TwoFactor::newSecret();
        $codes = TwoFactor::enable($this->user($id), $secret, 1);

        return [$secret, $codes];
    }

    private function appCode(string $secret): string
    {
        return (new TwoFactorAuth(new BaconQrCodeProvider()))->getCode($secret);
    }

    private function postCode(string $code, array $session = ['agent_id' => self::MASTER_A]): void
    {
        $this->session($session);
        $this->enableCsrfToken();
        $this->post('/agent-users/otp', ['otp' => $code]);
    }

    public function testNoCodeWhenAgentOtpIsOff(): void
    {
        $this->db->update('agents', ['mobile_otp' => 0], ['id' => self::AGENT_A], ['id' => 'binaryuuid']);
        $this->loginPost();

        $this->assertRedirectContains('city_id');
        $this->assertSession(self::MASTER_A, 'Auth.User.id');
        $this->assertNoMailSent();
    }

    public function testNoCodeWhenUserSwitchIsOff(): void
    {
        $this->setUser(self::MASTER_A, ['two_factor_enabled' => 0]);
        $this->loginPost();

        $this->assertSession(self::MASTER_A, 'Auth.User.id');
        $this->assertNoMailSent();
    }

    public function testEmailMethodKeepsTrustedIpSkip(): void
    {
        $this->setUser(self::MASTER_A, ['two_factor_method' => 'email_otp']);
        $this->trustCurrentIp(self::MASTER_A);
        $this->loginPost();

        $this->assertSession(self::MASTER_A, 'Auth.User.id');
        $this->assertNoMailSent();
    }

    public function testEmailMethodOnNewIpSendsCode(): void
    {
        $this->setUser(self::MASTER_A, ['two_factor_method' => 'email_otp']);
        $this->loginPost();

        $this->assertRedirectContains('/agent-users/otp');
        $this->assertSession(null, 'Auth.User');
        $this->assertMailSentTo('master.a@example.com');
    }

    public function testAuthenticatorAsksAppCodeEvenOnTrustedIp(): void
    {
        $this->linkAuthenticator(self::MASTER_A);
        $this->trustCurrentIp(self::MASTER_A);
        $this->loginPost();

        $this->assertRedirectContains('/agent-users/otp');
        $this->assertSession(null, 'Auth.User');
        $this->assertNoMailSent();
    }

    public function testAppCodeLogsInOnce(): void
    {
        [$secret] = $this->linkAuthenticator(self::MASTER_A);
        $code = $this->appCode($secret);

        $this->postCode($code);
        $this->assertRedirectContains('city_id');
        $this->assertSession(self::MASTER_A, 'Auth.User.id');
        $this->assertSession(null, 'Auth.User.totp_secret');

        $this->postCode($code);
        $this->assertSession(null, 'Auth.User');
        $this->assertFlashMessage('Incorrect code. Please try again.');
    }

    public function testWrongAppCodeCountsAttempt(): void
    {
        $this->linkAuthenticator(self::MASTER_A);

        $this->postCode('000000');
        $this->assertSession(null, 'Auth.User');
        $this->assertSession(1, 'otp_attempts');
    }

    public function testBackupCodeLogsIn(): void
    {
        [, $codes] = $this->linkAuthenticator(self::MASTER_A);

        $this->postCode($codes[0]);
        $this->assertSession(self::MASTER_A, 'Auth.User.id');
        $this->assertSame(9, TwoFactor::backupCodesLeft($this->user(self::MASTER_A)));
    }

    public function testResendOtpRefusedForAuthenticatorUsers(): void
    {
        $this->linkAuthenticator(self::MASTER_A);
        $this->session(['agent_id' => self::MASTER_A]);
        $this->enableCsrfToken();
        $this->post('/agent-users/resend-otp');

        $this->assertNoMailSent();
        $this->assertNull($this->row(self::MASTER_A)['otp']);
    }

    public function testFirstLoginEmailCodeThenSetupThenBackupCodes(): void
    {
        $this->loginPost();
        $this->assertRedirectContains('/agent-users/otp');
        $this->assertMailSentTo('master.a@example.com');
        $otp = (string)$this->row(self::MASTER_A)['otp'];

        $this->postCode($otp);
        $this->assertRedirectContains('/agent-users/two-factor/setup');
        $this->assertSession(null, 'Auth.User');

        $pending = ['agent_id' => self::MASTER_A, 'TwoFactor' => ['setup_pending' => time()]];
        $this->session($pending);
        $this->get('/agent-users/two-factor/setup');
        $this->assertResponseOk();
        $this->assertResponseContains('<svg');
        $secret = $this->_requestSession->read('TwoFactor.setup_secret');
        $this->assertNotEmpty($secret);

        $this->session(['agent_id' => self::MASTER_A, 'TwoFactor' => ['setup_pending' => time(), 'setup_secret' => $secret]]);
        $this->enableCsrfToken();
        $this->post('/agent-users/two-factor/setup', ['code' => $this->appCode($secret)]);
        $this->assertRedirectContains('/agent-users/two-factor/backup-codes');
        $this->assertNotNull($this->row(self::MASTER_A)['totp_enabled']);
        $codes = $this->_requestSession->read('TwoFactor.backup_codes');
        $this->assertCount(TwoFactor::BACKUP_CODE_COUNT, $codes);

        $this->session(['agent_id' => self::MASTER_A, 'TwoFactor' => ['setup_pending' => time(), 'backup_codes' => $codes]]);
        $this->get('/agent-users/two-factor/backup-codes');
        $this->assertResponseContains($codes[0]);

        $this->session(['agent_id' => self::MASTER_A, 'TwoFactor' => ['setup_pending' => time(), 'backup_codes' => $codes]]);
        $this->enableCsrfToken();
        $this->post('/agent-users/two-factor/backup-codes');
        $this->assertSession(self::MASTER_A, 'Auth.User.id');
        $this->assertSession(null, 'TwoFactor');
    }

    public function testSetupNeedsTheEmailCodeFirst(): void
    {
        $this->session(['agent_id' => self::MASTER_A]);
        $this->get('/agent-users/two-factor/setup');
        $this->assertRedirectContains('/agent-users/login');

        $this->session(['agent_id' => self::MASTER_A, 'TwoFactor' => ['setup_pending' => time() - 3600]]);
        $this->get('/agent-users/two-factor/setup');
        $this->assertRedirectContains('/agent-users/login');

        $this->session(['agent_id' => self::MASTER_A]);
        $this->get('/agent-users/two-factor/backup-codes');
        $this->assertRedirectContains('/agent-users/login');
    }

    public function testSetupCannotReplaceALinkedAuthenticatorBeforeLogin(): void
    {
        $this->linkAuthenticator(self::MASTER_A);
        $before = $this->row(self::MASTER_A)['totp_secret'];

        $this->session(['agent_id' => self::MASTER_A, 'TwoFactor' => ['setup_pending' => time()]]);
        $this->get('/agent-users/two-factor/setup');
        $this->assertRedirectContains('/agent-users/login');
        $this->assertSame($before, $this->row(self::MASTER_A)['totp_secret']);
    }

    private function loginAs(string $userId, string $agentId, string $role): void
    {
        $this->session(['Auth' => ['User' => [
            'id' => $userId, 'agent_id' => $agentId, 'role' => $role, 'email' => 'x@example.com', 'name' => 'Test',
            'credits' => '0.00',
            'agent' => ['mobile_otp' => true, 'low_credit_limit' => '0.00', 'first_name' => 'T', 'last_name' => 'A'],
        ]]]);
    }

    public function testSelfServicePageShowsStatus(): void
    {
        $this->linkAuthenticator(self::NORMAL_A);
        $this->loginAs(self::NORMAL_A, self::AGENT_A, 'normal');
        $this->get('/agent-users/two-factor');

        $this->assertResponseOk();
        $this->assertResponseContains('Authenticator app is set up');
    }

    public function testMasterResetsOwnSubUserOnly(): void
    {
        $this->linkAuthenticator(self::NORMAL_A);
        $this->linkAuthenticator(self::NORMAL_B);
        $this->linkAuthenticator(self::MASTER_A);

        $this->loginAs(self::MASTER_A, self::AGENT_A, 'master');
        $this->enableCsrfToken();
        $this->post('/agent-users/reset-two-factor/' . self::NORMAL_B);
        $this->assertNotNull($this->row(self::NORMAL_B)['totp_secret'], 'another agent\'s user is untouched');

        $this->loginAs(self::NORMAL_A, self::AGENT_A, 'normal');
        $this->enableCsrfToken();
        $this->post('/agent-users/reset-two-factor/' . self::MASTER_A);
        $this->assertNotNull($this->row(self::MASTER_A)['totp_secret'], 'a normal user cannot reset anyone');

        $this->loginAs(self::MASTER_A, self::AGENT_A, 'master');
        $this->enableCsrfToken();
        $this->post('/agent-users/reset-two-factor/' . self::NORMAL_A);
        $row = $this->row(self::NORMAL_A);
        $this->assertNull($row['totp_secret']);
        $this->assertNull($row['backup_codes']);
    }
}
