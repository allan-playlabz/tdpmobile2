<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AgentsSignupsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AgentsSignupsTable Test Case
 */
class AgentsSignupsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AgentsSignupsTable
     */
    public $AgentsSignups;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.AgentsSignups',
        'app.MasterAgents',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('AgentsSignups') ? [] : ['className' => AgentsSignupsTable::class];
        $this->AgentsSignups = TableRegistry::getTableLocator()->get('AgentsSignups', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->AgentsSignups);

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
