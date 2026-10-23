<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AgentUsersTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AgentUsersTable Test Case
 */
class AgentUsersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AgentUsersTable
     */
    public $AgentUsers;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.AgentUsers',
        'app.Agents',
        'app.Orders'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('AgentUsers') ? [] : ['className' => AgentUsersTable::class];
        $this->AgentUsers = TableRegistry::getTableLocator()->get('AgentUsers', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->AgentUsers);

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
