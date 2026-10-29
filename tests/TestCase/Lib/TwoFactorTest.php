<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\TwoFactor;
use App\Model\Entity\AgentUser;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;

/**
 * TOTP codes, replay protection, backup codes and secret encryption.
 */
class TwoFactorTest extends TestCase
{
    protected array $fixtures = ['app.Agents', 'app.AgentUsers'];

    private const NOW = 1790000000;
    private const AGENT = '00000000-0000-4000-8000-00000000000a';
    private const USER = '00000000-0000-4000-8000-0000000000a1';

    protected function setUp(): void
    {
        parent::setUp();
        $db = $this->getTableLocator()->get('AgentUsers')->getConnection();
        $db->insert('agents', [
            'id' => self::AGENT, 'first_name' => 'Test', 'last_name' => 'Agent', 'email' => 'agent@example.com',
            'company_name' => 'Agent', 'image' => '', 'country' => 'Singapore', 'gender' => 'male', 'enable_api' => 0,
            'status' => 'active', 'enable_agent_login' => 1, 'mobile_otp' => 1, 'credits' => '0.00',
        ], ['id' => 'binaryuuid']);
        $db->insert('agent_users', [
            'id' => self::USER, 'agent_id' => self::AGENT, 'name' => 'User', 'email' => 'user@example.com',
            'password' => 'x', 'role' => 'master', 'status' => 'active',
        ], ['id' => 'binaryuuid', 'agent_id' => 'binaryuuid']);
    }

    private function code(string $secret, int $time): string
    {
        return (new TwoFactorAuth(new BaconQrCodeProvider()))->getCode($secret, $time);
    }

    private function load(): AgentUser
    {
        return $this->getTableLocator()->get('AgentUsers')->get(self::USER, contain: ['Agents']);
    }

    private function user(string $secret): AgentUser
    {
        TwoFactor::enable($this->load(), $secret, 1);

        return $this->load();
    }

    public function testRequiredNeedsAgentAndUserSwitch(): void
    {
        $user = $this->load();
        $this->assertTrue(TwoFactor::required($user));
        $this->assertTrue(TwoFactor::usesAuthenticator($user), 'authenticator is the default method');

        $user->two_factor_method = TwoFactor::METHOD_EMAIL;
        $this->assertTrue(TwoFactor::required($user));
        $this->assertFalse(TwoFactor::usesAuthenticator($user));

        $user->two_factor_enabled = false;
        $this->assertFalse(TwoFactor::required($user));

        $user->two_factor_enabled = true;
        $user->agent->mobile_otp = false;
        $this->assertFalse(TwoFactor::required($user), 'agents.mobile_otp switches 2FA off for the whole agent');
    }

    public function testCodeWorksOnceAndOnlyNearNow(): void
    {
        $secret = TwoFactor::newSecret();
        $user = $this->user($secret);

        $this->assertFalse(TwoFactor::verify($user, $this->code($secret, self::NOW - 60), self::NOW));
        $this->assertTrue(TwoFactor::verify($user, $this->code($secret, self::NOW - 30), self::NOW));
        $this->assertTrue(TwoFactor::verify($user, $this->code($secret, self::NOW), self::NOW));
        $this->assertFalse(TwoFactor::verify($user, $this->code($secret, self::NOW), self::NOW), 'the same step is not accepted twice');
        $this->assertFalse(TwoFactor::verify($this->load(), $this->code($secret, self::NOW - 30), self::NOW), 'an older step is not accepted after a newer one');
        $this->assertTrue(TwoFactor::verify($user, $this->code($secret, self::NOW + 30), self::NOW));
        $this->assertFalse(TwoFactor::verify($user, $this->code($secret, self::NOW + 90), self::NOW));
        $this->assertFalse(TwoFactor::verify($user, 'abcdef', self::NOW));
    }

    public function testBackupCodesWorkOnce(): void
    {
        $user = $this->load();
        $codes = TwoFactor::regenerateBackupCodes($user);

        $this->assertCount(TwoFactor::BACKUP_CODE_COUNT, $codes);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}$/', $codes[0]);
        $this->assertSame(9, TwoFactor::useBackupCode($user, strtolower(str_replace('-', ' ', $codes[0]))));
        $this->assertNull(TwoFactor::useBackupCode($user, $codes[0]));
        $this->assertSame(9, TwoFactor::backupCodesLeft($this->load()));
        $this->assertNull(TwoFactor::useBackupCode($user, 'AAAA-AAAA'));
    }

    public function testSecretIsStoredEncryptedAndHidden(): void
    {
        $secret = TwoFactor::newSecret();
        $user = $this->user($secret);

        $this->assertNotSame($secret, $user->totp_secret);
        $this->assertSame($secret, TwoFactor::decrypt((string)$user->totp_secret));
        $this->assertTrue(TwoFactor::isSetUp($user));
        $array = $user->toArray();
        foreach (['totp_secret', 'backup_codes', 'totp_last_step', 'otp', 'password'] as $field) {
            $this->assertArrayNotHasKey($field, $array);
        }
        $this->assertSame('', TwoFactor::decrypt('not-encrypted'));

        TwoFactor::reset(self::USER);
        $this->assertFalse(TwoFactor::isSetUp($this->load()));
    }

    public function testQrCodeIsInlineSvg(): void
    {
        $svg = TwoFactor::qrSvg('user@example.com', TwoFactor::newSecret());

        $this->assertStringStartsWith('<svg', $svg);
        $this->assertSame('ABCD EFGH IJ', TwoFactor::formatSecret('ABCDEFGHIJ'));
    }

    public function testDedicatedKeyStillReadsSaltEncryptedSecrets(): void
    {
        $configured = Configure::read('TwoFactor.encryptionKey');
        try {
            $secret = TwoFactor::newSecret();
            Configure::delete('TwoFactor.encryptionKey');
            $legacy = TwoFactor::encrypt($secret);
            Configure::write('TwoFactor.encryptionKey', str_repeat('k', 40));

            $this->assertSame($secret, TwoFactor::decrypt($legacy), 'secrets saved before the key was set still work');
            $encrypted = TwoFactor::encrypt($secret);
            $this->assertSame($secret, TwoFactor::decrypt($encrypted));
            Configure::write('TwoFactor.encryptionKey', str_repeat('z', 40));
            $this->assertSame('', TwoFactor::decrypt($encrypted), 'a different key cannot read it');
        } finally {
            Configure::write('TwoFactor.encryptionKey', $configured);
        }
    }

    public function testAStaleUserCannotReuseAClaimedCode(): void
    {
        $secret = TwoFactor::newSecret();
        $user = $this->user($secret);
        $stale = $this->load();

        $this->assertTrue(TwoFactor::verify($user, $this->code($secret, self::NOW), self::NOW));
        $this->assertFalse(TwoFactor::verify($stale, $this->code($secret, self::NOW), self::NOW), 'a second request holding the old row loses the race');
    }

    public function testAStaleUserCannotReuseABackupCode(): void
    {
        $user = $this->load();
        $codes = TwoFactor::regenerateBackupCodes($user);
        $stale = $this->load();

        $this->assertSame(9, TwoFactor::useBackupCode($user, $codes[0]));
        $this->assertNull(TwoFactor::useBackupCode($stale, $codes[0]), 'a second request holding the old row loses the race');
    }
}
