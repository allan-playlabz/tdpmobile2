<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\Support\AppDataTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\VoucherMergingController::getStatus(): merge status of the agent's own orders only.
 */
class VoucherMergingControllerTest extends TestCase
{
    use AppDataTrait;
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Agents',
        'app.AgentUsers',
        'app.Announcements',
        'app.Logs',
        'app.VoucherMerging',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAgentsAndUsers();
        foreach ([[self::AGENT_A, 'REFA1', 'done'], [self::AGENT_B, 'REFB1', 'creating']] as [$agentId, $ref, $status]) {
            $this->insertRow('voucher_merging', [
                'agent_id' => $agentId, 'order_ref_id' => $ref, 'type' => 'singlepdf', 'status' => $status,
                'created' => new DateTime(), 'modified' => new DateTime(),
            ]);
        }
    }

    private function mergeStatus(string $ref): mixed
    {
        $this->get('/voucher-merging/get-status?order_ref_id=' . $ref);
        $this->assertResponseOk();
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('voucher_merging', $json);

        return $json['voucher_merging'];
    }

    public function testOwnMergeIsReturned(): void
    {
        $this->loginAs(self::NORMAL_A);
        $merge = $this->mergeStatus('REFA1');

        $this->assertSame('REFA1', $merge['order_ref_id']);
        $this->assertSame('done', $merge['status']);
        $this->assertSame(self::AGENT_A, $merge['agent_id']);
    }

    public function testAnotherAgentsMergeIsNull(): void
    {
        $this->loginAs(self::MASTER_A);

        $this->assertNull($this->mergeStatus('REFB1'));
    }

    public function testRequiresLogin(): void
    {
        $this->get('/voucher-merging/get-status?order_ref_id=REFA1');

        $this->assertRedirectContains('/agent-users/login');
    }
}
