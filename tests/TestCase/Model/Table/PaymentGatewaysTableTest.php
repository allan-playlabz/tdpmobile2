<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PaymentGatewaysTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\PaymentGatewaysTable Test Case
 */
class PaymentGatewaysTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\PaymentGatewaysTable
     */
    public $PaymentGateways;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.PaymentGateways',
        'app.PaymentMarkups',
        'app.PaymentMethods',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('PaymentGateways') ? [] : ['className' => PaymentGatewaysTable::class];
        $this->PaymentGateways = TableRegistry::getTableLocator()->get('PaymentGateways', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->PaymentGateways);

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
