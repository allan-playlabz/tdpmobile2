<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\OrdersToCompleteTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\OrdersToCompleteTable Test Case
 */
class OrdersToCompleteTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\OrdersToCompleteTable
     */
    public $OrdersToComplete;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.OrdersToComplete',
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
        $config = TableRegistry::getTableLocator()->exists('OrdersToComplete') ? [] : ['className' => OrdersToCompleteTable::class];
        $this->OrdersToComplete = TableRegistry::getTableLocator()->get('OrdersToComplete', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->OrdersToComplete);

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
