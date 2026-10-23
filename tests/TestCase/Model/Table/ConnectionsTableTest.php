<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ConnectionsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ConnectionsTable Test Case
 */
class ConnectionsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ConnectionsTable
     */
    public $Connections;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.Connections',
        'app.Tickets',
        'app.Attractions'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Connections') ? [] : ['className' => ConnectionsTable::class];
        $this->Connections = TableRegistry::getTableLocator()->get('Connections', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Connections);

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
}
