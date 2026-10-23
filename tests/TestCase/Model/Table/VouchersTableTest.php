<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\VouchersTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\VouchersTable Test Case
 */
class VouchersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\VouchersTable
     */
    public $Vouchers;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.Vouchers',
        'app.Codes',
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
        $config = TableRegistry::getTableLocator()->exists('Vouchers') ? [] : ['className' => VouchersTable::class];
        $this->Vouchers = TableRegistry::getTableLocator()->get('Vouchers', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Vouchers);

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
