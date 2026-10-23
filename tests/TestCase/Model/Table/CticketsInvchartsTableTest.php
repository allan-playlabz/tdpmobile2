<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CticketsInvchartsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\CticketsInvchartsTable Test Case
 */
class CticketsInvchartsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\CticketsInvchartsTable
     */
    public $CticketsInvcharts;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.CticketsInvcharts',
        'app.Tickets',
        'app.Invcharts'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('CticketsInvcharts') ? [] : ['className' => CticketsInvchartsTable::class];
        $this->CticketsInvcharts = TableRegistry::getTableLocator()->get('CticketsInvcharts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->CticketsInvcharts);

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
