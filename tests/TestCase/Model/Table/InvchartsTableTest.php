<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\InvchartsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\InvchartsTable Test Case
 */
class InvchartsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\InvchartsTable
     */
    public $Invcharts;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Invcharts',
        'app.Skus',
        'app.Merchants',
        'app.Tickets'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Invcharts') ? [] : ['className' => InvchartsTable::class];
        $this->Invcharts = TableRegistry::getTableLocator()->get('Invcharts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Invcharts);

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
