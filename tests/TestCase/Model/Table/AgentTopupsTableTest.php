<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AgentTopupsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AgentTopupsTable Test Case
 */
class AgentTopupsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AgentTopupsTable
     */
    public $AgentTopups;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.AgentTopups',
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
        $config = TableRegistry::getTableLocator()->exists('AgentTopups') ? [] : ['className' => AgentTopupsTable::class];
        $this->AgentTopups = TableRegistry::getTableLocator()->get('AgentTopups', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->AgentTopups);

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
