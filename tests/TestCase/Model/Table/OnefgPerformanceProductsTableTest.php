<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\OnefgPerformanceProductsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\OnefgPerformanceProductsTable Test Case
 */
class OnefgPerformanceProductsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\OnefgPerformanceProductsTable
     */
    public $OnefgPerformanceProducts;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.OnefgPerformanceProducts'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('OnefgPerformanceProducts') ? [] : ['className' => OnefgPerformanceProductsTable::class];
        $this->OnefgPerformanceProducts = TableRegistry::getTableLocator()->get('OnefgPerformanceProducts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->OnefgPerformanceProducts);

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
