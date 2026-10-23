<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PaymentMarkupsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\PaymentMarkupsTable Test Case
 */
class PaymentMarkupsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\PaymentMarkupsTable
     */
    public $PaymentMarkups;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.PaymentMarkups',
        'app.PaymentMethods',
        'app.PaymentGateways',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('PaymentMarkups') ? [] : ['className' => PaymentMarkupsTable::class];
        $this->PaymentMarkups = TableRegistry::getTableLocator()->get('PaymentMarkups', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->PaymentMarkups);

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
