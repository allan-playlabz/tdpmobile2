<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SettlementAgentsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\SettlementAgentsTable Test Case
 */
class SettlementAgentsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\SettlementAgentsTable
     */
    public $SettlementAgents;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.SettlementAgents',
        'app.Attractions',
        'app.Tickets',
        'app.Settlements',
        'app.Agents'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('SettlementAgents') ? [] : ['className' => SettlementAgentsTable::class];
        $this->SettlementAgents = TableRegistry::getTableLocator()->get('SettlementAgents', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->SettlementAgents);

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
