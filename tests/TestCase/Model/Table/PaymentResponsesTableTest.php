<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PaymentResponsesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\PaymentResponsesTable Test Case
 */
class PaymentResponsesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\PaymentResponsesTable
     */
    public $PaymentResponses;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.PaymentResponses',
        'app.OrderReves',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('PaymentResponses') ? [] : ['className' => PaymentResponsesTable::class];
        $this->PaymentResponses = TableRegistry::getTableLocator()->get('PaymentResponses', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->PaymentResponses);

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
