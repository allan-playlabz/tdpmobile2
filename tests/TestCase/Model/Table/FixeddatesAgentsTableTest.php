<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\FixeddatesAgentsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\FixeddatesAgentsTable Test Case
 */
class FixeddatesAgentsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\FixeddatesAgentsTable
     */
    public $FixeddatesAgents;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Attractions',
        'app.Tickets',
        'app.Agents',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('FixeddatesAgents') ? [] : ['className' => FixeddatesAgentsTable::class];
        $this->FixeddatesAgents = TableRegistry::getTableLocator()->get('FixeddatesAgents', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->FixeddatesAgents);

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
