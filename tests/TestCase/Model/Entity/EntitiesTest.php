<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Agent;
use App\Model\Entity\AgentUser;
use App\Model\Entity\Order;
use Cake\TestSuite\TestCase;

class EntitiesTest extends TestCase
{
    public function testAgentUserPasswordIsHashed(): void
    {
        $user = new AgentUser(['password' => 'Secret#12345']);

        $this->assertNotSame('Secret#12345', $user->password);
        $this->assertTrue(password_verify('Secret#12345', $user->password));
    }

    public function testAgentUserPrivilegedFieldsAreNotMassAssignable(): void
    {
        $user = new AgentUser();
        $user->patch([
            'name' => 'Jane', 'email' => 'jane@example.com',
            'role' => 'master', 'agent_id' => '00000000-0000-4000-8000-00000000000b', 'status' => 'active',
            'credits' => '9999', 'two_factor_enabled' => 0, 'totp_secret' => 'x',
        ]);

        $this->assertSame('Jane', $user->name);
        $this->assertSame('jane@example.com', $user->email);
        foreach (['role', 'agent_id', 'status', 'credits', 'two_factor_enabled', 'totp_secret'] as $field) {
            $this->assertNull($user->get($field), "$field must not be mass-assignable");
        }
    }

    public function testAgentUserSecretsAreHidden(): void
    {
        $user = new AgentUser([
            'id' => 'u1', 'email' => 'a@example.com',
            'password' => 'x', 'otp' => 123456, 'totp_secret' => 's', 'totp_last_step' => 1, 'backup_codes' => '[]',
        ]);

        $this->assertSame(['id' => 'u1', 'email' => 'a@example.com'], $user->toArray());
    }

    public function testAgent(): void
    {
        $agent = new Agent(['first_name' => 'Ann', 'last_name' => 'Lee', 'password' => 'pw']);

        $this->assertSame('Ann  Lee', $agent->fullname);
        $this->assertTrue(password_verify('pw', $agent->password));
        $this->assertArrayNotHasKey('password', $agent->toArray());
    }

    public function testOrderPaymentFieldsAreNotMassAssignable(): void
    {
        $order = new Order();
        $order->patch(['customer_name' => 'C', 'payment_method' => 'credits', 'pi_id' => 'pi_1', 'agent_id' => 'x']);

        $this->assertSame('C', $order->customer_name);
        $this->assertNull($order->payment_method);
        $this->assertNull($order->pi_id);
        $this->assertNull($order->agent_id);
    }
}
