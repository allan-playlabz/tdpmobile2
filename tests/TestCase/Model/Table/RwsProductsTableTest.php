<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\RwsProductsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\RwsProductsTable Test Case
 */
class RwsProductsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\RwsProductsTable
     */
    public $RwsProducts;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.RwsProducts'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('RwsProducts') ? [] : ['className' => RwsProductsTable::class];
        $this->RwsProducts = TableRegistry::getTableLocator()->get('RwsProducts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->RwsProducts);

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
