<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PaypalRecordsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\PaypalRecordsTable Test Case
 */
class PaypalRecordsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\PaypalRecordsTable
     */
    public $PaypalRecords;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.PaypalRecords',
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
        $config = TableRegistry::getTableLocator()->exists('PaypalRecords') ? [] : ['className' => PaypalRecordsTable::class];
        $this->PaypalRecords = TableRegistry::getTableLocator()->get('PaypalRecords', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->PaypalRecords);

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
