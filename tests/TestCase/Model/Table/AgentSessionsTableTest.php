<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AgentSessionsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AgentSessionsTable Test Case
 */
class AgentSessionsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AgentSessionsTable
     */
    public $AgentSessions;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.AgentSessions',
        'app.AgentUsers',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('AgentSessions') ? [] : ['className' => AgentSessionsTable::class];
        $this->AgentSessions = TableRegistry::getTableLocator()->get('AgentSessions', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->AgentSessions);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
