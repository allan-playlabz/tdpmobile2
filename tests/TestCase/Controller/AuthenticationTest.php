<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Guards the AuthComponent behaviour kept in the CakePHP 5 upgrade
 * (Authentication plugin, Session authenticator on `Auth.User`).
 */
class AuthenticationTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Agents',
        'app.AgentUsers',
    ];

    public function testProtectedPageRedirectsToLogin(): void
    {
        $this->get('/orders');
        $this->assertRedirectContains('/agent-users/login?redirect=%2Forders');
    }

    public function testLoginPageIsPublic(): void
    {
        $this->get('/agent-users/login');
        $this->assertResponseOk();
    }

    public function testSignupIsPublic(): void
    {
        $this->get('/signup');
        $this->assertResponseOk();
    }

    public function testLoggedInSessionLayoutIsAccepted(): void
    {
        $agentId = '00000000-0000-4000-8000-000000000002';
        $userId = '00000000-0000-4000-8000-000000000001';
        // Raw inserts: saving through the ORM runs LoggableBehavior, which needs getallheaders() (web SAPI only).
        $connection = $this->getTableLocator()->get('Agents')->getConnection();
        $connection->insert('agents', [
            'id' => $agentId, 'first_name' => 'Test', 'last_name' => 'Agent', 'email' => 'agent@example.com',
            'company_name' => 'Test Co', 'image' => '', 'country' => 'Singapore', 'gender' => 'male', 'enable_api' => 0,
        ], ['id' => 'binaryuuid']);
        $connection->insert('agent_users', [
            'id' => $userId, 'agent_id' => $agentId, 'name' => 'Test User', 'email' => 'agent@example.com', 'password' => 'x',
        ], ['id' => 'binaryuuid', 'agent_id' => 'binaryuuid']);

        // Same session layout AuthComponent wrote in 3.x.
        $this->session(['Auth' => ['User' => [
            'id' => $userId,
            'agent_id' => $agentId,
            'email' => 'agent@example.com',
            'name' => 'Test User',
            'role' => 'master',
            'credits' => '0.00',
            'agent' => ['mobile_otp' => false, 'low_credit_limit' => '0.00', 'first_name' => 'Test', 'last_name' => 'Agent'],
        ]]]);
        $this->get('/agent-users/change-password');
        $this->assertResponseOk();
    }

    public function testLoginWithWrongPasswordShowsError(): void
    {
        $this->enableCsrfToken();
        $this->post('/agent-users/login', ['email' => 'nobody@example.com', 'password' => 'x']);
        $this->assertResponseOk();
        $this->assertResponseContains('Wrong login information.');
        $this->assertSession(null, 'Auth.User');
    }
}
